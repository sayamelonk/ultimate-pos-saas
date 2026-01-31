<?php

namespace App\Services\Inventory;

use App\Models\InventoryStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function createTransfer(
        string $tenantId,
        string $sourceOutletId,
        string $destinationOutletId,
        string $userId,
        array $items,
        ?string $transferDate = null,
        ?string $notes = null
    ): StockTransfer {
        return DB::transaction(function () use ($tenantId, $sourceOutletId, $destinationOutletId, $userId, $items, $transferDate, $notes) {
            $transferNumber = $this->generateTransferNumber($tenantId);

            $transfer = StockTransfer::create([
                'tenant_id' => $tenantId,
                'from_outlet_id' => $sourceOutletId,
                'to_outlet_id' => $destinationOutletId,
                'transfer_number' => $transferNumber,
                'transfer_date' => $transferDate ?? now()->toDateString(),
                'status' => 'draft',
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            foreach ($items as $item) {
                $transfer->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $transfer;
        });
    }

    public function approveTransfer(StockTransfer $transfer, string $approvedBy): StockTransfer
    {
        if ($transfer->status !== 'draft') {
            throw new \Exception('Only draft transfers can be approved.');
        }

        return DB::transaction(function () use ($transfer, $approvedBy) {
            $transfer->load('items.inventoryItem');

            // Check if source outlet has enough stock
            foreach ($transfer->items as $item) {
                $sourceStock = InventoryStock::where('outlet_id', $transfer->from_outlet_id)
                    ->where('inventory_item_id', $item->inventory_item_id)
                    ->first();

                $availableQty = $sourceStock ? ($sourceStock->quantity - $sourceStock->reserved_qty) : 0;

                if ($availableQty < $item->quantity) {
                    throw new \Exception("Insufficient stock for item: {$item->inventoryItem->name}. Available: {$availableQty}, Requested: {$item->quantity}");
                }
            }

            // Reserve stock at source outlet
            foreach ($transfer->items as $item) {
                $sourceStock = InventoryStock::where('outlet_id', $transfer->from_outlet_id)
                    ->where('inventory_item_id', $item->inventory_item_id)
                    ->first();

                if ($sourceStock) {
                    $sourceStock->increment('reserved_qty', $item->quantity);
                }

                // Store cost price in transfer item
                $item->update(['cost_price' => $sourceStock->avg_cost ?? 0]);
            }

            $transfer->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            return $transfer->fresh();
        });
    }

    public function receiveTransfer(StockTransfer $transfer, string $receivedBy): StockTransfer
    {
        if (! in_array($transfer->status, ['approved', 'in_transit'])) {
            throw new \Exception('Only approved or in-transit transfers can be received.');
        }

        return DB::transaction(function () use ($transfer, $receivedBy) {
            $transfer->load('items.inventoryItem');

            foreach ($transfer->items as $item) {
                // Deduct from source outlet
                $sourceStock = InventoryStock::where('outlet_id', $transfer->from_outlet_id)
                    ->where('inventory_item_id', $item->inventory_item_id)
                    ->first();

                if ($sourceStock) {
                    $stockBefore = $sourceStock->quantity;
                    $sourceStock->decrement('quantity', $item->quantity);
                    $sourceStock->decrement('reserved_qty', $item->quantity);

                    // Create movement for source
                    StockMovement::create([
                        'outlet_id' => $transfer->from_outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'type' => StockMovement::TYPE_TRANSFER_OUT,
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $transfer->id,
                        'quantity' => -$item->quantity,
                        'cost_price' => $item->cost_price ?? $sourceStock->avg_cost,
                        'stock_before' => $stockBefore,
                        'stock_after' => $sourceStock->fresh()->quantity,
                        'notes' => "Transfer out: {$transfer->transfer_number}",
                        'created_by' => $receivedBy,
                    ]);
                }

                // Add to destination outlet
                $destStock = InventoryStock::firstOrCreate(
                    [
                        'outlet_id' => $transfer->to_outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_qty' => 0,
                        'avg_cost' => 0,
                        'last_cost' => 0,
                    ]
                );

                $destStockBefore = $destStock->quantity;
                $costPrice = $item->cost_price ?? ($sourceStock->avg_cost ?? 0);

                // Calculate new average cost for destination
                $currentValue = $destStock->quantity * $destStock->avg_cost;
                $newValue = $item->quantity * $costPrice;
                $newQuantity = $destStock->quantity + $item->quantity;
                $newAvgCost = $newQuantity > 0 ? ($currentValue + $newValue) / $newQuantity : $costPrice;

                $destStock->update([
                    'quantity' => $newQuantity,
                    'avg_cost' => $newAvgCost,
                    'last_cost' => $costPrice,
                    'last_received_at' => now(),
                ]);

                // Create movement for destination
                StockMovement::create([
                    'outlet_id' => $transfer->to_outlet_id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'type' => StockMovement::TYPE_TRANSFER_IN,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'quantity' => $item->quantity,
                    'cost_price' => $costPrice,
                    'stock_before' => $destStockBefore,
                    'stock_after' => $newQuantity,
                    'notes' => "Transfer in: {$transfer->transfer_number}",
                    'created_by' => $receivedBy,
                ]);
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => $receivedBy,
                'received_at' => now(),
            ]);

            return $transfer->fresh();
        });
    }

    public function cancelTransfer(StockTransfer $transfer, string $userId): StockTransfer
    {
        if (! in_array($transfer->status, ['draft', 'approved', 'in_transit'])) {
            throw new \Exception('This transfer cannot be cancelled.');
        }

        return DB::transaction(function () use ($transfer) {
            // If already approved, release reserved stock
            if (in_array($transfer->status, ['approved', 'in_transit'])) {
                $transfer->load('items');

                foreach ($transfer->items as $item) {
                    $sourceStock = InventoryStock::where('outlet_id', $transfer->from_outlet_id)
                        ->where('inventory_item_id', $item->inventory_item_id)
                        ->first();

                    if ($sourceStock) {
                        $sourceStock->decrement('reserved_qty', $item->quantity);
                    }
                }
            }

            $transfer->update(['status' => 'cancelled']);

            return $transfer->fresh();
        });
    }

    private function generateTransferNumber(string $tenantId): string
    {
        $prefix = 'TRF';
        $date = now()->format('Ymd');

        $lastTransfer = StockTransfer::where('tenant_id', $tenantId)
            ->where('transfer_number', 'like', "{$prefix}{$date}%")
            ->orderBy('transfer_number', 'desc')
            ->first();

        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$date}{$newNumber}";
    }
}
