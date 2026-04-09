<?php

namespace App\Services;

use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\KitchenStation;
use App\Models\Product;
use App\Models\Transaction;

class KitchenOrderService
{
    /**
     * Create a kitchen order from a completed transaction.
     * This is called after checkout to send the order to KDS.
     */
    public function createFromTransaction(Transaction $transaction): ?KitchenOrder
    {
        // Only create kitchen orders for dine_in and takeaway orders
        if (! in_array($transaction->order_type, ['dine_in', 'takeaway', 'take_away'])) {
            return null;
        }

        // Check if kitchen order already exists
        if ($transaction->kitchenOrder) {
            return $transaction->kitchenOrder;
        }

        // Get default station for this outlet (first active station)
        $defaultStation = KitchenStation::where('outlet_id', $transaction->outlet_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();

        // If no station exists, we can't create a kitchen order
        if (! $defaultStation) {
            return null;
        }

        // Load transaction items with product details
        $transaction->load(['items.product', 'table', 'customer']);

        // Create kitchen order
        $kitchenOrder = KitchenOrder::create([
            'tenant_id' => $transaction->tenant_id,
            'outlet_id' => $transaction->outlet_id,
            'transaction_id' => $transaction->id,
            'station_id' => $defaultStation->id,
            'table_id' => $transaction->table_id,
            'order_number' => $transaction->transaction_number,
            'order_type' => $this->normalizeOrderType($transaction->order_type),
            'table_name' => $transaction->table?->name ?? $transaction->table?->number,
            'customer_name' => $transaction->customer?->name ?? 'Guest',
            'status' => KitchenOrder::STATUS_PENDING,
            'priority' => KitchenOrder::PRIORITY_NORMAL,
            'notes' => $transaction->notes,
        ]);

        // Create kitchen order items
        foreach ($transaction->items as $item) {
            // Determine station for this item based on product category or default
            $itemStation = $this->getStationForProduct($item->product, $transaction->outlet_id) ?? $defaultStation;

            KitchenOrderItem::create([
                'kitchen_order_id' => $kitchenOrder->id,
                'transaction_item_id' => $item->id,
                'station_id' => $itemStation->id,
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
     * Get the appropriate kitchen station for a product.
     * This can be based on product category, tags, or specific configuration.
     */
    private function getStationForProduct(?Product $product, string $outletId): ?KitchenStation
    {
        if (! $product) {
            return null;
        }

        // Check if product has a specific kitchen_station_id
        if ($product->kitchen_station_id) {
            return KitchenStation::find($product->kitchen_station_id);
        }

        // Check if product category has a station mapping
        // Note: category_ids mapping is optional, so we skip this for now
        // to ensure SQLite compatibility during testing

        // Return null to use default station
        return null;
    }

    /**
     * Normalize order type to standard values.
     */
    private function normalizeOrderType(string $orderType): string
    {
        $mapping = [
            'dine_in' => 'dine_in',
            'dine-in' => 'dine_in',
            'dinein' => 'dine_in',
            'takeaway' => 'takeaway',
            'take_away' => 'takeaway',
            'take-away' => 'takeaway',
            'delivery' => 'delivery',
        ];

        return $mapping[strtolower($orderType)] ?? 'dine_in';
    }

    /**
     * Cancel kitchen order when transaction is voided.
     */
    public function cancelFromTransaction(Transaction $transaction, ?string $reason = null): void
    {
        $kitchenOrder = $transaction->kitchenOrder;

        if ($kitchenOrder && $kitchenOrder->canCancel()) {
            $kitchenOrder->cancel($reason ?? 'Transaction voided');
        }
    }

    /**
     * Update kitchen order priority.
     */
    public function updatePriority(KitchenOrder $order, string $priority): void
    {
        $order->setPriority($priority);
    }

    /**
     * Get pending orders count for an outlet.
     */
    public function getPendingCount(string $outletId): int
    {
        return KitchenOrder::where('outlet_id', $outletId)
            ->whereIn('status', [
                KitchenOrder::STATUS_PENDING,
                KitchenOrder::STATUS_PREPARING,
            ])
            ->count();
    }

    /**
     * Get average preparation time for an outlet (in minutes).
     */
    public function getAveragePreparationTime(string $outletId): float
    {
        $result = KitchenOrder::where('outlet_id', $outletId)
            ->where('status', KitchenOrder::STATUS_SERVED)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->whereDate('created_at', today())
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_time')
            ->value('avg_time');

        return round($result ?? 0, 1);
    }
}
