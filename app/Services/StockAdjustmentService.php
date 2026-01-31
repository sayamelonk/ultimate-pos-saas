<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function __construct(
        private StockService $stockService
    ) {}

    /**
     * Generate adjustment number
     */
    public function generateAdjustmentNumber(string $tenantId): string
    {
        $prefix = 'ADJ';
        $date = now()->format('Ymd');

        $last = StockAdjustment::where('tenant_id', $tenantId)
            ->where('adjustment_number', 'like', "{$prefix}{$date}%")
            ->orderBy('adjustment_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->adjustment_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$prefix}{$date}{$nextNumber}";
    }

    /**
     * Create stock adjustment
     */
    public function createAdjustment(array $data, string $userId): StockAdjustment
    {
        return StockAdjustment::create([
            'tenant_id' => $data['tenant_id'],
            'outlet_id' => $data['outlet_id'],
            'adjustment_number' => $data['adjustment_number'] ?? $this->generateAdjustmentNumber($data['tenant_id']),
            'adjustment_date' => $data['adjustment_date'] ?? now(),
            'type' => $data['type'],
            'status' => StockAdjustment::STATUS_DRAFT,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
        ]);
    }

    /**
     * Add item to adjustment
     */
    public function addAdjustmentItem(StockAdjustment $adjustment, array $data): StockAdjustmentItem
    {
        // Get current system quantity
        $stock = $this->stockService->getOrCreateStock(
            $adjustment->outlet_id,
            $data['inventory_item_id']
        );

        $item = new StockAdjustmentItem([
            'stock_adjustment_id' => $adjustment->id,
            'inventory_item_id' => $data['inventory_item_id'],
            'batch_id' => $data['batch_id'] ?? null,
            'system_qty' => $stock->quantity,
            'actual_qty' => $data['actual_qty'],
            'cost_price' => $stock->avg_cost,
            'notes' => $data['notes'] ?? null,
        ]);

        $item->calculateDifference();
        $item->save();

        return $item;
    }

    /**
     * Update adjustment item
     */
    public function updateAdjustmentItem(StockAdjustmentItem $item, array $data): StockAdjustmentItem
    {
        if (isset($data['actual_qty'])) {
            $item->actual_qty = $data['actual_qty'];
        }

        if (isset($data['notes'])) {
            $item->notes = $data['notes'];
        }

        $item->calculateDifference();
        $item->save();

        return $item;
    }

    /**
     * Remove adjustment item
     */
    public function removeAdjustmentItem(StockAdjustmentItem $item): void
    {
        $item->delete();
    }

    /**
     * Refresh system quantities for all items
     */
    public function refreshSystemQuantities(StockAdjustment $adjustment): void
    {
        foreach ($adjustment->items as $item) {
            $stock = $this->stockService->getOrCreateStock(
                $adjustment->outlet_id,
                $item->inventory_item_id
            );

            $item->system_qty = $stock->quantity;
            $item->cost_price = $stock->avg_cost;
            $item->calculateDifference();
            $item->save();
        }
    }

    /**
     * Approve and apply adjustment
     */
    public function approve(StockAdjustment $adjustment, string $approverId): StockAdjustment
    {
        if (! $adjustment->isDraft()) {
            throw new \Exception('Only draft adjustments can be approved');
        }

        if ($adjustment->items->isEmpty()) {
            throw new \Exception('Cannot approve empty adjustment');
        }

        return DB::transaction(function () use ($adjustment, $approverId) {
            // Refresh system quantities before applying
            $this->refreshSystemQuantities($adjustment);

            // Apply adjustments
            foreach ($adjustment->items as $item) {
                if ($item->hasVariance()) {
                    $this->stockService->adjustStock(
                        $adjustment->outlet_id,
                        $item->inventory_item_id,
                        $item->actual_qty,
                        $approverId,
                        'adjustment',
                        $adjustment->id,
                        $adjustment->reason ?? 'Stock adjustment'
                    );
                }
            }

            $adjustment->status = StockAdjustment::STATUS_APPROVED;
            $adjustment->approved_by = $approverId;
            $adjustment->approved_at = now();
            $adjustment->save();

            return $adjustment;
        });
    }

    /**
     * Cancel adjustment
     */
    public function cancel(StockAdjustment $adjustment): StockAdjustment
    {
        if (! $adjustment->isDraft()) {
            throw new \Exception('Only draft adjustments can be cancelled');
        }

        $adjustment->status = StockAdjustment::STATUS_CANCELLED;
        $adjustment->save();

        return $adjustment;
    }

    /**
     * Create stock take adjustment from physical count
     */
    public function createFromStockTake(
        string $tenantId,
        string $outletId,
        array $counts,
        string $userId,
        ?string $reason = null
    ): StockAdjustment {
        return DB::transaction(function () use ($tenantId, $outletId, $counts, $userId, $reason) {
            $adjustment = $this->createAdjustment([
                'tenant_id' => $tenantId,
                'outlet_id' => $outletId,
                'type' => StockAdjustment::TYPE_STOCK_TAKE,
                'reason' => $reason ?? 'Physical stock count',
            ], $userId);

            foreach ($counts as $inventoryItemId => $actualQty) {
                $this->addAdjustmentItem($adjustment, [
                    'inventory_item_id' => $inventoryItemId,
                    'actual_qty' => $actualQty,
                ]);
            }

            return $adjustment->fresh(['items.inventoryItem']);
        });
    }

    /**
     * Create opening balance adjustment
     */
    public function createOpeningBalance(
        string $tenantId,
        string $outletId,
        array $balances,
        string $userId
    ): StockAdjustment {
        return DB::transaction(function () use ($tenantId, $outletId, $balances, $userId) {
            $adjustment = $this->createAdjustment([
                'tenant_id' => $tenantId,
                'outlet_id' => $outletId,
                'type' => StockAdjustment::TYPE_OPENING_BALANCE,
                'reason' => 'Opening balance setup',
            ], $userId);

            foreach ($balances as $balance) {
                $this->addAdjustmentItem($adjustment, [
                    'inventory_item_id' => $balance['inventory_item_id'],
                    'actual_qty' => $balance['quantity'],
                ]);
            }

            return $adjustment->fresh(['items.inventoryItem']);
        });
    }

    /**
     * Get adjustment summary
     */
    public function getAdjustmentSummary(StockAdjustment $adjustment): array
    {
        $items = $adjustment->items;

        return [
            'total_items' => $items->count(),
            'items_with_variance' => $items->filter(fn ($i) => $i->hasVariance())->count(),
            'total_increase' => $items->where('difference', '>', 0)->sum('difference'),
            'total_decrease' => abs($items->where('difference', '<', 0)->sum('difference')),
            'total_value_difference' => $items->sum('value_difference'),
            'positive_value' => $items->where('value_difference', '>', 0)->sum('value_difference'),
            'negative_value' => abs($items->where('value_difference', '<', 0)->sum('value_difference')),
        ];
    }

    /**
     * Get items that need counting for an outlet
     */
    public function getItemsForStockTake(string $outletId): \Illuminate\Database\Eloquent\Collection
    {
        return InventoryStock::where('outlet_id', $outletId)
            ->with('inventoryItem.unit')
            ->orderBy('inventory_item_id')
            ->get();
    }
}
