# Phase 2: Models - Stock & Movement (Part 5)

## Overview

Dokumentasi ini mencakup models untuk **Stock Tracking**: StockBatch dan StockMovement. Ini adalah models yang meng-handle tracking stock per batch (FEFO - First Expired First Out) dan audit trail semua pergerakan stock.

---

## 1. StockBatch Model ⭐

**File:** `app/Models/StockBatch.php`

Tracking stock per batch dengan nomor batch, tanggal produksi, dan tanggal kadaluarsa untuk implementasi FEFO (First Expired First Out).

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    use HasFactory, HasUuid;

    const STATUS_AVAILABLE = 'available';
    const STATUS_DEPLETED = 'depleted';
    const STATUS_EXPIRED = 'expired';
    const STATUS_DISPOSED = 'disposed';

    protected $fillable = [
        'outlet_id',
        'inventory_item_id',
        'batch_number',
        'production_date',
        'expiry_date',
        'initial_qty',
        'current_qty',
        'cost_price',
        'goods_receive_item_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'expiry_date' => 'date',
            'initial_qty' => 'decimal:4',
            'current_qty' => 'decimal:4',
            'cost_price' => 'decimal:2',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function goodsReceiveItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiveItem::class);
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->expiry_date &&
            $this->expiry_date->lessThanOrEqualTo(now()->addDays($days));
    }

    public function isExpired(): bool
    {
        return $this->expiry_date &&
            $this->expiry_date->isPast();
    }

    public function getRemainingDays(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }
}
```

### Status Constants

| Constant | Value | Description |
|-----------|-------|-------------|
| `STATUS_AVAILABLE` | `'available'` | Batch tersedia, ada stock |
| `STATUS_DEPLETED` | `'depleted'` | Batch sudah habis (current_qty = 0) |
| `STATUS_EXPIRED` | `'expired'` | Batch kadaluarsa |
| `STATUS_DISPOSED` | `'disposed'` | Batch dibuang/dispose |

### Key Fields

| Field | Description | Example |
|-------|-------------|---------|
| `batch_number` | Nomor unik batch | BATCH-2025-01-20-A |
| `production_date` | Tanggal produksi | 2025-01-20 |
| `expiry_date` | Tanggal kadaluarsa | 2026-01-20 |
| `initial_qty` | Quantity awal saat diterima | 100.0000 |
| `current_qty` | Quantity saat ini | 75.5000 |
| `cost_price` | Cost price per unit | 15,000 |
| `goods_receive_item_id` | Link ke GoodsReceiveItem | null |

### Helper Methods

#### isExpiringSoon(int $days = 7): bool
Cek apakah batch akan kadaluarsa dalam X hari ke depan.

```php
// Cek batch yang akan kadaluarsa dalam 7 hari
if ($batch->isExpiringSoon(7)) {
    // Tampilkan warning
    echo "Batch {$batch->batch_number} akan kadaluarsa dalam 7 hari!";
}

// Cek batch yang akan kadaluarsa dalam 30 hari
if ($batch->isExpiringSoon(30)) {
    // Buat promo untuk barang ini
}
```

#### isExpired(): bool
Cek apakah batch sudah kadaluarsa.

```php
if ($batch->isExpired()) {
    $batch->update(['status' => StockBatch::STATUS_EXPIRED]);
}
```

#### getRemainingDays(): ?int
Hitung sisa hari sampai kadaluarsa.

```php
$days = $batch->getRemainingDays();
if ($days !== null) {
    if ($days > 0) {
        echo "Masih {$days} hari lagi";
    } else {
        echo "Sudah kadaluarsa " . abs($days) . " hari yang lalu";
    }
}

// Returns:
// - 365 (masih 365 hari)
// - 5 (masih 5 hari)
// - 0 (hari ini kadaluarsa)
// - -3 (kadaluarsa 3 hari yang lalu)
// - null (tidak ada expiry_date)
```

### Usage Examples

```php
// Create stock batch dari goods receive
StockBatch::create([
    'outlet_id' => $outlet->id,
    'inventory_item_id' => $susu->id,
    'batch_number' => 'SUSU-2025-01-20-A',
    'production_date' => '2025-01-20',
    'expiry_date' => '2026-01-20',    // 1 tahun
    'initial_qty' => 240.0000,         // 240 pcs
    'current_qty' => 240.0000,         // 240 pcs
    'cost_price' => 20000,             // 20k per pcs
    'goods_receive_item_id' => $grItem->id,
    'status' => StockBatch::STATUS_AVAILABLE,
]);

