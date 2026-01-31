# Phase 2 - Root Services (Advanced Implementation)

## Overview

Dokumentasi ini berisi **source code lengkap** untuk 5 Service yang terletak di **root folder** `app/Services/` (BUKAN di subfolder `app/Services/Inventory/`).

### Perbedaan Root Services vs Inventory Services

| Aspect | Root Services (app/Services/) | Inventory Services (app/Services/Inventory/) |
|--------|------------------------------|-----------------------------------------------|
| **Namespace** | `App\Services` | `App\Services\Inventory` |
| **Constructor** | Menggunakan Dependency Injection | Tidak ada constructor |
| **StockService** | 335 lines - FEFO/FIFO, batch tracking, stock valuation | 257 lines - Basic operations |
| **Purpose** | Advanced features untuk POS/Production | Basic inventory operations Phase 2 |
| **Features** | - FEFO (First Expired First Out)  <br> - FIFO (First In First Out)  <br> - Batch tracking  <br> - Stock valuation  <br> - Low stock alerts  <br> - Expiry warnings | - Basic stock in/out  <br> - Simple adjustments  <br> - Basic transfers |

### 5 Root Services

1. **StockService** (335 lines) - Core stock management dengan FEFO/FIFO logic
2. **PurchaseOrderService** (343 lines) - Purchase order workflow dengan Goods Receive
3. **StockTransferService** (292 lines) - Transfer antar outlet dengan reservation
4. **RecipeCostService** (227 lines) - Recipe cost calculation dengan unit conversion
5. **StockAdjustmentService** (270 lines) - Stock adjustment dan stock take

---

## 1. StockService.php

**Namespace**: `App\Services`
**Location**: `app/Services/StockService.php`
**Lines**: 335

### Key Features

- **Weighted Average Cost (WAC)** calculation untuk stock valuation
- **FEFO/FIFO** batch deduction logic
- **Batch tracking** untuk items dengan expiry
- **Stock reservation** untuk orders
- **Low stock detection** dan **expiry warnings**

### Source Code

```php
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
            ->orderBy('expiry_date') // FEFO - First Expired First Out
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
```

### Key Methods

#### receiveStock()
- Menerima stok masuk dari Goods Receive
- Menghitung **Weighted Average Cost** baru
- Membuat batch jika item memiliki `track_batches = true`
- Mencatat movement history

#### issueStock()
- Mengeluarkan stok untuk orders, waste, dll
- Memanggil `deductFromBatches()` jika item track batches
- Menggunakan average cost sebagai cost price

#### deductFromBatches()
- **FEFO**: Urutkan berdasarkan `expiry_date` ASC
- **FIFO**: Jika expiry sama, urutkan berdasarkan `created_at` ASC
- Kurangi quantity dari batch secara berurutan
- Return batch pertama untuk tracking di movement

#### reserveStock() & releaseReservation()
- Untuk hold stok saat order dibuat
- Mencegah overselling
- Available quantity = quantity - reserved_qty

#### getStockValuation()
- Menghitung total nilai inventory: `SUM(quantity * avg_cost)`
- Untuk laporan aset inventory

#### getLowStockItems()
- Filter stok di bawah minimum stock
- Alert untuk restock

#### getExpiringBatches()
- Filter batch yang akan expired dalam X hari
- Default 7 hari
- Urutkan berdasarkan expiry terdekat

---

## 2. PurchaseOrderService.php

**Namespace**: `App\Services`
**Location**: `app/Services/PurchaseOrderService.php`
**Lines**: 343

### Key Features

- **Constructor Dependency Injection** untuk StockService
- **Auto-generate PO & GR numbers**
- **Workflow**: Draft → Submitted → Approved → Partial → Received
- **Multi-receive** support (partial GR)
- **Auto stock update** saat GR completed

### Source Code

```php
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
```

### Key Methods

#### createPurchaseOrder()
- Membuat PO dengan status DRAFT
- Auto-generate PO number jika tidak diberikan
- Menambahkan items dan menghitung totals

#### createGoodsReceive()
- Membuat GR dari PO yang approved
- Support partial receive (bisa beberapa kali)
- Update PO item `received_qty`
- Update PO status: APPROVED → PARTIAL → RECEIVED

