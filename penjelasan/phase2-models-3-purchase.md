# Phase 2: Models - Purchase Management (Part 3)

## Overview

Dokumentasi ini mencakup models untuk **Purchase Management**: Purchase Orders dan Goods Receive. Ini adalah models yang meng-handle procurement workflow dari pembuatan PO hingga penerimaan barang.

---

## 1. PurchaseOrder Model ⭐

**File:** `app/Models/PurchaseOrder.php`

Purchase order untuk pembelian barang ke supplier.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'supplier_id',
        'po_number',
        'order_date',
        'expected_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'notes',
        'terms',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceives(): HasMany
    {
        return $this->hasMany(GoodsReceive::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED]);
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function canBeReceived(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PARTIAL]);
    }

    public function isFullyReceived(): bool
    {
        return $this->items->every(fn ($item) => $item->received_qty >= $item->quantity);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->discount_amount = $this->items->sum('discount_amount');
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
    }
}
```

### Status Constants

| Constant | Value | Description |
|-----------|-------|-------------|
| `STATUS_DRAFT` | `'draft'` | PO belum disubmit |
| `STATUS_SUBMITTED` | `'submitted'` | PO sudah disubmit, menunggu approval |
| `STATUS_APPROVED` | `'approved'` | PO disetujui, siap diterima |
| `STATUS_PARTIAL` | `'partial'` | Sebagian barang sudah diterima |
| `STATUS_RECEIVED` | `'received'` | Semua barang sudah diterima |
| `STATUS_CANCELLED` | `'cancelled'` | PO dibatalkan |

### Status Flow

```
draft → submitted → approved → partial → received
                       ↓
                   cancelled
```

### Helper Methods

#### isEditable(): bool
Cek apakah PO masih bisa diedit (draft atau submitted).

#### canBeApproved(): bool
Cek apakah PO bisa di-approve (must be `submitted`).

#### canBeReceived(): bool
Cek apakah PO bisa diterima (approved atau partial).

#### isFullyReceived(): bool
Cek apakah semua item sudah diterima sepenuhnya.

```php
if ($po->isFullyReceived()) {
    $po->update(['status' => PurchaseOrder::STATUS_RECEIVED]);
}
```

#### calculateTotals(): void
Hitung subtotal, tax, discount, dan total dari semua items.

```php
$po->calculateTotals();
// $po->subtotal = sum(items.total)
// $po->tax_amount = sum(items.tax_amount)
// $po->discount_amount = sum(items.discount_amount)
// $po->total = subtotal + tax - discount
```

### Usage Examples

```php
// Create PO
$po = PurchaseOrder::create([
    'tenant_id' => $tenant->id,
    'outlet_id' => $outlet->id,
    'supplier_id' => $supplier->id,
    'po_number' => 'PO-20250128-001',
    'order_date' => now()->toDateString(),
    'expected_date' => now()->addDays(7)->toDateString(),
    'status' => PurchaseOrder::STATUS_DRAFT,
    'created_by' => auth()->id(),
]);

// Add items
PurchaseOrderItem::create([
    'purchase_order_id' => $po->id,
    'inventory_item_id' => $item->id,
    'unit_id' => $karton->id,      // Order dalam karton
    'unit_conversion' => 12.00,    // 1 karton = 12 pcs (stock unit)
    'quantity' => 10.0000,         // 10 karton
    'unit_price' => 120000,        // 120k per karton
    'discount_percent' => 5.00,
    'tax_percent' => 11.00,
]);

// Calculate totals
$po->calculateTotals();