// Deduct stock (misal: terjual 10 pcs)
$batch->decrement('current_qty', 10.0000);

// Cek jika batch sudah habis
if ($batch->current_qty <= 0) {
    $batch->update(['status' => StockBatch::STATUS_DEPLETED]);
}

// Cek batch yang akan kadaluarsa
$expiringBatches = StockBatch::where('status', StockBatch::STATUS_AVAILABLE)
    ->whereHas('inventoryItem', function ($q) {
        $q->where('track_expiry', true);
    })
    ->whereDate('expiry_date', '<=', now()->addDays(7))
    ->orderBy('expiry_date')
    ->get();

foreach ($expiringBatches as $batch) {
    echo $batch->batch_number . ' - ' . $batch->getRemainingDays() . ' hari lagi';
}
```

---

## 2. StockMovement Model ⭐

**File:** `app/Models/StockMovement.php`

Audit trail untuk semua pergerakan stock. Setiap kali stock berubah, catat di sini.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory, HasUuid;

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_WASTE = 'waste';

    protected $fillable = [
        'outlet_id',
        'inventory_item_id',
        'batch_id',
        'type',
        'reference_type',
        'reference_id',
        'quantity',
        'cost_price',
        'stock_before',
        'stock_after',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'cost_price' => 'decimal:2',
            'stock_before' => 'decimal:4',
            'stock_after' => 'decimal:4',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isIn(): bool
    {
        return $this->type === self::TYPE_IN || $this->type === self::TYPE_TRANSFER_IN;
    }

    public function isOut(): bool
    {
        return $this->type === self::TYPE_OUT ||
            $this->type === self::TYPE_TRANSFER_OUT ||
            $this->type === self::TYPE_WASTE;
    }

    public function getValue(): float
    {
        return abs($this->quantity) * $this->cost_price;
    }
}
```

### Movement Type Constants

| Constant | Value | Description | Quantity Sign |
|-----------|-------|-------------|---------------|
| `TYPE_IN` | `'in'` | Stock masuk (dari PO, langsung beli) | Positive (+) |
| `TYPE_OUT` | `'out'` | Stock keluar (penjualan, pemakaian) | Negative (-) |
| `TYPE_ADJUSTMENT` | `'adjustment'` | Stock adjustment (stock opname) | +/- |
| `TYPE_TRANSFER_IN` | `'transfer_in'` | Terima transfer dari outlet lain | Positive (+) |
| `TYPE_TRANSFER_OUT` | `'transfer_out'` | Kirim transfer ke outlet lain | Negative (-) |
| `TYPE_WASTE` | `'waste'` | Stock dibuang/waste | Negative (-) |

### Key Fields

| Field | Description | Example |
|-------|-------------|---------|
| `type` | Tipe movement | `in`, `out`, `adjustment`, dll |
| `reference_type` | Model yang menyebabkan movement | `GoodsReceive::class` |
| `reference_id` | ID dari reference | ID dari GoodsReceive |
| `quantity` | Quantity yang berubah (bisa negatif) | +100.0000 atau -10.0000 |
| `cost_price` | Cost price saat movement | 15,000 |
| `stock_before` | Stock sebelum movement | 50.0000 |
| `stock_after` | Stock sesudah movement | 150.0000 |
| `notes` | Keterangan tambahan | "Purchase #PO-001" |

### Reference Types

| Movement Type | Reference Type | Reference ID | Contoh |
|--------------|----------------|--------------|--------|
| `in` | `GoodsReceive::class` | `goods_receives.id` | Terima barang dari supplier |
| `out` | `Order::class` | `orders.id` | Penjualan ke customer |
| `adjustment` | `StockAdjustment::class` | `stock_adjustments.id` | Stock opname |
| `transfer_out` | `StockTransfer::class` | `stock_transfers.id` | Kirim ke outlet lain |
| `transfer_in` | `StockTransfer::class` | `stock_transfers.id` | Terima dari outlet lain |
| `waste` | `WasteLog::class` | `waste_logs.id` | Barang kadaluarsa/rusak |