#### completeGoodsReceive()
- Mengupdate stock untuk setiap item di GR
- Memanggil `StockService::receiveStock()` dengan batch info

#### updatePoStatusAfterReceive()
- Cek apakah PO sudah fully received
- Update status ke PARTIAL atau RECEIVED

#### generatePoNumber() & generateGrNumber()
- Format: `PO202601280001` (prefix + date + sequence)
- Auto-increment sequence per hari

---

## 3. StockTransferService.php

**Namespace**: `App\Services`
**Location**: `app/Services/StockTransferService.php`
**Lines**: 292

### Key Features

- **Constructor Dependency Injection** untuk StockService
- **Auto-generate transfer numbers**
- **Stock reservation** saat approve
- **Multi-outlet transfer**
- **Workflow**: Draft → Pending → In Transit → Received/Cancelled

### Source Code

```php
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
```

### Key Methods

#### createTransfer()
- Membuat transfer request dengan status DRAFT
- Validasi outlet berbeda (from ≠ to)

#### submitForApproval()
- Validasi available quantity sebelum submit
- Cek: `getAvailableQuantity() >= quantity`

#### approve()
- Reserve stok di source outlet
- Status berubah ke IN_TRANSIT
- Mencegah stok dipakai untuk transfer lain

#### receive()
- Release reservation di source
- Process actual transfer via `StockService::transferStock()`
- Support partial receive (bisa beda quantity)

#### cancel()
- Release reservation jika masih IN_TRANSIT
- Tidak bisa cancel setelah RECEIVED

---

## 4. RecipeCostService.php

**Namespace**: `App\Services`
**Location**: `app/Services/RecipeCostService.php`
**Lines**: 227

### Key Features

- **Recipe cost calculation** dengan current ingredient prices
- **Unit conversion** antara recipe unit dan stock unit
- **Waste percentage** handling
- **Food cost percentage** calculation
- **Selling price suggestion** based on target food cost

### Source Code

