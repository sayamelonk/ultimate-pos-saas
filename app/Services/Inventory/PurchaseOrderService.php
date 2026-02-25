<?php

namespace App\Services\Inventory;

use App\Models\BatchSetting;
use App\Models\GoodsReceive;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use App\Models\StockBatchMovement;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function createPurchaseOrder(
        string $tenantId,
        string $supplierId,
        string $outletId,
        string $userId,
        array $items,
        ?string $expectedDate = null,
        ?string $notes = null
    ): PurchaseOrder {
        return DB::transaction(function () use ($tenantId, $supplierId, $outletId, $userId, $items, $expectedDate, $notes) {
            $poNumber = $this->generatePoNumber($tenantId);

            $subtotal = 0;
            $itemsData = [];

            foreach ($items as $item) {
                $inventoryItem = InventoryItem::find($item['inventory_item_id']);
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'inventory_item_id' => $item['inventory_item_id'],
                    'unit_id' => $inventoryItem->purchase_unit_id ?? $inventoryItem->unit_id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $lineTotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $purchaseOrder = PurchaseOrder::create([
                'tenant_id' => $tenantId,
                'po_number' => $poNumber,
                'supplier_id' => $supplierId,
                'outlet_id' => $outletId,
                'status' => 'draft',
                'order_date' => now()->toDateString(),
                'expected_date' => $expectedDate,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total' => $subtotal,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            foreach ($itemsData as $itemData) {
                $purchaseOrder->items()->create($itemData);
            }

            return $purchaseOrder;
        });
    }

    public function approvePurchaseOrder(PurchaseOrder $purchaseOrder, string $approvedBy): PurchaseOrder
    {
        if ($purchaseOrder->status !== 'draft') {
            throw new \Exception('Only draft purchase orders can be approved.');
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $purchaseOrder->fresh();
    }

    public function cancelPurchaseOrder(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        if (! in_array($purchaseOrder->status, ['draft', 'approved', 'sent'])) {
            throw new \Exception('This purchase order cannot be cancelled.');
        }

        // Check if any goods have been received
        if ($purchaseOrder->goodsReceives()->where('status', 'completed')->exists()) {
            throw new \Exception('Cannot cancel purchase order with completed goods receives.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return $purchaseOrder->fresh();
    }

    public function updateReceivedQuantities(PurchaseOrder $purchaseOrder): void
    {
        // Update received quantities on PO items based on completed goods receives
        foreach ($purchaseOrder->items as $poItem) {
            $receivedQty = $purchaseOrder->goodsReceives()
                ->where('status', 'completed')
                ->join('goods_receive_items', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
                ->where('goods_receive_items.inventory_item_id', $poItem->inventory_item_id)
                ->sum('goods_receive_items.quantity');

            $poItem->update(['received_quantity' => $receivedQty]);
        }

        // Update PO status based on received quantities
        $this->updatePoStatus($purchaseOrder);
    }

    public function updatePoStatus(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->load('items');

        $totalOrdered = $purchaseOrder->items->sum('quantity');
        $totalReceived = $purchaseOrder->items->sum('received_quantity');

        if ($totalReceived == 0) {
            // No change if nothing received yet
            return;
        }

        if ($totalReceived >= $totalOrdered) {
            $purchaseOrder->update(['status' => 'received']);
        } else {
            $purchaseOrder->update(['status' => 'partially_received']);
        }
    }

    private function generatePoNumber(string $tenantId): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');

        $lastPo = PurchaseOrder::where('tenant_id', $tenantId)
            ->where('po_number', 'like', "{$prefix}{$date}%")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = (int) substr($lastPo->po_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$date}{$newNumber}";
    }

    public function createGoodsReceive(
        PurchaseOrder $purchaseOrder,
        string $userId,
        array $items,
        ?string $invoiceNumber = null,
        ?string $receiveDate = null,
        ?string $notes = null
    ): GoodsReceive {
        return DB::transaction(function () use ($purchaseOrder, $userId, $items, $invoiceNumber, $receiveDate, $notes) {
            $grNumber = $this->generateGrNumber($purchaseOrder->tenant_id);

            $goodsReceive = GoodsReceive::create([
                'tenant_id' => $purchaseOrder->tenant_id,
                'outlet_id' => $purchaseOrder->outlet_id,
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'gr_number' => $grNumber,
                'receive_date' => $receiveDate ?? now()->toDateString(),
                'status' => 'draft',
                'invoice_number' => $invoiceNumber,
                'notes' => $notes,
                'received_by' => $userId,
            ]);

            $subtotal = 0;

            foreach ($items as $item) {
                $poItem = $purchaseOrder->items()->findOrFail($item['purchase_order_item_id']);

                $quantity = $item['quantity_received'] ?? 0;
                $unitPrice = $poItem->unit_price;
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                $goodsReceive->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'inventory_item_id' => $poItem->inventory_item_id,
                    'unit_id' => $poItem->unit_id,
                    'quantity' => $quantity,
                    'stock_qty' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'unit_conversion' => 1,
                    'discount_percent' => 0,
                    'discount_amount' => 0,
                    'tax_percent' => 0,
                    'tax_amount' => 0,
                ]);
            }

            $goodsReceive->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            return $goodsReceive;
        });
    }

    public function completeGoodsReceive(GoodsReceive $goodsReceive, string $userId): GoodsReceive
    {
        if ($goodsReceive->status !== 'draft') {
            throw new \Exception('Only draft goods receives can be completed.');
        }

        return DB::transaction(function () use ($goodsReceive, $userId) {
            $goodsReceive->load(['items.inventoryItem', 'outlet']);

            foreach ($goodsReceive->items as $grItem) {
                // Update or create inventory stock
                $stock = InventoryStock::firstOrCreate(
                    [
                        'outlet_id' => $goodsReceive->outlet_id,
                        'inventory_item_id' => $grItem->inventory_item_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_qty' => 0,
                        'avg_cost' => 0,
                        'last_cost' => 0,
                    ]
                );

                $stockBefore = $stock->quantity;
                $newQuantity = $stockBefore + $grItem->stock_qty;

                // Calculate new average cost
                $currentValue = $stock->quantity * $stock->avg_cost;
                $newValue = $grItem->stock_qty * $grItem->unit_price;
                $newAvgCost = $newQuantity > 0 ? ($currentValue + $newValue) / $newQuantity : $grItem->unit_price;

                $stock->update([
                    'quantity' => $newQuantity,
                    'avg_cost' => $newAvgCost,
                    'last_cost' => $grItem->unit_price,
                    'last_received_at' => now(),
                ]);

                // Create stock batch if batch number or expiry date provided
                if ($grItem->batch_number || $grItem->expiry_date) {
                    $settings = BatchSetting::getForTenant($goodsReceive->tenant_id);

                    // Generate batch number if not provided
                    $batchNumber = $grItem->batch_number;
                    if (! $batchNumber && $settings->auto_generate_batch) {
                        $batchNumber = StockBatch::generateBatchNumber(
                            $goodsReceive->outlet_id,
                            $settings->batch_prefix
                        );
                    }

                    $stockBatch = StockBatch::create([
                        'tenant_id' => $goodsReceive->tenant_id,
                        'outlet_id' => $goodsReceive->outlet_id,
                        'inventory_item_id' => $grItem->inventory_item_id,
                        'goods_receive_item_id' => $grItem->id,
                        'batch_number' => $batchNumber,
                        'expiry_date' => $grItem->expiry_date,
                        'initial_quantity' => $grItem->stock_qty,
                        'current_quantity' => $grItem->stock_qty,
                        'reserved_quantity' => 0,
                        'unit_cost' => $grItem->unit_price,
                        'status' => StockBatch::STATUS_ACTIVE,
                    ]);

                    // Create batch movement record
                    StockBatchMovement::create([
                        'tenant_id' => $goodsReceive->tenant_id,
                        'outlet_id' => $goodsReceive->outlet_id,
                        'stock_batch_id' => $stockBatch->id,
                        'inventory_item_id' => $grItem->inventory_item_id,
                        'type' => StockBatchMovement::TYPE_RECEIVE,
                        'quantity' => $grItem->stock_qty,
                        'balance_before' => 0,
                        'balance_after' => $grItem->stock_qty,
                        'reference_type' => GoodsReceive::class,
                        'reference_number' => $goodsReceive->gr_number,
                        'notes' => 'Goods receive: '.$goodsReceive->gr_number,
                        'user_id' => $userId,
                    ]);
                }

                // Create stock movement
                StockMovement::create([
                    'outlet_id' => $goodsReceive->outlet_id,
                    'inventory_item_id' => $grItem->inventory_item_id,
                    'type' => StockMovement::TYPE_IN,
                    'reference_type' => GoodsReceive::class,
                    'reference_id' => $goodsReceive->id,
                    'quantity' => $grItem->stock_qty,
                    'cost_price' => $grItem->unit_price,
                    'stock_before' => $stockBefore,
                    'stock_after' => $newQuantity,
                    'notes' => "Goods receive: {$goodsReceive->gr_number}",
                    'created_by' => $userId,
                ]);
            }

            // Update goods receive status
            $goodsReceive->update([
                'status' => 'completed',
            ]);

            // Update PO received quantities
            $this->updateReceivedQuantities($goodsReceive->purchaseOrder);

            return $goodsReceive->fresh();
        });
    }

    private function generateGrNumber(string $tenantId): string
    {
        $prefix = 'GR';
        $date = now()->format('Ymd');

        $lastGr = GoodsReceive::where('tenant_id', $tenantId)
            ->where('gr_number', 'like', "{$prefix}{$date}%")
            ->orderBy('gr_number', 'desc')
            ->first();

        if ($lastGr) {
            $lastNumber = (int) substr($lastGr->gr_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$date}{$newNumber}";
    }
}