### Helper Methods

#### isIn(): bool
Cek apakah movement adalah stock masuk.

```php
if ($movement->isIn()) {
    echo "Stock bertambah: " . $movement->quantity;
}
```

#### isOut(): bool
Cek apakah movement adalah stock keluar.

```php
if ($movement->isOut()) {
    echo "Stock berkurang: " . abs($movement->quantity);
}
```

#### getValue(): float
Hitung nilai movement (dalam rupiah).

```php
// Quantity: 100 pcs, Cost Price: 15,000
$value = $movement->getValue(); // 1,500,000
```

### Usage Examples

```php
// 1. Stock IN dari Goods Receive
StockMovement::create([
    'outlet_id' => $outlet->id,
    'inventory_item_id' => $item->id,
    'batch_id' => $batch->id,
    'type' => StockMovement::TYPE_IN,
    'reference_type' => GoodsReceive::class,
    'reference_id' => $gr->id,
    'quantity' => 100.0000,        // +100
    'cost_price' => 15000,
    'stock_before' => 50.0000,
    'stock_after' => 150.0000,
    'notes' => 'Purchase #PO-2025-001',
    'created_by' => auth()->id(),
]);

// 2. Stock OUT untuk penjualan
StockMovement::create([
    'outlet_id' => $outlet->id,
    'inventory_item_id' => $item->id,
    'batch_id' => $batch->id,
    'type' => StockMovement::TYPE_OUT,
    'reference_type' => Order::class,
    'reference_id' => $order->id,
    'quantity' => -10.0000,        // -10
    'cost_price' => 15000,
    'stock_before' => 150.0000,
    'stock_after' => 140.0000,
    'notes' => 'Order #ORD-001',
    'created_by' => auth()->id(),
]);

// 3. Stock ADJUSTMENT (stock opname)
StockMovement::create([
    'outlet_id' => $outlet->id,
    'inventory_item_id' => $item->id,
    'type' => StockMovement::TYPE_ADJUSTMENT,
    'reference_type' => StockAdjustment::class,
    'reference_id' => $adjustment->id,
    'quantity' => -5.0000,         // Selisih -5 (stock lebih sedikit)
    'cost_price' => 15000,
    'stock_before' => 140.0000,
    'stock_after' => 135.0000,
    'notes' => 'Stock Opname Jan 2025 - Selisih',
    'created_by' => auth()->id(),
]);

// 4. Stock WASTE (barang kadaluarsa)
StockMovement::create([
    'outlet_id' => $outlet->id,
    'inventory_item_id' => $item->id,
    'batch_id' => $batch->id,
    'type' => StockMovement::TYPE_WASTE,
    'reference_type' => WasteLog::class,
    'reference_id' => $waste->id,
    'quantity' => -20.0000,        // -20 (dibuang)
    'cost_price' => 15000,
    'stock_before' => 135.0000,
    'stock_after' => 115.0000,
    'notes' => 'Expired batch BATCH-2025-01',
    'created_by' => auth()->id(),
]);

// 5. TRANSFER OUT ke outlet lain
StockMovement::create([
    'outlet_id' => $outletA->id,
    'inventory_item_id' => $item->id,
    'batch_id' => $batch->id,
    'type' => StockMovement::TYPE_TRANSFER_OUT,
    'reference_type' => StockTransfer::class,
    'reference_id' => $transfer->id,
    'quantity' => -30.0000,        // -30 (dikirim)
    'cost_price' => 15000,
    'stock_before' => 115.0000,
    'stock_after' => 85.0000,
    'notes' => 'Transfer to Outlet B',
    'created_by' => auth()->id(),
]);

// 6. TRANSFER IN dari outlet lain
StockMovement::create([
    'outlet_id' => $outletB->id,
    'inventory_item_id' => $item->id,
    'batch_id' => $batch->id,
    'type' => StockMovement::TYPE_TRANSFER_IN,
    'reference_type' => StockTransfer::class,
    'reference_id' => $transfer->id,
    'quantity' => 30.0000,         // +30 (diterima)
    'cost_price' => 15000,
    'stock_before' => 50.0000,
    'stock_after' => 80.0000,
    'notes' => 'Transfer from Outlet A',
    'created_by' => auth()->id(),
]);
```