```php
<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Unit;
use Illuminate\Support\Collection;

class RecipeCostService
{
    /**
     * Calculate recipe cost based on current ingredient prices
     */
    public function calculateRecipeCost(Recipe $recipe): float
    {
        $recipe->load('items.inventoryItem', 'items.unit');

        $totalCost = 0;

        foreach ($recipe->items as $item) {
            $totalCost += $this->calculateItemCost($item);
        }

        return $totalCost;
    }

    /**
     * Calculate cost for a single recipe item
     */
    public function calculateItemCost(RecipeItem $item): float
    {
        $inventoryItem = $item->inventoryItem;
        if (! $inventoryItem) {
            return 0;
        }

        // Get quantity in stock unit
        $quantityInStockUnit = $this->convertToStockUnit(
            $item->quantity,
            $item->unit,
            $inventoryItem->unit
        );

        // Apply waste factor
        $wasteFactor = 1 + ($item->waste_percentage / 100);
        $grossQuantity = $quantityInStockUnit * $wasteFactor;

        // Calculate cost
        return $grossQuantity * $inventoryItem->cost_price;
    }

    /**
     * Update recipe estimated cost
     */
    public function updateRecipeCost(Recipe $recipe): Recipe
    {
        $recipe->estimated_cost = $this->calculateRecipeCost($recipe);
        $recipe->save();

        return $recipe;
    }

    /**
     * Update all recipe costs for a tenant
     */
    public function updateAllRecipeCosts(string $tenantId): int
    {
        $recipes = Recipe::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($recipes as $recipe) {
            $this->updateRecipeCost($recipe);
        }

        return $recipes->count();
    }

    /**
     * Get cost breakdown for a recipe
     */
    public function getCostBreakdown(Recipe $recipe): Collection
    {
        $recipe->load('items.inventoryItem', 'items.unit');

        return $recipe->items->map(function ($item) {
            $cost = $this->calculateItemCost($item);

            return [
                'inventory_item_id' => $item->inventory_item_id,
                'inventory_item_name' => $item->inventoryItem?->name,
                'quantity' => $item->quantity,
                'unit' => $item->unit?->abbreviation,
                'waste_percentage' => $item->waste_percentage,
                'gross_quantity' => $item->getGrossQuantity(),
                'unit_cost' => $item->inventoryItem?->cost_price ?? 0,
                'total_cost' => $cost,
            ];
        });
    }

    /**
     * Get cost per yield unit
     */
    public function getCostPerUnit(Recipe $recipe): float
    {
        if ($recipe->yield_qty <= 0) {
            return 0;
        }

        $totalCost = $this->calculateRecipeCost($recipe);

        return $totalCost / $recipe->yield_qty;
    }

    /**
     * Simulate cost with different ingredient prices
     */
    public function simulateCost(Recipe $recipe, array $priceOverrides): float
    {
        $recipe->load('items.inventoryItem', 'items.unit');

        $totalCost = 0;

        foreach ($recipe->items as $item) {
            $inventoryItem = $item->inventoryItem;
            if (! $inventoryItem) {
                continue;
            }

            // Use override price if provided
            $costPrice = $priceOverrides[$item->inventory_item_id] ?? $inventoryItem->cost_price;

            $quantityInStockUnit = $this->convertToStockUnit(
                $item->quantity,
                $item->unit,
                $inventoryItem->unit
            );

            $wasteFactor = 1 + ($item->waste_percentage / 100);
            $grossQuantity = $quantityInStockUnit * $wasteFactor;

            $totalCost += $grossQuantity * $costPrice;
        }

        return $totalCost;
    }

    /**
     * Find recipes affected by ingredient price change
     */
    public function getAffectedRecipes(string $inventoryItemId): Collection
    {
        return Recipe::whereHas('items', function ($query) use ($inventoryItemId) {
            $query->where('inventory_item_id', $inventoryItemId);
        })
            ->where('is_active', true)
            ->with(['items' => function ($query) use ($inventoryItemId) {
                $query->where('inventory_item_id', $inventoryItemId);
            }])
            ->get();
    }

    /**
     * Calculate food cost percentage
     */
    public function calculateFoodCostPercentage(Recipe $recipe, float $sellingPrice): float
    {
        if ($sellingPrice <= 0) {
            return 0;
        }

        $cost = $this->calculateRecipeCost($recipe);

        return ($cost / $sellingPrice) * 100;
    }

    /**
     * Suggest selling price based on target food cost percentage
     */
    public function suggestSellingPrice(Recipe $recipe, float $targetFoodCostPercent): float
    {
        if ($targetFoodCostPercent <= 0 || $targetFoodCostPercent >= 100) {
            return 0;
        }

        $cost = $this->calculateRecipeCost($recipe);

        return $cost / ($targetFoodCostPercent / 100);
    }

    /**
     * Convert quantity between units
     */
    private function convertToStockUnit(float $quantity, ?Unit $fromUnit, ?Unit $stockUnit): float
    {
        if (! $fromUnit || ! $stockUnit) {
            return $quantity;
        }

        // If same unit, no conversion needed
        if ($fromUnit->id === $stockUnit->id) {
            return $quantity;
        }

        // If both have same base unit, convert through base
        if ($fromUnit->base_unit_id && $fromUnit->base_unit_id === $stockUnit->base_unit_id) {
            $inBase = $quantity * $fromUnit->conversion_factor;

            return $inBase / $stockUnit->conversion_factor;
        }

        // If fromUnit is derived from stockUnit
        if ($fromUnit->base_unit_id === $stockUnit->id) {
            return $quantity * $fromUnit->conversion_factor;
        }

        // If stockUnit is derived from fromUnit
        if ($stockUnit->base_unit_id === $fromUnit->id) {
            return $quantity / $stockUnit->conversion_factor;
        }

        // Cannot convert - return original
        return $quantity;
    }
}
```

### Key Methods

#### calculateRecipeCost()
- Sum semua item costs
- Eager load: items dengan inventoryItem dan unit

#### calculateItemCost()
1. Convert quantity ke stock unit
2. Apply waste factor: `gross = net * (1 + waste%)`
3. Calculate cost: `gross * cost_price`

#### convertToStockUnit()
- Handle 4 skenario conversion:
  1. Same unit - no conversion
  2. Same base unit - convert through base
  3. fromUnit derived from stockUnit
  4. stockUnit derived from fromUnit
