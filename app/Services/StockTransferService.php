<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function __construct(
        private StockService $stockService
    ) {}

    /**
     * Generate transfer number
     */
    public function generateTransferNumber(string $tenantId): string
    {
        $prefix = 'TR';
        $date = now()->format('Ymd');

        $lastTransfer = StockTransfer::where('tenant_id', $tenantId)
            ->where('transfer_number', 'like', "{$prefix}{$date}%")
            ->orderBy('transfer_number', 'desc')
            ->first();

        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$prefix}{$date}{$nextNumber}";
    }

    /**
     * Create stock transfer request
     */
    public function createTransfer(array $data, array $items, string $userId): StockTransfer
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $transfer = StockTransfer::create([
                'tenant_id' => $data['tenant_id'],
                'from_outlet_id' => $data['from_outlet_id'],
                'to_outlet_id' => $data['to_outlet_id'],
                'transfer_number' => $data['transfer_number'] ?? $this->generateTransferNumber($data['tenant_id']),
                'transfer_date' => $data['transfer_date'] ?? now(),
                'status' => StockTransfer::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($items as $itemData) {
                $this->addTransferItem($transfer, $itemData);
            }

            return $transfer->fresh(['items.inventoryItem', 'fromOutlet', 'toOutlet']);
        });
    }

    /**
     * Add item to transfer
     */
    public function addTransferItem(StockTransfer $transfer, array $data): StockTransferItem
    {
        // Get current cost from source outlet stock
        $stock = $this->stockService->getOrCreateStock(
            $transfer->from_outlet_id,
            $data['inventory_item_id']
        );

        return StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'inventory_item_id' => $data['inventory_item_id'],
            'batch_id' => $data['batch_id'] ?? null,
            'quantity' => $data['quantity'],
            'unit_id' => $data['unit_id'],
            'cost_price' => $stock->avg_cost,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Update transfer item
     */
    public function updateTransferItem(StockTransferItem $item, array $data): StockTransferItem
    {
        $item->update($data);

        return $item;
    }

    /**
     * Remove transfer item
     */
    public function removeTransferItem(StockTransferItem $item): void
    {
        $item->delete();
    }

    /**
     * Submit transfer for approval
     */
    public function submitForApproval(StockTransfer $transfer): StockTransfer
    {
        if (! $transfer->isDraft()) {
            throw new \Exception('Only draft transfers can be submitted');
        }

        if ($transfer->items->isEmpty()) {
            throw new \Exception('Cannot submit empty transfer');
        }

        // Validate stock availability
        foreach ($transfer->items as $item) {
            $stock = $this->stockService->getOrCreateStock(
                $transfer->from_outlet_id,
                $item->inventory_item_id
            );

            if ($stock->getAvailableQuantity() < $item->quantity) {
                throw new \Exception(
                    "Insufficient stock for {$item->inventoryItem->name}. Available: {$stock->getAvailableQuantity()}"
                );
            }
        }

        $transfer->status = StockTransfer::STATUS_PENDING;
        $transfer->save();

        return $transfer;
    }

    /**
     * Approve and dispatch transfer
     */
    public function approve(StockTransfer $transfer, string $approverId): StockTransfer
    {
        if (! $transfer->canBeApproved()) {
            throw new \Exception('Transfer cannot be approved in current status');
        }

        return DB::transaction(function () use ($transfer, $approverId) {
            // Reserve stock at source outlet
            foreach ($transfer->items as $item) {
                $reserved = $this->stockService->reserveStock(
                    $transfer->from_outlet_id,
                    $item->inventory_item_id,
                    $item->quantity
                );

                if (! $reserved) {
                    throw new \Exception(
                        "Cannot reserve stock for {$item->inventoryItem->name}"
                    );
                }
            }

            $transfer->status = StockTransfer::STATUS_IN_TRANSIT;
            $transfer->approved_by = $approverId;
            $transfer->approved_at = now();
            $transfer->save();

            return $transfer;
        });
    }

    /**
     * Receive transfer at destination
     */
    public function receive(StockTransfer $transfer, array $receivedQuantities, string $receiverId): StockTransfer
    {
        if (! $transfer->canBeReceived()) {
            throw new \Exception('Transfer cannot be received in current status');
        }

        return DB::transaction(function () use ($transfer, $receivedQuantities, $receiverId) {
            foreach ($transfer->items as $item) {
                $receivedQty = $receivedQuantities[$item->id] ?? $item->quantity;
                $item->received_qty = $receivedQty;
                $item->save();

                // Release reservation at source
                $this->stockService->releaseReservation(
                    $transfer->from_outlet_id,
                    $item->inventory_item_id,
                    $item->quantity
                );

                // Process actual transfer
                $this->stockService->transferStock(
                    $transfer->from_outlet_id,
                    $transfer->to_outlet_id,
                    $item->inventory_item_id,
                    $receivedQty,
                    $receiverId,
                    $transfer->id
                );
            }

            $transfer->status = StockTransfer::STATUS_RECEIVED;
            $transfer->received_by = $receiverId;
            $transfer->received_at = now();
            $transfer->save();

            return $transfer;
        });
    }

    /**
     * Cancel transfer
     */
    public function cancel(StockTransfer $transfer): StockTransfer
    {
        if ($transfer->isReceived()) {
            throw new \Exception('Received transfers cannot be cancelled');
        }

        return DB::transaction(function () use ($transfer) {
            // Release any reservations if in transit
            if ($transfer->isInTransit()) {
                foreach ($transfer->items as $item) {
                    $this->stockService->releaseReservation(
                        $transfer->from_outlet_id,
                        $item->inventory_item_id,
                        $item->quantity
                    );
                }
            }

            $transfer->status = StockTransfer::STATUS_CANCELLED;
            $transfer->save();

            return $transfer;
        });
    }

    /**
     * Get pending incoming transfers for an outlet
     */
    public function getPendingIncoming(string $outletId): \Illuminate\Database\Eloquent\Collection
    {
        return StockTransfer::where('to_outlet_id', $outletId)
            ->where('status', StockTransfer::STATUS_IN_TRANSIT)
            ->with(['items.inventoryItem', 'fromOutlet'])
            ->orderBy('transfer_date')
            ->get();
    }

    /**
     * Get pending outgoing transfers for an outlet
     */
    public function getPendingOutgoing(string $outletId): \Illuminate\Database\Eloquent\Collection
    {
        return StockTransfer::where('from_outlet_id', $outletId)
            ->whereIn('status', [
                StockTransfer::STATUS_PENDING,
                StockTransfer::STATUS_IN_TRANSIT,
            ])
            ->with(['items.inventoryItem', 'toOutlet'])
            ->orderBy('transfer_date')
            ->get();
    }

    /**
     * Get transfer history between outlets
     */
    public function getTransferHistory(
        string $fromOutletId,
        string $toOutletId,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = StockTransfer::where('from_outlet_id', $fromOutletId)
            ->where('to_outlet_id', $toOutletId)
            ->where('status', StockTransfer::STATUS_RECEIVED);

        if ($startDate) {
            $query->where('transfer_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transfer_date', '<=', $endDate);
        }

        return $query->with(['items.inventoryItem'])
            ->orderBy('transfer_date', 'desc')
            ->get();
    }
}
