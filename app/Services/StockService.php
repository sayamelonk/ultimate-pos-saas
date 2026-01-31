<?php

namespace App\Services;

use App\Models\GoodsReceiveItem;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Receive stock into an outlet (from goods receive)
     */
    public function receiveStock(
        string $outletId,
        string $inventoryItemId,
        float $quantity,
        float $costPrice,
        string $userId,
        ?GoodsReceiveItem $grItem = null,
        ?string $batchNumber = null,
        ?\DateTimeInterface $expiryDate = null
    ): StockMovement {
        return DB::transaction(function () use ($outletId, $inventoryItemId, $quantity, $costPrice, $userId, $grItem, $batchNumber, $expiryDate) {
            $item = InventoryItem::findOrFail($inventoryItemId);
            $stock = $this->getOrCreateStock($outletId, $inventoryItemId);

            $stockBefore = $stock->quantity;

            // Update weighted average cost
            $totalValue = ($stock->quantity * $stock->avg_cost) + ($quantity * $costPrice);
            $totalQty = $stock->quantity + $quantity;
            $newAvgCost = $totalQty > 0 ? $totalValue / $totalQty : $costPrice;

            $stock->quantity = $totalQty;
            $stock->avg_cost = $newAvgCost;
            $stock->last_cost = $costPrice;
            $stock->last_received_at = now();
            $stock->save();

            // Create batch if tracking is enabled
            $batch = null;
            if ($item->track_batches && $batchNumber) {
                $batch = StockBatch::create([
                    'outlet_id' => $outletId,
                    'inventory_item_id' => $inventoryItemId,
                    'batch_number' => $batchNumber,
                    'production_date' => $grItem?->production_date,
                    'expiry_date' => $expiryDate,
                    'initial_qty' => $quantity,
                    'current_qty' => $quantity,
                    'cost_price' => $costPrice,
                    'goods_receive_item_id' => $grItem?->id,
                    'status' => 'available',
                ]);
            }

            // Record movement
            return StockMovement::create([
                'outlet_id' => $outletId,
                'inventory_item_id' => $inventoryItemId,
                'batch_id' => $batch?->id,
                'type' => StockMovement::TYPE_IN,
                'reference_type' => $grItem ? 'goods_receive' : null,
                'reference_id' => $grItem?->goods_receive_id,
                'quantity' => $quantity,
                'cost_price' => $costPrice,
                'stock_before' => $stockBefore,
                'stock_after' => $stock->quantity,
                'notes' => 'Stock received',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Issue stock from an outlet (for orders, waste, etc.)
     */
    public function issueStock(
        string $outletId,
        string $inventoryItemId,
        float $quantity,
        string $type,
        string $userId,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null
    ): StockMovement {
        return DB::transaction(function () use ($outletId, $inventoryItemId, $quantity, $type, $userId, $referenceType, $referenceId, $notes) {
            $item = InventoryItem::findOrFail($inventoryItemId);
            $stock = $this->getOrCreateStock($outletId, $inventoryItemId);

            $stockBefore = $stock->quantity;
            $costPrice = $stock->avg_cost;

            // Deduct from batches if tracking enabled (FIFO/FEFO)
            $batch = null;
            if ($item->track_batches) {
                $batch = $this->deductFromBatches($outletId, $inventoryItemId, $quantity);
            }

            $stock->quantity -= $quantity;
            $stock->last_issued_at = now();
            $stock->save();

            return StockMovement::create([
                'outlet_id' => $outletId,
                'inventory_item_id' => $inventoryItemId,
                'batch_id' => $batch?->id,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity' => -$quantity,
                'cost_price' => $costPrice,
                'stock_before' => $stockBefore,
                'stock_after' => $stock->quantity,
                'notes' => $notes,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Adjust stock (for stock take, corrections)
     */
    public function adjustStock(
        string $outletId,
        string $inventoryItemId,
        float $newQuantity,
        string $userId,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null
    ): StockMovement {
        return DB::transaction(function () use ($outletId, $inventoryItemId, $newQuantity, $userId, $referenceType, $referenceId, $notes) {
            $stock = $this->getOrCreateStock($outletId, $inventoryItemId);

            $stockBefore = $stock->quantity;
            $difference = $newQuantity - $stockBefore;

            $stock->quantity = $newQuantity;
            $stock->save();

            return StockMovement::create([
                'outlet_id' => $outletId,
                'inventory_item_id' => $inventoryItemId,
                'batch_id' => null,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'quantity' => $difference,
                'cost_price' => $stock->avg_cost,
                'stock_before' => $stockBefore,
                'stock_after' => $newQuantity,
                'notes' => $notes ?? 'Stock adjustment',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Transfer stock between outlets
     */
    public function transferStock(
        string $fromOutletId,
        string $toOutletId,
        string $inventoryItemId,
        float $quantity,
        string $userId,
        ?string $transferId = null
    ): array {
        return DB::transaction(function () use ($fromOutletId, $toOutletId, $inventoryItemId, $quantity, $userId, $transferId) {
            $fromStock = $this->getOrCreateStock($fromOutletId, $inventoryItemId);
            $costPrice = $fromStock->avg_cost;

            // Issue from source outlet
            $outMovement = $this->issueStock(
                $fromOutletId,
                $inventoryItemId,
                $quantity,
                StockMovement::TYPE_TRANSFER_OUT,
                $userId,
                'transfer',
                $transferId,
                'Transfer out'
            );

            // Receive at destination outlet
            $inMovement = $this->receiveStock(
                $toOutletId,
                $inventoryItemId,
                $quantity,
                $costPrice,
                $userId
            );

            // Update the in movement to be transfer type
            $inMovement->update([
                'type' => StockMovement::TYPE_TRANSFER_IN,
                'reference_type' => 'transfer',
                'reference_id' => $transferId,
                'notes' => 'Transfer in',
            ]);

            return [
                'out' => $outMovement,
                'in' => $inMovement,
            ];
        });
    }

    /**
     * Get or create inventory stock record for outlet
     */
    public function getOrCreateStock(string $outletId, string $inventoryItemId): InventoryStock
    {
        return InventoryStock::firstOrCreate(
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
    }

    /**
     * Reserve stock for an order
     */
    public function reserveStock(string $outletId, string $inventoryItemId, float $quantity): bool
    {
        $stock = $this->getOrCreateStock($outletId, $inventoryItemId);

        if ($stock->getAvailableQuantity() < $quantity) {
            return false;
        }

        $stock->reserved_qty += $quantity;
        $stock->save();

        return true;
    }

    /**
     * Release reserved stock
     */
    public function releaseReservation(string $outletId, string $inventoryItemId, float $quantity): void
    {
        $stock = $this->getOrCreateStock($outletId, $inventoryItemId);
        $stock->reserved_qty = max(0, $stock->reserved_qty - $quantity);
        $stock->save();
    }

    /**
     * Deduct from batches using FIFO/FEFO
     */
    private function deductFromBatches(string $outletId, string $inventoryItemId, float $quantity): ?StockBatch
    {
        $batches = StockBatch::where('outlet_id', $outletId)
            ->where('inventory_item_id', $inventoryItemId)
            ->where('status', 'available')
            ->where('current_qty', '>', 0)
            ->orderBy('expiry_date') // FEFO - First Expiry First Out
            ->orderBy('created_at')  // Then FIFO
            ->get();

        $remaining = $quantity;
        $firstBatch = null;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            if (! $firstBatch) {
                $firstBatch = $batch;
            }

            $deduct = min($batch->current_qty, $remaining);
            $batch->current_qty -= $deduct;

            if ($batch->current_qty <= 0) {
                $batch->status = 'depleted';
            }

            $batch->save();
            $remaining -= $deduct;
        }

        return $firstBatch;
    }

    /**
     * Get stock valuation for an outlet
     */
    public function getStockValuation(string $outletId): float
    {
        return InventoryStock::where('outlet_id', $outletId)
            ->selectRaw('SUM(quantity * avg_cost) as total_value')
            ->value('total_value') ?? 0;
    }

    /**
     * Get low stock items for an outlet
     */
    public function getLowStockItems(string $outletId): \Illuminate\Database\Eloquent\Collection
    {
        return InventoryStock::where('outlet_id', $outletId)
            ->with('inventoryItem')
            ->get()
            ->filter(fn ($stock) => $stock->isLowStock());
    }

    /**
     * Get expiring batches for an outlet
     */
    public function getExpiringBatches(string $outletId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return StockBatch::where('outlet_id', $outletId)
            ->where('status', 'available')
            ->where('current_qty', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->with('inventoryItem')
            ->orderBy('expiry_date')
            ->get();
    }
}
