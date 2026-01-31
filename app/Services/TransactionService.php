<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionDiscount;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private StockService $stockService,
        private PriceService $priceService,
        private DiscountService $discountService,
        private CustomerService $customerService
    ) {}

    public function calculateTransaction(
        string $outletId,
        array $items,
        ?string $customerId = null,
        array $discounts = [],
        ?float $pointsToRedeem = null
    ): array {
        $outlet = Outlet::findOrFail($outletId);
        $customer = $customerId ? Customer::find($customerId) : null;

        $lineItems = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $inventoryItem = InventoryItem::findOrFail($item['inventory_item_id']);
            $quantity = $item['quantity'] ?? 1;
            $unitPrice = $item['unit_price'] ?? $this->priceService->getSellingPrice(
                $item['inventory_item_id'],
                $outletId,
                $customerId
            );

            $itemSubtotal = $quantity * $unitPrice;
            $subtotal += $itemSubtotal;

            $lineItems[] = [
                'inventory_item_id' => $inventoryItem->id,
                'item_name' => $inventoryItem->name,
                'item_sku' => $inventoryItem->sku,
                'quantity' => $quantity,
                'unit_name' => $inventoryItem->unit?->name ?? 'pcs',
                'unit_price' => $unitPrice,
                'cost_price' => $inventoryItem->cost_price,
                'discount_amount' => 0,
                'subtotal' => $itemSubtotal,
            ];
        }

        $discountResult = $this->discountService->calculateOrderDiscount($discounts, $subtotal, $lineItems);
        $discountAmount = $discountResult['total_discount'];

        $pointsDiscount = 0;
        if ($customer && $pointsToRedeem && $pointsToRedeem > 0) {
            $maxRedeemable = min($pointsToRedeem, $customer->total_points);
            $pointsDiscount = $this->customerService->getPointsValue($maxRedeemable);
            $pointsDiscount = min($pointsDiscount, $subtotal - $discountAmount);
        }

        $afterDiscount = $subtotal - $discountAmount - $pointsDiscount;

        $taxPercentage = $outlet->tax_percentage ?? 0;
        $taxAmount = ($afterDiscount * $taxPercentage) / 100;

        $serviceChargePercentage = $outlet->service_charge_percentage ?? 0;
        $serviceChargeAmount = ($afterDiscount * $serviceChargePercentage) / 100;

        $grandTotal = $afterDiscount + $taxAmount + $serviceChargeAmount;
        $grandTotal = round($grandTotal);

        $rounding = $grandTotal - ($afterDiscount + $taxAmount + $serviceChargeAmount);

        $pointsEarned = $customer ? $this->customerService->calculatePointsEarned($grandTotal) : 0;

        return [
            'items' => $lineItems,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'points_discount' => $pointsDiscount,
            'points_to_redeem' => $pointsToRedeem ? min($pointsToRedeem, $customer?->total_points ?? 0) : 0,
            'tax_percentage' => $taxPercentage,
            'tax_amount' => $taxAmount,
            'service_charge_percentage' => $serviceChargePercentage,
            'service_charge_amount' => $serviceChargeAmount,
            'rounding' => $rounding,
            'grand_total' => $grandTotal,
            'points_earned' => $pointsEarned,
            'applied_discounts' => $discountResult['applied_discounts'],
            'customer' => $customer,
        ];
    }

    public function createTransaction(
        string $tenantId,
        string $outletId,
        string $posSessionId,
        string $userId,
        array $items,
        string $paymentMethodId,
        float $paymentAmount,
        ?string $customerId = null,
        array $discounts = [],
        ?float $pointsToRedeem = null,
        ?string $referenceNumber = null,
        ?string $notes = null
    ): Transaction {
        return DB::transaction(function () use (
            $tenantId, $outletId, $posSessionId, $userId, $items,
            $paymentMethodId, $paymentAmount, $customerId, $discounts,
            $pointsToRedeem, $referenceNumber, $notes
        ) {
            $calculation = $this->calculateTransaction($outletId, $items, $customerId, $discounts, $pointsToRedeem);

            $transactionNumber = $this->generateTransactionNumber($outletId);

            $transaction = Transaction::create([
                'tenant_id' => $tenantId,
                'outlet_id' => $outletId,
                'pos_session_id' => $posSessionId,
                'customer_id' => $customerId,
                'user_id' => $userId,
                'transaction_number' => $transactionNumber,
                'type' => Transaction::TYPE_SALE,
                'subtotal' => $calculation['subtotal'],
                'discount_amount' => $calculation['discount_amount'] + $calculation['points_discount'],
                'tax_amount' => $calculation['tax_amount'],
                'service_charge_amount' => $calculation['service_charge_amount'],
                'rounding' => $calculation['rounding'],
                'grand_total' => $calculation['grand_total'],
                'payment_amount' => $paymentAmount,
                'change_amount' => max(0, $paymentAmount - $calculation['grand_total']),
                'tax_percentage' => $calculation['tax_percentage'],
                'service_charge_percentage' => $calculation['service_charge_percentage'],
                'points_earned' => $calculation['points_earned'],
                'points_redeemed' => $calculation['points_to_redeem'],
                'notes' => $notes,
                'status' => Transaction::STATUS_PENDING,
            ]);

            foreach ($calculation['items'] as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'item_name' => $item['item_name'],
                    'item_sku' => $item['item_sku'],
                    'quantity' => $item['quantity'],
                    'unit_name' => $item['unit_name'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $item['cost_price'],
                    'discount_amount' => $item['discount_amount'],
                    'subtotal' => $item['subtotal'] - $item['discount_amount'],
                ]);
            }

            $paymentMethod = PaymentMethod::findOrFail($paymentMethodId);
            $chargeAmount = $paymentMethod->calculateCharge($paymentAmount);

            TransactionPayment::create([
                'transaction_id' => $transaction->id,
                'payment_method_id' => $paymentMethodId,
                'amount' => $paymentAmount,
                'charge_amount' => $chargeAmount,
                'reference_number' => $referenceNumber,
            ]);

            foreach ($calculation['applied_discounts'] as $appliedDiscount) {
                TransactionDiscount::create([
                    'transaction_id' => $transaction->id,
                    'transaction_item_id' => $appliedDiscount['item_id'] ?? null,
                    'discount_id' => $appliedDiscount['discount_id'],
                    'discount_name' => $appliedDiscount['discount_name'],
                    'type' => $appliedDiscount['type'],
                    'value' => $appliedDiscount['value'],
                    'amount' => $appliedDiscount['amount'],
                ]);

                if ($appliedDiscount['discount_id']) {
                    $discount = Discount::find($appliedDiscount['discount_id']);
                    if ($discount) {
                        $this->discountService->applyDiscount($discount);
                    }
                }
            }

            if ($calculation['points_discount'] > 0) {
                TransactionDiscount::create([
                    'transaction_id' => $transaction->id,
                    'discount_name' => 'Points Redemption',
                    'type' => 'fixed_amount',
                    'value' => $calculation['points_to_redeem'],
                    'amount' => $calculation['points_discount'],
                ]);
            }

            $this->deductStock($transaction);

            $customer = $calculation['customer'];
            if ($customer) {
                if ($calculation['points_to_redeem'] > 0) {
                    $this->customerService->redeemPoints(
                        $customer,
                        $calculation['points_to_redeem'],
                        $transaction->id,
                        $userId
                    );
                }

                if ($calculation['points_earned'] > 0) {
                    $this->customerService->addPoints(
                        $customer,
                        $calculation['points_earned'],
                        $transaction->id,
                        $userId
                    );
                }

                $this->customerService->incrementVisit($customer);
                $this->customerService->addSpending($customer, $calculation['grand_total']);
            }

            $transaction->complete();

            return $transaction->fresh(['items', 'payments.paymentMethod', 'discounts', 'customer']);
        });
    }

    public function voidTransaction(Transaction $transaction, string $userId, string $reason): Transaction
    {
        if (! $transaction->canVoid()) {
            throw new \RuntimeException('Transaction cannot be voided');
        }

        return DB::transaction(function () use ($transaction, $userId, $reason) {
            $this->restoreStock($transaction, $userId);

            if ($transaction->customer_id) {
                $customer = $transaction->customer;

                if ($transaction->points_redeemed > 0) {
                    $this->customerService->adjustPoints(
                        $customer,
                        $transaction->points_redeemed,
                        $userId,
                        'Points restored from voided transaction'
                    );
                }

                if ($transaction->points_earned > 0) {
                    $this->customerService->adjustPoints(
                        $customer,
                        -$transaction->points_earned,
                        $userId,
                        'Points removed from voided transaction'
                    );
                }

                $customer->decrement('total_visits');
                $customer->decrement('total_spent', $transaction->grand_total);
            }

            $transaction->void();
            $transaction->update(['notes' => ($transaction->notes ? $transaction->notes."\n" : '')."Voided: {$reason}"]);

            return $transaction->fresh();
        });
    }

    public function refundTransaction(
        Transaction $originalTransaction,
        array $itemsToRefund,
        string $userId,
        string $paymentMethodId,
        string $reason
    ): Transaction {
        if (! $originalTransaction->canRefund()) {
            throw new \RuntimeException('Transaction cannot be refunded');
        }

        return DB::transaction(function () use ($originalTransaction, $itemsToRefund, $userId, $paymentMethodId, $reason) {
            $refundAmount = 0;
            $refundItems = [];

            foreach ($itemsToRefund as $refundItem) {
                $originalItem = $originalTransaction->items()->find($refundItem['transaction_item_id']);

                if (! $originalItem) {
                    throw new \RuntimeException('Invalid item for refund');
                }

                $refundQty = $refundItem['quantity'];
                $itemRefundAmount = ($originalItem->subtotal / $originalItem->quantity) * $refundQty;
                $refundAmount += $itemRefundAmount;

                $refundItems[] = [
                    'inventory_item_id' => $originalItem->inventory_item_id,
                    'item_name' => $originalItem->item_name,
                    'item_sku' => $originalItem->item_sku,
                    'quantity' => $refundQty,
                    'unit_name' => $originalItem->unit_name,
                    'unit_price' => $originalItem->unit_price,
                    'cost_price' => $originalItem->cost_price,
                    'subtotal' => $itemRefundAmount,
                ];
            }

            $transactionNumber = $this->generateTransactionNumber($originalTransaction->outlet_id);

            $refundTransaction = Transaction::create([
                'tenant_id' => $originalTransaction->tenant_id,
                'outlet_id' => $originalTransaction->outlet_id,
                'pos_session_id' => $originalTransaction->pos_session_id,
                'customer_id' => $originalTransaction->customer_id,
                'user_id' => $userId,
                'transaction_number' => $transactionNumber,
                'type' => Transaction::TYPE_REFUND,
                'original_transaction_id' => $originalTransaction->id,
                'subtotal' => $refundAmount,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'service_charge_amount' => 0,
                'rounding' => 0,
                'grand_total' => $refundAmount,
                'payment_amount' => $refundAmount,
                'change_amount' => 0,
                'notes' => "Refund: {$reason}",
                'status' => Transaction::STATUS_PENDING,
            ]);

            foreach ($refundItems as $item) {
                TransactionItem::create(array_merge($item, [
                    'transaction_id' => $refundTransaction->id,
                    'discount_amount' => 0,
                ]));

                $this->stockService->receiveStock(
                    $originalTransaction->outlet_id,
                    $item['inventory_item_id'],
                    $item['quantity'],
                    $item['cost_price'],
                    $userId
                );
            }

            TransactionPayment::create([
                'transaction_id' => $refundTransaction->id,
                'payment_method_id' => $paymentMethodId,
                'amount' => $refundAmount,
                'charge_amount' => 0,
            ]);

            $refundTransaction->complete();

            return $refundTransaction->fresh(['items', 'payments.paymentMethod']);
        });
    }

    private function deductStock(Transaction $transaction): void
    {
        foreach ($transaction->items as $item) {
            $this->stockService->issueStock(
                $transaction->outlet_id,
                $item->inventory_item_id,
                $item->quantity,
                StockMovement::TYPE_OUT,
                $transaction->user_id,
                'transaction',
                $transaction->id,
                "Sale: {$transaction->transaction_number}"
            );
        }
    }

    private function restoreStock(Transaction $transaction, string $userId): void
    {
        foreach ($transaction->items as $item) {
            $this->stockService->receiveStock(
                $transaction->outlet_id,
                $item->inventory_item_id,
                $item->quantity,
                $item->cost_price,
                $userId
            );
        }
    }

    private function generateTransactionNumber(string $outletId): string
    {
        $outlet = Outlet::find($outletId);
        $outletCode = $outlet ? strtoupper(substr($outlet->code ?? $outlet->name, 0, 5)) : 'TRX';
        $date = now()->format('Ymd');

        $todayCount = Transaction::where('outlet_id', $outletId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $sequence = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

        return "{$outletCode}-{$date}-{$sequence}";
    }

    public function getTransactionHistory(
        string $outletId,
        ?string $status = null,
        ?string $type = null,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
        int $perPage = 15
    ) {
        $query = Transaction::where('outlet_id', $outletId)
            ->with(['customer', 'user', 'items', 'payments.paymentMethod']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