// Submit & approve
$po->update(['status' => PurchaseOrder::STATUS_SUBMITTED]);
$po->update([
    'status' => PurchaseOrder::STATUS_APPROVED,
    'approved_by' => auth()->id(),
    'approved_at' => now(),
]);
```

---

## 2. PurchaseOrderItem Model ⭐

**File:** `app/Models/PurchaseOrderItem.php`

Detail item dalam purchase order.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'unit_id',
        'unit_conversion',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'total',
        'received_qty',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_conversion' => 'decimal:4',
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'received_qty' => 'decimal:4',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function goodsReceiveItems(): HasMany
    {
        return $this->hasMany(GoodsReceiveItem::class);
    }

    public function getRemainingQty(): float
    {
        return $this->quantity - $this->received_qty;
    }

    public function isFullyReceived(): bool
    {
        return $this->received_qty >= $this->quantity;
    }

    public function getStockQuantity(): float
    {
        return $this->quantity * $this->unit_conversion;
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->quantity * $this->unit_price;
        $this->discount_amount = $subtotal * ($this->discount_percent / 100);
        $afterDiscount = $subtotal - $this->discount_amount;
        $this->tax_amount = $afterDiscount * ($this->tax_percent / 100);
        $this->total = $afterDiscount + $this->tax_amount;
    }
}
```

### Key Fields

| Field | Description | Example |
|-------|-------------|---------|
| `unit_id` | Unit saat order (bukan stock unit) | Karton |
| `unit_conversion` | Konversi ke stock unit | 1 karton = 12 pcs |
| `quantity` | Quantity dalam order unit | 10 karton |
| `received_qty` | Quantity yang sudah diterima | 5 karton |
| `unit_price` | Harga per order unit | 120k/karton |

### Helper Methods

#### getRemainingQty(): float
Hitung sisa quantity yang belum diterima.

```php
$remaining = $item->getRemainingQty(); // quantity - received_qty
```

#### isFullyReceived(): bool
Cek apakah item sudah diterima sepenuhnya.

#### getStockQuantity(): float
Konversi quantity ke stock unit.

```php
// Order: 10 karton, Conversion: 12 pcs/karton
$stockQty = $item->getStockQuantity(); // 120.0000 pcs
```

#### calculateTotals(): void
Hitung discount, tax, dan total.

```php
$item->calculateTotals();
// $subtotal = 10 × 120,000 = 1,200,000
// $discount_amount = 1,200,000 × 5% = 60,000
// $after_discount = 1,140,000
// $tax_amount = 1,140,000 × 11% = 125,400
// $total = 1,140,000 + 125,400 = 1,265,400
```

### Usage Examples

```php
// Example: Order tepung dalam karung
PurchaseOrderItem::create([
    'purchase_order_id' => $po->id,
    'inventory_item_id' => $tepung->id,
    'unit_id' => $karung->id,       // Order dalam karung
    'unit_conversion' => 50.0000,   // 1 karung = 50 kg (stock unit)
    'quantity' => 20.0000,          // 20 karung
    'unit_price' => 1500000,        // 1.5jt per karung
    'discount_percent' => 10.00,
    'tax_percent' => 11.00,
    'received_qty' => 0,            // Belum diterima
]);

// Setelah penerimaan pertama (8 karung)
$item->increment('received_qty', 8.0000);
echo $item->getRemainingQty(); // 12.0000 karung

// Setelah penerimaan kedua (12 karung)
$item->increment('received_qty', 12.0000);
echo $item->isFullyReceived(); // true
```

---

## 3. GoodsReceive Model ⭐

**File:** `app/Models/GoodsReceive.php`

Penerimaan barang dari supplier. Terhubung dengan PurchaseOrder.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceive extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'purchase_order_id',
        'supplier_id',
        'gr_number',
        'receive_date',
        'status',
        'invoice_number',
        'invoice_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'notes',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'receive_date' => 'date',
            'invoice_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiveItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->discount_amount = $this->items->sum('discount_amount');
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
    }
}
```

### Relationships

| Relationship | Type | Description |
|--------------|------|-------------|
| `purchaseOrder()` | BelongsTo | PO terkait (optional) |
| `supplier()` | BelongsTo | Supplier barang |
| `items()` | HasMany | Detail barang diterima |
| `receivedBy()` | BelongsTo | User yang menerima |

### Helper Methods

#### calculateTotals(): void
Hitung totals dari semua items (sama seperti PO).

### Usage Examples

```php
// Create goods receive dengan PO
$gr = GoodsReceive::create([
    'tenant_id' => $tenant->id,
    'outlet_id' => $outlet->id,
    'purchase_order_id' => $po->id,     // Terkait dengan PO
    'supplier_id' => $supplier->id,
    'gr_number' => 'GR-20250128-001',
    'receive_date' => now()->toDateString(),
    'invoice_number' => 'INV-2025-00123',
    'invoice_date' => now()->toDateString(),
    'status' => GoodsReceive::STATUS_DRAFT,
    'received_by' => auth()->id(),
]);

