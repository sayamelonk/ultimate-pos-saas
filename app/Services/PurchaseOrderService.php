<?php

namespace App\Services;

use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        private StockService $stockService
    ) {}

    /**
     * Generate next PO number
     */
    public function generatePoNumber(string $tenantId): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');

        $lastPo = PurchaseOrder::where('tenant_id', $tenantId)
            ->where('po_number', 'like', "{$prefix}{$date}%")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = (int) substr($lastPo->po_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$prefix}{$date}{$nextNumber}";
    }

    /**
     * Create purchase order
     */
    public function createPurchaseOrder(array $data, array $items, string $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $po = PurchaseOrder::create([
                'tenant_id' => $data['tenant_id'],
                'outlet_id' => $data['outlet_id'],
                'supplier_id' => $data['supplier_id'],
                'po_number' => $data['po_number'] ?? $this->generatePoNumber($data['tenant_id']),
                'order_date' => $data['order_date'] ?? now(),
                'expected_date' => $data['expected_date'] ?? null,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($items as $itemData) {
                $this->addPurchaseOrderItem($po, $itemData);
            }

            $this->recalculateTotals($po);

            return $po->fresh(['items', 'supplier', 'outlet']);
        });
    }

    /**
     * Add item to purchase order
     */
    public function addPurchaseOrderItem(PurchaseOrder $po, array $data): PurchaseOrderItem
    {
        $item = new PurchaseOrderItem([
            'purchase_order_id' => $po->id,
            'inventory_item_id' => $data['inventory_item_id'],
            'unit_id' => $data['unit_id'],
            'unit_conversion' => $data['unit_conversion'] ?? 1,
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'discount_percent' => $data['discount_percent'] ?? 0,
            'tax_percent' => $data['tax_percent'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ]);

        $item->calculateTotals();
        $item->save();

        return $item;
    }

    /**
     * Update purchase order item
     */
    public function updatePurchaseOrderItem(PurchaseOrderItem $item, array $data): PurchaseOrderItem
    {
        $item->fill($data);
        $item->calculateTotals();
        $item->save();

        $this->recalculateTotals($item->purchaseOrder);

        return $item;
    }

    /**
     * Remove item from purchase order
     */
    public function removePurchaseOrderItem(PurchaseOrderItem $item): void
    {
        $po = $item->purchaseOrder;
        $item->delete();
        $this->recalculateTotals($po);
    }

    /**
     * Recalculate PO totals
     */
    public function recalculateTotals(PurchaseOrder $po): void
    {
        $po->refresh();
        $po->calculateTotals();
        $po->save();
    }

    /**
     * Submit PO for approval
     */
    public function submitForApproval(PurchaseOrder $po): PurchaseOrder
    {
        if (! $po->isDraft()) {
            throw new \Exception('Only draft POs can be submitted for approval');
        }

        if ($po->items->isEmpty()) {
            throw new \Exception('Cannot submit empty PO');
        }

        $po->status = PurchaseOrder::STATUS_SUBMITTED;
        $po->save();

        return $po;
    }

    /**
     * Approve purchase order
     */
    public function approve(PurchaseOrder $po, string $approverId): PurchaseOrder
    {
        if (! $po->canBeApproved()) {
            throw new \Exception('PO cannot be approved in current status');
        }

        $po->status = PurchaseOrder::STATUS_APPROVED;
        $po->approved_by = $approverId;
        $po->approved_at = now();
        $po->save();

        return $po;
    }

    /**
     * Cancel purchase order
     */
    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if (! $po->isEditable()) {
            throw new \Exception('PO cannot be cancelled in current status');
        }

        $po->status = PurchaseOrder::STATUS_CANCELLED;
        $po->save();

        return $po;
    }

    /**
     * Create goods receive from PO
     */
    public function createGoodsReceive(
        PurchaseOrder $po,
        array $receivedItems,
        string $userId,
        ?string $invoiceNumber = null,
        ?\DateTimeInterface $invoiceDate = null
    ): GoodsReceive {
        if (! $po->canBeReceived()) {
            throw new \Exception('PO cannot be received in current status');
        }

        return DB::transaction(function () use ($po, $receivedItems, $userId, $invoiceNumber, $invoiceDate) {
            // Create GR
            $gr = GoodsReceive::create([
                'tenant_id' => $po->tenant_id,
                'outlet_id' => $po->outlet_id,
                'purchase_order_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'gr_number' => $this->generateGrNumber($po->tenant_id),
                'receive_date' => now(),
                'status' => GoodsReceive::STATUS_DRAFT,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'received_by' => $userId,
            ]);

            foreach ($receivedItems as $itemData) {
                $poItem = PurchaseOrderItem::findOrFail($itemData['purchase_order_item_id']);

                $grItem = new GoodsReceiveItem([
                    'goods_receive_id' => $gr->id,
                    'purchase_order_item_id' => $poItem->id,
                    'inventory_item_id' => $poItem->inventory_item_id,
                    'unit_id' => $poItem->unit_id,
                    'unit_conversion' => $poItem->unit_conversion,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'] ?? $poItem->unit_price,
                    'discount_percent' => $itemData['discount_percent'] ?? $poItem->discount_percent,
                    'tax_percent' => $itemData['tax_percent'] ?? $poItem->tax_percent,
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'production_date' => $itemData['production_date'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $grItem->calculateTotals();
                $grItem->save();

                // Update PO item received qty
                $poItem->received_qty += $itemData['quantity'];
                $poItem->save();
            }

            $gr->calculateTotals();
            $gr->save();

            // Update PO status
            $this->updatePoStatusAfterReceive($po);

            return $gr->fresh(['items', 'supplier']);
        });
    }

    /**
     * Complete goods receive and update stock
     */
    public function completeGoodsReceive(GoodsReceive $gr, string $userId): GoodsReceive
    {
        if (! $gr->isDraft()) {
            throw new \Exception('Only draft GR can be completed');
        }

        return DB::transaction(function () use ($gr, $userId) {
            foreach ($gr->items as $item) {
                $this->stockService->receiveStock(
                    $gr->outlet_id,
                    $item->inventory_item_id,
                    $item->stock_qty,
                    $item->getCostPerStockUnit(),
                    $userId,
                    $item,
                    $item->batch_number,
                    $item->expiry_date
                );
            }

            $gr->status = GoodsReceive::STATUS_COMPLETED;
            $gr->save();

            return $gr;
        });
    }

    /**
     * Generate GR number
     */
    public function generateGrNumber(string $tenantId): string
    {
        $prefix = 'GR';
        $date = now()->format('Ymd');

        $lastGr = GoodsReceive::where('tenant_id', $tenantId)
            ->where('gr_number', 'like', "{$prefix}{$date}%")
            ->orderBy('gr_number', 'desc')
            ->first();

        if ($lastGr) {
            $lastNumber = (int) substr($lastGr->gr_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$prefix}{$date}{$nextNumber}";
    }

    /**
     * Update PO status after receiving
     */
    private function updatePoStatusAfterReceive(PurchaseOrder $po): void
    {
        $po->refresh();

        if ($po->isFullyReceived()) {
            $po->status = PurchaseOrder::STATUS_RECEIVED;
        } else {
            $po->status = PurchaseOrder::STATUS_PARTIAL;
        }

        $po->save();
    }

    /**
     * Get pending POs for an outlet
     */
    public function getPendingPurchaseOrders(string $outletId): \Illuminate\Database\Eloquent\Collection
    {
        return PurchaseOrder::where('outlet_id', $outletId)
            ->whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_PARTIAL,
            ])
            ->with(['supplier', 'items.inventoryItem'])
            ->orderBy('expected_date')
            ->get();
    }

    /**
     * Get overdue POs
     */
    public function getOverduePurchaseOrders(string $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return PurchaseOrder::where('tenant_id', $tenantId)
            ->whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_PARTIAL,
            ])
            ->whereNotNull('expected_date')
            ->where('expected_date', '<', now())
            ->with(['supplier', 'outlet'])
            ->orderBy('expected_date')
            ->get();
    }
}
