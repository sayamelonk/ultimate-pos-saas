<?php

namespace App\Services\Inventory;

use App\Models\InventoryStock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function createAdjustment(
        string $tenantId,
        string $outletId,
        string $userId,
        string $type,
        array $items,
        ?string $adjustmentDate = null,
        ?string $reason = null,
        ?string $notes = null
    ): StockAdjustment {
        return DB::transaction(function () use ($tenantId, $outletId, $userId, $type, $items, $adjustmentDate, $reason, $notes) {
            $adjustmentNumber = $this->generateAdjustmentNumber($tenantId);

            $adjustment = StockAdjustment::create([
                'tenant_id' => $tenantId,
                'outlet_id' => $outletId,
                'adjustment_number' => $adjustmentNumber,
                'adjustment_date' => $adjustmentDate ?? now()->toDateString(),
                'type' => $type,
                'status' => 'draft',
                'reason' => $reason,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            $totalVariance = 0;

            foreach ($items as $item) {
                $systemQty = $item['system_quantity'] ?? 0;
                $actualQty = $item['actual_quantity'] ?? 0;
                $difference = $actualQty - $systemQty;
                $totalVariance += abs($difference);

                $adjustment->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'system_qty' => $systemQty,
                    'actual_qty' => $actualQty,
                    'difference' => $difference,
                    'cost_price' => 0,
                    'value_difference' => 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $adjustment->update(['total_variance' => $totalVariance]);

            return $adjustment;
        });
    }

    public function approveAdjustment(StockAdjustment $adjustment, string $approvedBy): StockAdjustment
    {
        if ($adjustment->status !== 'draft') {
            throw new \Exception('Only draft adjustments can be approved.');
        }

        return DB::transaction(function () use ($adjustment, $approvedBy) {
            $adjustment->load('items.inventoryItem');

            foreach ($adjustment->items as $item) {
                // Get or create stock record
                $stock = InventoryStock::firstOrCreate(
                    [
                        'outlet_id' => $adjustment->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_qty' => 0,
                        'avg_cost' => 0,
                        'last_cost' => 0,
                    ]
                );

                $stockBefore = $stock->quantity;
                $variance = $item->difference;

                if ($variance != 0) {
                    // Update stock quantity
                    $newQuantity = $stockBefore + $variance;
                    $stock->update(['quantity' => max(0, $newQuantity)]);

                    // Create stock movement
                    StockMovement::create([
                        'outlet_id' => $adjustment->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'type' => StockMovement::TYPE_ADJUSTMENT,
                        'reference_type' => StockAdjustment::class,
                        'reference_id' => $adjustment->id,
                        'quantity' => $variance,
                        'cost_price' => $stock->avg_cost,
                        'stock_before' => $stockBefore,
                        'stock_after' => max(0, $newQuantity),
                        'notes' => "Stock adjustment: {$adjustment->adjustment_number} ({$adjustment->type})",
                        'created_by' => $approvedBy,
                    ]);
                }
            }

            $adjustment->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            return $adjustment->fresh();
        });
    }

    public function rejectAdjustment(StockAdjustment $adjustment): StockAdjustment
    {
        if ($adjustment->status !== 'draft') {
            throw new \Exception('Only draft adjustments can be rejected.');
        }

        $adjustment->update(['status' => 'rejected']);

        return $adjustment->fresh();
    }

    public function cancelAdjustment(StockAdjustment $adjustment): StockAdjustment
    {
        if (! in_array($adjustment->status, ['draft'])) {
            throw new \Exception('Only draft adjustments can be cancelled.');
        }

        $adjustment->update(['status' => 'cancelled']);

        return $adjustment->fresh();
    }

    private function generateAdjustmentNumber(string $tenantId): string
    {
        $prefix = 'ADJ';
        $date = now()->format('Ymd');

        $lastAdjustment = StockAdjustment::where('tenant_id', $tenantId)
            ->where('adjustment_number', 'like', "{$prefix}{$date}%")
            ->orderBy('adjustment_number', 'desc')
            ->first();

        if ($lastAdjustment) {
            $lastNumber = (int) substr($lastAdjustment->adjustment_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$date}{$newNumber}";
    }
}