// Create goods receive tanpa PO (direct purchase)
$gr = GoodsReceive::create([
    'tenant_id' => $tenant->id,
    'outlet_id' => $outlet->id,
    'purchase_order_id' => null,        // Tanpa PO
    'supplier_id' => $supplier->id,
    'gr_number' => 'GR-20250128-002',
    'receive_date' => now()->toDateString(),
    'status' => GoodsReceive::STATUS_DRAFT,
    'received_by' => auth()->id(),
]);
```

---

## 4. GoodsReceiveItem Model ⭐

**File:** `app/Models/GoodsReceiveItem.php`

Detail item dalam goods receive. Termasuk tracking batch dan expiry.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GoodsReceiveItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'goods_receive_id',
        'purchase_order_item_id',
        'inventory_item_id',
        'unit_id',
        'unit_conversion',
        'quantity',
        'stock_qty',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'total',
        'batch_number',
        'production_date',
        'expiry_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_conversion' => 'decimal:4',
            'quantity' => 'decimal:4',
            'stock_qty' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'production_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function goodsReceive(): BelongsTo
    {
        return $this->belongsTo(GoodsReceive::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockBatch(): HasOne
    {
        return $this->hasOne(StockBatch::class, 'goods_receive_item_id');
    }

    public function getCostPerStockUnit(): float
    {
        if ($this->stock_qty <= 0) {
            return 0;
        }

        return $this->total / $this->stock_qty;
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->quantity * $this->unit_price;
        $this->discount_amount = $subtotal * ($this->discount_percent / 100);
        $afterDiscount = $subtotal - $this->discount_amount;
        $this->tax_amount = $afterDiscount * ($this->tax_percent / 100);
        $this->total = $afterDiscount + $this->tax_amount;
        $this->stock_qty = $this->quantity * $this->unit_conversion;
    }
}
```

### Key Fields

| Field | Description | Example |
|-------|-------------|---------|
| `quantity` | Quantity diterima dalam order unit | 10 karton |
| `stock_qty` | Quantity dalam stock unit (auto) | 120 pcs |
| `batch_number` | Nomor batch | BATCH-2025-001 |
| `production_date` | Tanggal produksi | 2025-01-20 |
| `expiry_date` | Tanggal kadaluarsa | 2026-01-20 |

### Helper Methods

#### getCostPerStockUnit(): float
Hitung cost price per stock unit (pcs/kg).

```php
// Total: 1,265,400, Stock Qty: 120 pcs
$costPerPcs = $grItem->getCostPerStockUnit(); // 10,545 per pcs
```

#### calculateTotals(): void
Hitung totals dan stock_qty secara otomatis.

```php
$grItem->calculateTotals();
// $stock_qty = $quantity × $unit_conversion
// $total = dengan discount & tax
```

### Usage Examples

```php
// Receive item dengan batch & expiry tracking
GoodsReceiveItem::create([
    'goods_receive_id' => $gr->id,
    'purchase_order_item_id' => $poItem->id, // Link ke PO item
    'inventory_item_id' => $susu->id,
    'unit_id' => $karton->id,
    'unit_conversion' => 24.0000,  // 1 karton = 24 pcs
    'quantity' => 10.0000,         // 10 karton diterima
    'unit_price' => 480000,        // 480k per karton
    'discount_percent' => 5.00,
    'tax_percent' => 11.00,
    'batch_number' => 'SUSU-2025-01-20-A',
    'production_date' => '2025-01-20',
    'expiry_date' => '2026-01-20', // 1 tahun
    'notes' => 'Kondisi baik',
]);

// stock_qty otomatis dihitung: 10 × 24 = 240 pcs
echo $grItem->stock_qty; // 240.0000

// Cost per stock unit
echo $grItem->getCostPerStockUnit(); // ±20,000 per pcs
```

