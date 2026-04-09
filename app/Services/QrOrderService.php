<?php

namespace App\Services;

use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\KitchenStation;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QrOrder;
use App\Models\QrOrderItem;
use App\Models\Table;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QrOrderService
{
    public function __construct(
        private TransactionService $transactionService,
        private KitchenOrderService $kitchenOrderService,
        private XenditService $xenditService,
        private PosSessionService $posSessionService
    ) {}

    /**
     * Load menu data for a QR token.
     *
     * @return array{outlet: Outlet, table: Table, categories: Collection, products: Collection, tax_mode: string, tax_percentage: float, service_charge_percentage: float}
     */
    public function getMenuData(string $qrToken): array
    {
        $table = Table::where('qr_token', $qrToken)
            ->where('is_active', true)
            ->with('outlet.tenant')
            ->firstOrFail();

        $outlet = $table->outlet;
        $tenant = $outlet->tenant;

        if (! $tenant->hasFeature('qr_order')) {
            abort(403, 'QR Order is not available for this merchant.');
        }

        $categories = ProductCategory::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $products = Product::where('tenant_id', $tenant->id)
            ->active()
            ->forMenu()
            ->with([
                'category',
                'variants' => fn ($q) => $q->where('is_active', true),
                'modifierGroups.modifiers' => fn ($q) => $q->where('is_active', true),
                'productOutlets' => fn ($q) => $q->where('outlet_id', $outlet->id),
            ])
            ->orderBy('sort_order')
            ->get();

        return [
            'outlet' => $outlet,
            'table' => $table,
            'tenant' => $tenant,
            'categories' => $categories,
            'products' => $products,
            'tax_mode' => $outlet->getTaxMode(),
            'tax_percentage' => $outlet->getEffectiveTaxPercentage(),
            'service_charge_percentage' => $outlet->getEffectiveServiceChargePercentage(),
        ];
    }

    /**
     * Create a QR order from cart items.
     */
    public function createOrder(
        Table $table,
        array $cartItems,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?string $notes = null
    ): QrOrder {
        return DB::transaction(function () use ($table, $cartItems, $customerName, $customerPhone, $notes) {
            $outlet = $table->outlet;
            $tenant = $outlet->tenant;

            // Calculate totals using TransactionService logic
            $calculation = $this->transactionService->calculateTransaction(
                $outlet->id,
                $cartItems
            );

            $orderNumber = QrOrder::generateOrderNumber($outlet->id);

            $qrOrder = QrOrder::create([
                'tenant_id' => $tenant->id,
                'outlet_id' => $outlet->id,
                'table_id' => $table->id,
                'order_number' => $orderNumber,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'notes' => $notes,
                'status' => QrOrder::STATUS_PENDING,
                'subtotal' => $calculation['subtotal'],
                'tax_amount' => $calculation['tax_amount'],
                'service_charge_amount' => $calculation['service_charge_amount'],
                'grand_total' => $calculation['grand_total'],
                'tax_mode' => $calculation['tax_mode'],
                'tax_percentage' => $calculation['tax_percentage'],
                'service_charge_percentage' => $calculation['service_charge_percentage'],
            ]);

            foreach ($calculation['items'] as $item) {
                QrOrderItem::create([
                    'qr_order_id' => $qrOrder->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'item_name' => $item['item_name'],
                    'item_sku' => $item['item_sku'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'modifiers_total' => $item['modifiers_total'] ?? 0,
                    'subtotal' => $item['subtotal'],
                    'modifiers' => $item['modifiers'] ?? null,
                    'item_notes' => $item['item_notes'] ?? null,
                ]);
            }

            return $qrOrder->load('items');
        });
    }

    /**
     * Initiate QRIS payment via Xendit.
     */
    public function initiateQrisPayment(QrOrder $qrOrder): QrOrder
    {
        $qrOrder->update(['payment_method' => QrOrder::PAYMENT_QRIS]);

        $invoiceData = $this->xenditService->createQrOrderInvoice($qrOrder);

        $qrOrder->update([
            'status' => QrOrder::STATUS_WAITING_PAYMENT,
            'xendit_invoice_id' => $invoiceData['xendit_invoice_id'],
            'xendit_invoice_url' => $invoiceData['xendit_invoice_url'],
            'xendit_response' => $invoiceData['xendit_response'],
            'xendit_expired_at' => $invoiceData['xendit_expired_at'],
        ]);

        return $qrOrder->fresh();
    }

    /**
     * Mark order as pay-at-counter (pending approval from staff).
     */
    public function selectPayAtCounter(QrOrder $qrOrder): QrOrder
    {
        $qrOrder->markAsPayAtCounter();

        return $qrOrder->fresh();
    }

    /**
     * Approve a QR order: create transaction, then send to kitchen (KDS).
     * Flow: customer pays at counter → staff approves → kitchen processes.
     */
    public function approveOrder(QrOrder $qrOrder, ?string $paymentMethodId = null): QrOrder
    {
        // 1. Create transaction (payment received)
        $transaction = $this->createTransactionFromQrOrder($qrOrder, $paymentMethodId);

        if ($transaction) {
            $qrOrder->update(['transaction_id' => $transaction->id]);
        }

        // 2. Send to kitchen
        $qrOrder->markAsProcessing();
        $this->createKitchenOrderFromQrOrder($qrOrder);

        return $qrOrder->fresh();
    }

    /**
     * Handle successful payment from Xendit webhook.
     */
    public function handlePaymentSuccess(QrOrder $qrOrder, array $payload): void
    {
        $qrOrder->markAsPaid();
        $qrOrder->update([
            'xendit_response' => $payload,
        ]);

        // Create transaction from QR order
        $transaction = $this->createTransactionFromQrOrder($qrOrder);

        if ($transaction) {
            $qrOrder->update([
                'transaction_id' => $transaction->id,
                'status' => QrOrder::STATUS_PROCESSING,
            ]);

            $this->createKitchenOrderFromQrOrder($qrOrder);
        }
    }

    /**
     * Cashier completes a pay-at-counter order (creates Transaction).
     */
    public function completePayAtCounterOrder(QrOrder $qrOrder): QrOrder
    {
        $transaction = $this->createTransactionFromQrOrder($qrOrder);

        if ($transaction) {
            $qrOrder->update([
                'transaction_id' => $transaction->id,
                'status' => QrOrder::STATUS_COMPLETED,
            ]);
        }

        return $qrOrder->fresh();
    }

    /**
     * Create a Transaction from a QrOrder (maps items to TransactionService format).
     */
    protected function createTransactionFromQrOrder(QrOrder $qrOrder, ?string $paymentMethodId = null): ?Transaction
    {
        $qrOrder->load('items');

        // Find active POS session for the outlet
        $session = $this->posSessionService->getOpenSessionForOutlet($qrOrder->outlet_id);

        if (! $session) {
            Log::warning('QR Order: No active POS session for outlet', [
                'qr_order_id' => $qrOrder->id,
                'outlet_id' => $qrOrder->outlet_id,
            ]);

            return null;
        }

        // Map QR order items to transaction format
        $items = $qrOrder->items->map(fn (QrOrderItem $item) => [
            'product_id' => $item->product_id,
            'variant_id' => $item->product_variant_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'modifiers' => collect($item->modifiers ?? [])->pluck('id')->toArray(),
            'notes' => $item->item_notes,
        ])->toArray();

        // Use provided payment method or auto-select (QRIS > Cash > any)
        if ($paymentMethodId) {
            $paymentMethod = PaymentMethod::where('id', $paymentMethodId)
                ->where('tenant_id', $qrOrder->tenant_id)
                ->where('is_active', true)
                ->first();
        } else {
            $paymentMethod = PaymentMethod::where('tenant_id', $qrOrder->tenant_id)
                ->where('is_active', true)
                ->orderByRaw("CASE WHEN code = 'qris' THEN 0 WHEN code = 'cash' THEN 1 ELSE 2 END")
                ->first();
        }

        if (! $paymentMethod) {
            Log::warning('QR Order: No active payment method', [
                'qr_order_id' => $qrOrder->id,
            ]);

            return null;
        }

        try {
            return $this->transactionService->createTransaction(
                $qrOrder->tenant_id,
                $qrOrder->outlet_id,
                $session->id,
                $session->user_id,
                $items,
                $paymentMethod->id,
                (float) $qrOrder->grand_total,
                null, // no customer_id
                [],   // no discounts
                null, // no points
                $qrOrder->order_number,
                "QR Order: {$qrOrder->order_number}".($qrOrder->notes ? " - {$qrOrder->notes}" : '')
            );
        } catch (\Exception $e) {
            Log::error('QR Order: Failed to create transaction', [
                'qr_order_id' => $qrOrder->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create KitchenOrder directly from QR order (for pay-at-counter flow).
     */
    protected function createKitchenOrderFromQrOrder(QrOrder $qrOrder): ?KitchenOrder
    {
        $qrOrder->load(['items.product', 'table']);

        $defaultStation = KitchenStation::where('outlet_id', $qrOrder->outlet_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();

        if (! $defaultStation) {
            // Auto-create a default kitchen station for this outlet
            $defaultStation = KitchenStation::create([
                'tenant_id' => $qrOrder->tenant_id,
                'outlet_id' => $qrOrder->outlet_id,
                'name' => 'Kitchen',
                'code' => 'KTC-'.strtoupper(substr($qrOrder->outlet_id, 0, 6)),
                'sort_order' => 1,
                'is_active' => true,
            ]);

            Log::info('Auto-created default kitchen station for outlet', [
                'outlet_id' => $qrOrder->outlet_id,
                'station_id' => $defaultStation->id,
            ]);
        }

        $kitchenOrder = KitchenOrder::create([
            'tenant_id' => $qrOrder->tenant_id,
            'outlet_id' => $qrOrder->outlet_id,
            'station_id' => $defaultStation->id,
            'table_id' => $qrOrder->table_id,
            'order_number' => $qrOrder->order_number,
            'order_type' => 'dine_in',
            'table_name' => $qrOrder->table?->name ?? $qrOrder->table?->number,
            'customer_name' => $qrOrder->customer_name ?? 'QR Guest',
            'status' => KitchenOrder::STATUS_PENDING,
            'priority' => KitchenOrder::PRIORITY_NORMAL,
            'notes' => $qrOrder->notes,
        ]);

        foreach ($qrOrder->items as $item) {
            KitchenOrderItem::create([
                'kitchen_order_id' => $kitchenOrder->id,
                'station_id' => $defaultStation->id,
                'item_name' => $item->item_name,
                'quantity' => $item->quantity,
                'modifiers' => $item->modifiers,
                'notes' => $item->item_notes,
                'status' => KitchenOrderItem::STATUS_PENDING,
            ]);
        }

        return $kitchenOrder->load('items');
    }

    /**
     * Cancel a QR order. Expire Xendit invoice if exists.
     */
    public function cancelOrder(QrOrder $qrOrder): void
    {
        if ($qrOrder->xendit_invoice_id) {
            try {
                $this->xenditService->expireInvoice($qrOrder->xendit_invoice_id);
            } catch (\Exception $e) {
                Log::warning('QR Order: Failed to expire Xendit invoice', [
                    'qr_order_id' => $qrOrder->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $qrOrder->markAsCancelled();
    }

    /**
     * Generate QR token for a table.
     */
    public function generateQrForTable(Table $table): string
    {
        return $table->generateQrToken();
    }

    /**
     * Revoke QR token for a table.
     */
    public function revokeQrForTable(Table $table): void
    {
        $table->revokeQrToken();
    }

    /**
     * Get pending orders for an outlet.
     */
    public function getPendingOrdersForOutlet(string $outletId): \Illuminate\Database\Eloquent\Collection
    {
        return QrOrder::where('outlet_id', $outletId)
            ->active()
            ->with(['items', 'table'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get order status data for customer polling.
     */
    public function getOrderStatus(QrOrder $qrOrder): array
    {
        return [
            'id' => $qrOrder->id,
            'order_number' => $qrOrder->order_number,
            'status' => $qrOrder->status,
            'payment_method' => $qrOrder->payment_method,
            'grand_total' => $qrOrder->grand_total,
            'xendit_invoice_url' => $qrOrder->xendit_invoice_url,
            'created_at' => $qrOrder->created_at->toISOString(),
        ];
    }
}