---

## FEFO (First Expired First Out) Implementation

### What is FEFO?

FEFO adalah metode pengeluaran stock di mana batch dengan **tanggal kadaluarsa terdekat** digunakan terlebih dahulu. Ini penting untuk:
- Mengurangi waste akibat kadaluarsa
- Menjaga kualitas produk
- Memenuhi regulasi keamanan pangan

### FEFO Algorithm

```php
// Function untuk mendapatkan batch sesuai FEFO
function getNextBatch($outletId, $inventoryItemId, $quantityNeeded) {
    // 1. Get available batches, ordered by expiry date (asc)
    $batches = StockBatch::where('outlet_id', $outletId)
        ->where('inventory_item_id', $inventoryItemId)
        ->where('status', StockBatch::STATUS_AVAILABLE)
        ->where('current_qty', '>', 0)
        ->orderBy('expiry_date', 'asc')  // FEFO: expired first
        ->get();

    $remainingQty = $quantityNeeded;
    $batchesToUse = [];

    // 2. Take from batches in order
    foreach ($batches as $batch) {
        if ($remainingQty <= 0) {
            break;
        }

        // Skip expired batches
        if ($batch->isExpired()) {
            continue;
        }

        // Take quantity from this batch
        if ($batch->current_qty >= $remainingQty) {
            // Batch cukup untuk fulfill remaining
            $batchesToUse[] = [
                'batch' => $batch,
                'qty' => $remainingQty,
            ];
            $remainingQty = 0;
        } else {
            // Batch tidak cukup, ambil semua
            $batchesToUse[] = [
                'batch' => $batch,
                'qty' => $batch->current_qty,
            ];
            $remainingQty -= $batch->current_qty;
        }
    }

    // 3. Check if we have enough stock
    if ($remainingQty > 0) {
        throw new \Exception("Not enough stock. Need: {$quantityNeeded}, Available: " . ($quantityNeeded - $remainingQty));
    }

    return $batchesToUse;
}

// Usage: Ketika ada penjualan
$quantityNeeded = 50; // Butuh 50 pcs
$batchesToUse = getNextBatch($outlet->id, $item->id, $quantityNeeded);

foreach ($batchesToUse as ['batch' => $batch, 'qty' => $qty]) {
    // Deduct from batch
    $batch->decrement('current_qty', $qty);

    // Create stock movement
    StockMovement::create([
        'outlet_id' => $outlet->id,
        'inventory_item_id' => $item->id,
        'batch_id' => $batch->id,
        'type' => StockMovement::TYPE_OUT,
        'quantity' => -$qty,
        'cost_price' => $batch->cost_price,
        'stock_before' => $batch->current_qty + $qty,
        'stock_after' => $batch->current_qty,
        'notes' => "Sales using FEFO - Batch: {$batch->batch_number}",
    ]);

    // Update batch status if depleted
    if ($batch->current_qty <= 0) {
        $batch->update(['status' => StockBatch::STATUS_DEPLETED]);
    }
}
```

### FEFO Example Scenario

```
Scenario: Butuh 50 pcs susu

Available Batches:
┌─────────────┬───────────────┬─────────────┬─────────────┐
│ Batch       │ Exp Date      │ Current Qty │ Status      │
├─────────────┼───────────────┼─────────────┼─────────────┤
│ BATCH-A     │ 2025-02-01    │ 30 pcs      │ Available   │ ← Paling dekat
│ BATCH-B     │ 2025-03-15    │ 40 pcs      │ Available   │
│ BATCH-C     │ 2025-05-20    │ 50 pcs      │ Available   │
└─────────────┴───────────────┴─────────────┴─────────────┘

FEFO Allocation:
1. BATCH-A: 30 pcs (habiskan batch ini dulu)
   Remaining: 50 - 30 = 20 pcs

2. BATCH-B: 20 pcs (ambil 20 dari 40)
   Remaining: 20 - 20 = 0 pcs

Result:
- BATCH-A: 0 pcs (depleted)
- BATCH-B: 20 pcs (sisa 20)
- BATCH-C: 50 pcs (tidak terpakai)
```