---

## Workflow Summary

### Complete Purchase to Receive Workflow

```
1. Create Purchase Order (status: draft)
   ├─ Add Items (order unit, unit conversion, quantity, price)
   └─ Calculate Totals

2. Submit PO (status: submitted)
   └─ Waiting for approval

3. Approve PO (status: approved)
   ├─ Set approved_by & approved_at
   └─ Ready to receive

4. Create Goods Receive (status: draft)
   ├─ Link to PO (optional)
   ├─ Add Receive Items
   │  ├─ Link to PO Item
   │  ├─ Enter Quantity Received
   │  ├─ Enter Batch Number (FEFO tracking)
   │  ├─ Enter Production Date
   │  └─ Enter Expiry Date
   └─ Calculate Totals

5. Complete Goods Receive (status: completed)
   ├─ Create StockBatch entries
   ├─ Update InventoryStock
   ├─ Create StockMovement (TYPE_IN)
   ├─ Update PO Item received_qty
   └─ Update PO status (partial/received)
```

### Status Update Flow

**PO Status Flow:**
```
draft → submitted → approved → partial → received
                       ↓
                   cancelled
```

**GR Status Flow:**
```
draft → completed
  ↓
cancelled
```

### Automatic Calculations

**PurchaseOrderItem::calculateTotals():**
```php
$subtotal = quantity × unit_price
$discount_amount = $subtotal × (discount_percent / 100)
$after_discount = $subtotal - $discount_amount
$tax_amount = $after_discount × (tax_percent / 100)
$total = $after_discount + $tax_amount
```

**GoodsReceiveItem::calculateTotals():**
```php
$subtotal = quantity × unit_price
$discount_amount = $subtotal × (discount_percent / 100)
$after_discount = $subtotal - $discount_amount
$tax_amount = $after_discount × (tax_percent / 100)
$total = $after_discount + $tax_amount
$stock_qty = $quantity × $unit_conversion  // Auto convert to stock unit
```

---

## Integration with Stock Management

### When Goods Receive is Completed:

1. **Create StockBatch:**
   ```php
   StockBatch::create([
       'tenant_id' => $gr->tenant_id,
       'outlet_id' => $gr->outlet_id,
       'inventory_item_id' => $grItem->inventory_item_id,
       'goods_receive_item_id' => $grItem->id,
       'batch_number' => $grItem->batch_number,
       'quantity' => $grItem->stock_qty,
       'remaining_qty' => $grItem->stock_qty,
       'cost_price' => $grItem->getCostPerStockUnit(),
       'production_date' => $grItem->production_date,
       'expiry_date' => $grItem->expiry_date,
   ]);
   ```

2. **Update InventoryStock:**
   ```php
   $stock = InventoryStock::firstOrCreate([
       'tenant_id' => $gr->tenant_id,
       'outlet_id' => $gr->outlet_id,
       'inventory_item_id' => $grItem->inventory_item_id,
   ]);

   $stock->increment('quantity', $grItem->stock_qty);
   $stock->updateAverageCost($grItem->getCostPerStockUnit(), $grItem->stock_qty);
   ```

3. **Create StockMovement:**
   ```php
   StockMovement::create([
       'tenant_id' => $gr->tenant_id,
       'outlet_id' => $gr->outlet_id,
       'inventory_item_id' => $grItem->inventory_item_id,
       'batch_id' => $batch->id,
       'movement_date' => $gr->receive_date,
       'type' => StockMovement::TYPE_IN,
       'quantity' => $grItem->stock_qty,
       'cost_price' => $grItem->getCostPerStockUnit(),
       'reference_type' => GoodsReceive::class,
       'reference_id' => $gr->id,
   ]);
   ```

---

## Next Steps

Lanjut ke dokumentasi berikutnya:
- [Phase 2: Models - Recipes (Part 4)](./phase2-models-4-recipes.md) - Recipe, RecipeItem
- [Phase 2: Models - Stock & Movement (Part 5)](./phase2-models-5-stock.md) - StockBatch, StockMovement
