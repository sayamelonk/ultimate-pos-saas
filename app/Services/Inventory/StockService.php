<?php

namespace App\Services\Inventory;

use App\Models\InventoryStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function issueStock(
        string $outletId,
        string $inventoryItemId,
        float $quantity,
        string $userId,
        string $reason = '',
        ?string $referenceType = null,
        ?string $referenceId = null
    ): StockMovement {
        return DB::transaction(function () use ($outletId, $inventoryItemId, $quantity, $userId, $reason, $referenceType, $referenceId) {
            $stock = InventoryStock::where('outlet_id', $outletId)
                ->where('inventory_item_id', $inventoryItemId)
                ->first();

            if (! $stock) {
                throw new \Exception('No stock record found for this item at this outlet.');
            }

            $availableQty = $stock->quantity - ($stock->reserved_qty ?? 0);

            if ($availableQty < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$availableQty}, Requested: {$quantity}");
            }

            $stockBefore = $stock->quantity;
            $newQuantity = $stockBefore - $quantity;

            $stock->update([
                'quantity' => $newQuantity,
                'last_issued_at' => now(),
            ]);

            return StockMovement::create([
                'outlet_id' => $outletId,
                'inventory_item_id' => $inventoryItemId,
                'type' => StockMovement::TYPE_OUT,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity' => -$quantity,
                'cost_price' => $stock->avg_cost,
                'stock_before' => $stockBefore,
                'stock_after' => $newQuantity,
                'notes' => $reason,
                'created_by' => $userId,
            ]);
        });
    }

    public function receiveStock(
        string $outletId,
        string $inventoryItemId,
        float $quantity,
        float $unitCost,
        string $userId,
        string $reason = '',
        ?string $referenceType = null,
        ?string $referenceId = null
    ): StockMovement {
        return DB::transaction(function () use ($outletId, $inventoryItemId, $quantity, $unitCost, $userId, $reason, $referenceType, $referenceId) {
            $stock = InventoryStock::firstOrCreate(
                [
                    'outlet_id' => $outletId,
                    'inventory_item_id' => $inventoryItemId,
                ],
                [
                    'quantity' => 0,
                    'reserved_qty' => 0,
                    'avg_cost' => 0,
                    'last_cost' => 0,
                ]
            );

            $stockBefore = $stock->quantity;
            $newQuantity = $stockBefore + $quantity;

            // Calculate new average cost
            $currentValue = $stock->quantity * $stock->avg_cost;
            $newValue = $quantity * $unitCost;
            $newAvgCost = $newQuantity > 0 ? ($currentValue + $newValue) / $newQuantity : $unitCost;

            $stock->update([
                'quantity' => $newQuantity,
                'avg_cost' => $newAvgCost,
                'last_cost' => $unitCost,
                'last_received_at' => now(),
            ]);

            return StockMovement::create([
                'outlet_id' => $outletId,
                'inventory_item_id' => $inventoryItemId,
                'type' => StockMovement::TYPE_IN,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity' => $quantity,
                'cost_price' => $unitCost,
                'stock_before' => $stockBefore,
                'stock_after' => $newQuantity,
                'notes' => $reason,
                'created_by' => $userId,
            ]);
        });
    }

    public function adjustStock(
        string $outletId,
        string $inventoryItemId,
        float $adjustmentQuantity,
        string $userId,
        string $reason = '',
        ?string $referenceType = null,
        ?string $referenceId = null
    ): StockMovement {
        return DB::transaction(function () use ($outletId, $inventoryItemId, $adjustmentQuantity, $userId, $reason, $referenceType, $referenceId) {
            $stock = InventoryStock::firstOrCreate(
                [
                    'outlet_id' => $outletId,
                    'inventory_item_id' => $inventoryItemId,
                ],
                [
                    'quantity' => 0,
                    'reserved_qty' => 0,
                    'avg_cost' => 0,
                    'last_cost' => 0,
                ]
            );

            $stockBefore = $stock->quantity;
            $newQuantity = max(0, $stockBefore + $adjustmentQuantity);

            $stock->update([
                'quantity' => $newQuantity,
            ]);

            return StockMovement::create([
                'outlet_id' => $outletId,
                'inventory_item_id' => $inventoryItemId,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity' => $adjustmentQuantity,
                'cost_price' => $stock->avg_cost,
                'stock_before' => $stockBefore,
                'stock_after' => $newQuantity,
                'notes' => $reason,
                'created_by' => $userId,
            ]);
        });
    }

    public function reserveStock(
        string $outletId,
        string $inventoryItemId,
        float $quantity
    ): void {
        $stock = InventoryStock::where('outlet_id', $outletId)
            ->where('inventory_item_id', $inventoryItemId)
            ->first();

        if (! $stock) {
            throw new \Exception('No stock record found.');
        }

        $availableQty = $stock->quantity - ($stock->reserved_qty ?? 0);

        if ($availableQty < $quantity) {
            throw new \Exception("Insufficient stock to reserve. Available: {$availableQty}, Requested: {$quantity}");
        }

        $stock->increment('reserved_qty', $quantity);
    }

    public function releaseReservation(
        string $outletId,
        string $inventoryItemId,
        float $quantity
    ): void {
        $stock = InventoryStock::where('outlet_id', $outletId)
            ->where('inventory_item_id', $inventoryItemId)
            ->first();

        if ($stock) {
            $stock->decrement('reserved_qty', min($quantity, $stock->reserved_qty));
        }
    }

    public function getAvailableStock(string $outletId, string $inventoryItemId): float
    {
        $stock = InventoryStock::where('outlet_id', $outletId)
            ->where('inventory_item_id', $inventoryItemId)
            ->first();

        if (! $stock) {
            return 0;
        }

        return $stock->quantity - ($stock->reserved_qty ?? 0);
    }

    public function getTotalStock(string $inventoryItemId, ?array $outletIds = null): float
    {
        $query = InventoryStock::where('inventory_item_id', $inventoryItemId);

        if ($outletIds) {
            $query->whereIn('outlet_id', $outletIds);
        }

        return $query->sum('quantity');
    }

    public function recordWaste(
        string $outletId,
        string $inventoryItemId,
        float $quantity,
        string $userId,
        string $reason = ''
    ): StockMovement {
        return DB::transaction(function () use ($outletId, $inventoryItemId, $quantity, $userId, $reason) {
            $stock = InventoryStock::where('outlet_id', $outletId)
                ->where('inventory_item_id', $inventoryItemId)
                ->first();

            if (! $stock) {
                throw new \Exception('No stock record found for this item at this outlet.');
            }

            $stockBefore = $stock->quantity;
            $newQuantity = max(0, $stockBefore - $quantity);

            $stock->update([
                'quantity' => $newQuantity,
                'last_issued_at' => now(),
            ]);

            return StockMovement::create([
                'outlet_id' => $outletId,
                'inventory_item_id' => $inventoryItemId,
                'type' => StockMovement::TYPE_WASTE,
                'quantity' => -$quantity,
                'cost_price' => $stock->avg_cost,
                'stock_before' => $stockBefore,
                'stock_after' => $newQuantity,
                'notes' => $reason,
                'created_by' => $userId,
            ]);
        });
    }
}