---

## Stock Movement Workflow Summary

### Complete Stock Movement Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    STOCK IN FLOWS                           │
└─────────────────────────────────────────────────────────────┘

1. Purchase from Supplier (GoodsReceive)
   ├─ Create StockBatch (dengan batch_number & expiry_date)
   ├─ Create StockMovement (TYPE_IN)
   ├─ Update InventoryStock (+quantity)
   └─ Update average cost (WAC)

2. Direct Purchase (tanpa PO)
   ├─ Create StockBatch (tanpa batch_number)
   ├─ Create StockMovement (TYPE_IN)
   └─ Update InventoryStock (+quantity)

3. Transfer from Other Outlet (StockTransfer)
   ├─ Create StockMovement (TYPE_TRANSFER_IN)
   ├─ Update StockBatch (current_qty +)
   └─ Update InventoryStock (+quantity)

4. Stock Adjustment (StockAdjustment) - Positive
   ├─ Create StockMovement (TYPE_ADJUSTMENT)
   ├─ Update StockBatch (current_qty +)
   └─ Update InventoryStock (+quantity)

┌─────────────────────────────────────────────────────────────┐
│                    STOCK OUT FLOWS                          │
└─────────────────────────────────────────────────────────────┘

1. Sales (Order)
   ├─ FEFO: Select batch berdasarkan expiry_date ASC
   ├─ Create StockMovement (TYPE_OUT) per batch
   ├─ Update StockBatch (current_qty -)
   └─ Update InventoryStock (-quantity)

2. Recipe Consumption (untuk produk jadi)
   ├─ Calculate gross quantity dengan waste
   ├─ FEFO: Select batch
   ├─ Create StockMovement (TYPE_OUT)
   ├─ Update StockBatch (current_qty -)
   └─ Update InventoryStock (-quantity)

3. Transfer to Other Outlet (StockTransfer)
   ├─ FEFO: Select batch
   ├─ Create StockMovement (TYPE_TRANSFER_OUT)
   ├─ Update StockBatch (current_qty -)
   └─ Update InventoryStock (-quantity)

4. Stock Adjustment (StockAdjustment) - Negative
   ├─ Create StockMovement (TYPE_ADJUSTMENT)
   ├─ Update StockBatch (current_qty -)
   └─ Update InventoryStock (-quantity)

5. Waste/Expired (WasteLog)
   ├─ Select expired/damaged batch
   ├─ Create StockMovement (TYPE_WASTE)
   ├─ Update StockBatch (status → expired/disposed)
   └─ Update InventoryStock (-quantity)
```

---

## Stock Reporting & Analytics

### 1. Expiry Report

```php
// Batch yang akan kadaluarsa dalam 30 hari
$expiringSoon = StockBatch::where('status', StockBatch::STATUS_AVAILABLE)
    ->whereDate('expiry_date', '<=', now()->addDays(30))
    ->whereDate('expiry_date', '>', now())
    ->with(['inventoryItem', 'outlet'])
    ->orderBy('expiry_date')
    ->get();

// Total value barang yang akan kadaluarsa
$totalValue = $expiringSoon->sum(function ($batch) {
    return $batch->current_qty * $batch->cost_price;
});
```

### 2. Stock Movement Report

```php
// Laporan pergerakan stock per item
$movements = StockMovement::where('inventory_item_id', $itemId)
    ->whereBetween('created_at', [$startDate, $endDate])
    ->with(['batch', 'createdBy'])
    ->orderBy('created_at', 'desc')
    ->get();

// Summary per type
$summary = $movements->groupBy('type')->map(function ($movements) {
    return [
        'count' => $movements->count(),
        'total_quantity' => $movements->sum('quantity'),
        'total_value' => $movements->sum(function ($m) {
            return $m->getValue();
        }),
    ];
});
```

### 3. Stock Valuation Report

```php
// Hitung total value inventory per outlet
$valuation = InventoryStock::where('outlet_id', $outletId)
    ->with('inventoryItem')
    ->get()
    ->sum(function ($stock) {
        return $stock->quantity * $stock->inventoryItem->cost_price;
    });