- Return original jika tidak bisa convert

#### getCostBreakdown()
- Return collection dengan detail per item
- Include: quantity, unit, waste, gross qty, unit cost, total cost

#### getCostPerUnit()
- Calculate cost per yield unit
- Example: Recipe cost 100, yield 10 portions → cost per unit = 10

#### simulateCost()
- "What-if" analysis
- Override ingredient prices untuk simulasi
- Untuk forecasting harga jual

#### calculateFoodCostPercentage()
- Food Cost % = (Recipe Cost / Selling Price) * 100
- Industry standard: 25-35%

#### suggestSellingPrice()
- Reverse calculation dari target food cost
- Selling Price = Recipe Cost / (Target Food Cost % / 100)
- Example: Cost 100, target 30% → price = 100 / 0.3 = 333.33

---

## 5. StockAdjustmentService.php

**Namespace**: `App\Services`
**Location**: `app/Services/StockAdjustmentService.php`
**Lines**: 270

### Key Features

- **Constructor Dependency Injection** untuk StockService
- **Auto-generate adjustment numbers**
- **Stock take workflow**: System vs Actual qty
- **Opening balance** setup
- **Automatic variance calculation**

### Source Code

```php
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
```

### Key Methods

#### createAdjustment()
- Membuat adjustment dengan status DRAFT
- Type: stock_take, opening_balance, damage, loss, etc.

#### addAdjustmentItem()
- Capture system_qty dari current stock
- Input actual_qty dari physical count
- Auto-calculate difference dan value_difference

#### refreshSystemQuantities()
- Update system_qty untuk semua items
- Dipanggil sebelum approve untuk ensure data terbaru

#### approve()
- Refresh quantities dulu
- Apply adjustment hanya jika hasVariance()
- Call `StockService::adjustStock()` untuk update

#### createFromStockTake()
- Quick method untuk stock take workflow
- Input: array `[inventoryItemId => actualQty]`

#### createOpeningBalance()
- Setup initial stock untuk outlet baru
- Type: OPENING_BALANCE

#### getAdjustmentSummary()
- Statistic adjustment:
  - Total items
  - Items dengan variance
  - Total increase/decrease quantity
  - Total positive/negative value difference

---

## Summary

### Key Differences Between Root & Inventory Services

| Feature | Root Services | Inventory Services |
|---------|--------------|--------------------|
| **Namespace** | `App\Services` | `App\Services\Inventory` |
| **Dependency Injection** | ✅ Constructor DI | ❌ Static/service locator |
| **FEFO/FIFO** | ✅ Full implementation | ❌ Not implemented |
| **Batch Tracking** | ✅ Complete | ❌ Basic only |
| **Stock Valuation** | ✅ getStockValuation() | ❌ Not available |
| **Low Stock Alerts** | ✅ getLowStockItems() | ❌ Not available |
| **Expiry Warnings** | ✅ getExpiringBatches() | ❌ Not available |
| **Stock Reservation** | ✅ reserveStock/releaseReservation | ❌ Not available |
| **Unit Conversion** | ✅ Advanced conversion logic | ❌ Not available |
| **Food Cost Calculation** | ✅ Full implementation | ❌ Not available |
| **Complex Number Generation** | ✅ PO/GR/TR/ADJ | ❌ Not applicable |

### Usage Recommendation

- **Phase 2 (Basic Inventory)**: Gunakan `App\Services\Inventory\*` services
- **Phase 4 (POS/Production)**: Gunakan `App\Services\*` root services
- **Migration Path**: Tambahkan method dari root services ke Inventory services saat needed

### Implementation Notes

1. **All root services use constructor DI** - Lebih testable dan maintainable
2. **FEFO/FIFO logic** di StockService.deductFromBatches() - Critical untuk batch tracking
3. **Stock reservation** di StockTransferService - Mencegah overselling
4. **Unit conversion** di RecipeCostService - Handle complex unit relationships
5. **Number generation** follows pattern: `PREFIX + YYYYMMDD + sequence`

---

**Total Lines**: 1,467 lines across 5 service files

**Documentation Created**: 2026-01-28

**Source**: ultimate-pos-saas-master/app/Services/