```

### 4. Batch Aging Report

```php
// Group batch berdasarkan sisa umur
$batches = StockBatch::where('status', StockBatch::STATUS_AVAILABLE)
    ->with('inventoryItem')
    ->get()
    ->groupBy(function ($batch) {
        $days = $batch->getRemainingDays();
        if ($days === null) {
            return 'No Expiry';
        } elseif ($days < 0) {
            return 'Expired';
        } elseif ($days <= 30) {
            return '0-30 days';
        } elseif ($days <= 90) {
            return '31-90 days';
        } elseif ($days <= 180) {
            return '91-180 days';
        } else {
            return '180+ days';
        }
    });
```

---

## Best Practices

### 1. **Always Create StockMovement**

Setiap kali stock berubah, SELALU buat StockMovement:

```php
// ❌ WRONG: Hanya update stock tanpa record
$stock->decrement('quantity', 10);

// ✅ CORRECT: Update stock DAN buat movement
$stockBefore = $stock->quantity;
$stock->decrement('quantity', 10);

StockMovement::create([
    'quantity' => -10,
    'stock_before' => $stockBefore,
    'stock_after' => $stock->fresh()->quantity,
    // ... other fields
]);
```

### 2. **Implement FEFO Properly**

Gunakan FEFO untuk semua barang dengan expiry:

```php
// Gunakan FEFO untuk: makanan, minuman, obat, kosmetik
if ($item->track_expiry) {
    $batches = StockBatch::orderBy('expiry_date', 'asc')->get();
} else {
    // Untuk barang non-expiry, gunakan FIFO
    $batches = StockBatch::orderBy('created_at', 'asc')->get();
}
```

### 3. **Regular Batch Cleanup**

Schedule untuk update status batch:

```php
// Daily: Update expired batches
StockBatch::where('status', StockBatch::STATUS_AVAILABLE)
    ->where('expiry_date', '<', now())
    ->update(['status' => StockBatch::STATUS_EXPIRED]);

// Daily: Update depleted batches
StockBatch::where('status', StockBatch::STATUS_AVAILABLE)
    ->where('current_qty', '<=', 0)
    ->update(['status' => StockBatch::STATUS_DEPLETED]);
```

### 4. **Stock Opname Validation**

Gunakan StockMovement untuk validasi stock opname:

```php
// System stock: 100
// Actual stock: 95
// Variance: -5

StockMovement::create([
    'type' => StockMovement::TYPE_ADJUSTMENT,
    'quantity' => -5,  // Selisih
    'stock_before' => 100,
    'stock_after' => 95,
    'notes' => 'Stock Opname Jan 2025 - Variance: -5',
]);
```

---

## Summary Table: All Phase 2 Models

### Master Data (6 models)
- ✅ Unit
- ✅ Supplier
- ✅ SupplierItem
- ✅ InventoryCategory
- ✅ InventoryItem
- ✅ InventoryStock

### Operations (5 models)
- ✅ StockAdjustment & StockAdjustmentItem
- ✅ StockTransfer & StockTransferItem
- ✅ WasteLog

### Purchase Management (4 models)
- ✅ PurchaseOrder & PurchaseOrderItem
- ✅ GoodsReceive & GoodsReceiveItem

### Recipes (2 models)
- ✅ Recipe & RecipeItem

### Stock Tracking (2 models)
- ✅ StockBatch
- ✅ StockMovement

**Total: 19 models untuk Phase 2 Inventory Management System**

---

## Next Steps

Dokumentasi Phase 2 sudah lengkap! Lanjut ke:

- [Phase 3: Products & Pricing](./phase3-products.md) - Product, ProductVariant, Price, Discount
- [Phase 4: Orders & Payments](./phase4-orders.md) - Order, OrderItem, Payment
- [Phase 5: Customers & Loyalty](./phase5-customers.md) - Customer, CustomerGroup, Loyalty

Kembali ke:
- [Phase 2: Models - Master Data (Part 1)](./phase2-models-1-master-data.md)
- [Phase 2: Models - Operations (Part 2)](./phase2-models-2-operations.md)
- [Phase 2: Models - Purchase Management (Part 3)](./phase2-models-3-purchase.md)
- [Phase 2: Models - Recipes (Part 4)](./phase2-models-4-recipes.md)
