# Phase 2: Models - Operations (Part 2)

## Overview

Dokumentasi ini mencakup models untuk **Inventory Operations**: Stock Adjustments, Stock Transfers, dan Waste Logs. Ini adalah models yang meng-handle operasi stock movement.

---

## 1. StockAdjustment Model ⭐

**File:** `app/Models/StockAdjustment.php`

Stock adjustment untuk stock take, correction, atau opening balance.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_STOCK_TAKE = 'stock_take';
    public const TYPE_CORRECTION = 'correction';
    public const TYPE_OPENING_BALANCE = 'opening_balance';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'adjustment_number',
        'adjustment_date',
        'type',
        'status',
        'reason',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
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
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getTotalValueDifference(): float
    {
        return $this->items->sum('value_difference');
    }

    public function getPositiveAdjustments(): float
    {
        return $this->items->where('difference', '>', 0)->sum('difference');
    }

    public function getNegativeAdjustments(): float
    {
        return abs($this->items->where('difference', '<', 0)->sum('difference'));
    }
}
```

### Constants

| Constant | Value | Description |
|-----------|-------|-------------|
| `STATUS_DRAFT` | `'draft'` | Adjustment belum disubmit |
| `STATUS_APPROVED` | `'approved'` | Adjustment sudah di-approve |
| `STATUS_CANCELLED` | `'cancelled'` | Adjustment dibatalkan |
| `TYPE_STOCK_TAKE` | `'stock_take'` | Stock opname berkala |
| `TYPE_CORRECTION` | `'correction'` | Koreksi kesalahan |
| `TYPE_OPENING_BALANCE` | `'opening_balance'` | Stock awal |

### Helper Methods

#### isDraft(): bool
Cek apakah masih draft.

#### isApproved(): bool
Cek apakah sudah di-approve.

#### getTotalValueDifference(): float
Hitung total value difference semua items.

```php
$totalValueDiff = $adjustment->getTotalValueDifference();
```

### Usage Examples

```php
// Create stock adjustment
$adjustment = StockAdjustment::create([
    'tenant_id' => $tenant->id,
    'outlet_id' => $outlet->id,
    'adjustment_number' => 'ADJ-20250128-001',
    'adjustment_date' => now()->toDateString(),
    'type' => StockAdjustment::TYPE_STOCK_TAKE,
    'status' => StockAdjustment::STATUS_DRAFT,
    'reason' => 'Monthly stock take',
    'created_by' => auth()->id(),
]);

// Add adjustment items
StockAdjustmentItem::create([
    'stock_adjustment_id' => $adjustment->id,
    'inventory_item_id' => $item->id,
    'system_quantity' => 100,
    'actual_quantity' => 95,
    'difference' => -5,
    'cost_price' => 50000,
    'value_difference' => -250000,
]);

// Approve adjustment (via service)
if ($adjustment->isDraft()) {
    // Logic approve via StockAdjustmentService
}
```

---

## 2. StockAdjustmentItem Model ⭐

**File:** `app/Models/StockAdjustmentItem.php`

Detail item dalam stock adjustment.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'stock_adjustment_id',
        'inventory_item_id',
        'system_quantity',
        'actual_quantity',
        'variance',
        'cost_price',
        'value_difference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:4',
            'actual_quantity' => 'decimal:4',
            'variance' => 'decimal:4',
            'cost_price' => 'decimal:2',
            'value_difference' => 'decimal:2',
        ];
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
```

### Relationships

| Relationship | Type | Description |
|--------------|------|-------------|
| `stockAdjustment()` | BelongsTo | Parent adjustment |
| `inventoryItem()` | BelongsTo | Item yang di-adjust |

### Key Fields

| Field | Description |
|-------|-------------|
| `system_quantity` | Quantity menurut sistem |
| `actual_quantity` | Quantity aktual (hasil hitung) |
| `variance` | Selisih: actual - system |
| `cost_price` | Cost price per unit |
| `value_difference` | variance × cost_price |

### Usage Examples

```php
StockAdjustmentItem::create([
    'stock_adjustment_id' => $adjustment->id,
    'inventory_item_id' => $item->id,
    'system_quantity' => 100.00,    // Menurut sistem: 100 kg
    'actual_quantity' => 95.50,    // Aktual: 95.5 kg
    'variance' => -4.50,             // Selisih: -4.5 kg
    'cost_price' => 50000,         // 50k per kg
    'value_difference' => -225000, // -4.5 × 50k = -225k
    'notes' => 'Found expired items',
]);
```

---

## 3. StockTransfer Model ⭐

**File:** `app/Models/StockTransfer.php`

Transfer stock antar outlet.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory, HasUuid;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'from_outlet_id',
        'to_outlet_id',
        'transfer_number',
        'transfer_date',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'received_by',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'approved_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function fromOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'from_outlet_id');
    }

    public function toOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'to_outlet_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInTransit(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeReceived(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    public function getTotalValue(): float
    {
        return $this->items->sum(fn ($item) => $item->quantity * $item->cost_price);
    }
}
```

### Status Flow

```
draft → pending → in_transit → received
                           ↓
                      cancelled
```

### Helper Methods

#### canBeApproved(): bool
Cek apakah bisa di-approve (must be `pending`).

#### canBeReceived(): bool
Cek apakah bisa diterima (must be `in_transit`).

#### getTotalValue(): float
Hitung total value transfer.

```php
$totalValue = $transfer->getTotalValue();
```

### Usage Examples

```php
// Create transfer request
$transfer = StockTransfer::create([
    'tenant_id' => $tenant->id,
    'from_outlet_id' => $outletA->id,
    'to_outlet_id' => $outletB->id,
    'transfer_number' => 'TRF-20250128-001',
    'transfer_date' => now()->toDateString(),
    'status' => StockTransfer::STATUS_DRAFT,
    'created_by' => auth()->id(),
]);

// Add items
StockTransferItem::create([
    'stock_transfer_id' => $transfer->id,
    'inventory_item_id' => $item->id,
    'quantity' => 50,
    'unit_id' => $kg->id,
    'cost_price' => 50000,
]);

// Submit & approve
$transfer->update(['status' => StockTransfer::STATUS_PENDING]);
$transfer->update(['status' => StockTransfer::STATUS_IN_TRANSIT]);

// Receive at destination
$transfer->update([
    'status' => StockTransfer::STATUS_RECEIVED,
    'received_by' => auth()->id(),
    'received_at' => now(),
]);
```

---

## 4. StockTransferItem Model ⭐

**File:** `app/Models/StockTransferItem.php`

Detail item dalam stock transfer.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'stock_transfer_id',
        'inventory_item_id',
        'quantity',
        'unit_id',
        'cost_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'cost_price' => 'decimal:2',
        ];
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
```

### Usage Examples

```php
StockTransferItem::create([
    'stock_transfer_id' => $transfer->id,
    'inventory_item_id' => $flour->id,
    'quantity' => 50.0000, // 50 kg
    'unit_id' => $kg->id,
    'cost_price' => 85000, // 85k per kg
    'notes' => 'Emergency stock transfer',
]);
```

---

## 5. WasteLog Model ⭐

**File:** `app/Models/WasteLog.php`

Catatan pembuangan/waste inventory.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteLog extends Model
{
    use HasFactory, HasUuid;

    public const REASON_EXPIRED = 'expired';
    public const REASON_SPOILED = 'spoiled';
    public const REASON_DAMAGED = 'damaged';
    public const REASON_PREPARATION = 'preparation';
    public const REASON_OVERPRODUCTION = 'overproduction';
    public const REASON_OTHER = 'other';

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'inventory_item_id',
        'batch_id',
        'waste_date',
        'quantity',
        'unit_id',
        'cost_price',
        'total_cost',
        'reason',
        'notes',
        'logged_by',
    ];

    protected function casts(): array
    {
        return [
            'waste_date' => 'date',
            'quantity' => 'decimal:4',
            'cost_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
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

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function calculateTotalCost(): void
    {
        $this->total_cost = $this->quantity * $this->cost_price;
    }

    public static function getReasons(): array
    {
        return [
            self::REASON_EXPIRED => 'Expired',
            self::REASON_SPOILED => 'Spoiled',
            self::REASON_DAMAGED => 'Damaged',
            self::REASON_PREPARATION => 'Preparation Waste',
            self::REASON_OVERPRODUCTION => 'Overproduction',
            self::REASON_OTHER => 'Other',
        ];
    }
}
```

### Waste Reasons

| Reason | Value | Description |
|--------|-------|-------------|
| Expired | `expired` | Product expired |
| Spoiled | `spoiled` | Product rusak |
| Damaged | `damaged` | Kemasan rusak |
| Preparation Waste | `preparation` | Waste saat prep |
| Overproduction | `overproduction` | Kelebihan produksi |
| Other | `other` | Alasan lain |

### Helper Methods

#### calculateTotalCost(): void
Hitung total cost secara otomatis.

```php
$wasteLog->calculateTotalCost(); // quantity × cost_price
```

#### getReasons(): array
Static method untuk get semua reason options.

```php
$reasons = WasteLog::getReasons();
// [
//     'expired' => 'Expired',
//     'spoiled' => 'Spoiled',
//     'damaged' => 'Damaged',
//     'preparation' => 'Preparation Waste',
//     'overproduction' => 'Overproduction',
//     'other' => 'Other',
// ]
```

### Usage Examples

```php
// Log waste
$waste = WasteLog::create([
    'tenant_id' => $tenant->id,
    'outlet_id' => $outlet->id,
    'inventory_item_id' => $item->id,
    'batch_id' => $batch->id, // Optional
    'waste_date' => now()->toDateString(),
    'quantity' => 5.500, // 5.5 kg
    'unit_id' => $kg->id,
    'cost_price' => 85000,
    'reason' => WasteLog::REASON_EXPIRED,
    'notes' => 'Expired batch #BATCH-001',
    'logged_by' => auth()->id(),
]);

// Total cost dihitung otomatis
echo $waste->total_cost; // 467,500 (5.5 × 85,000)

// Get semua reasons untuk dropdown
$reasons = WasteLog::getReasons();
```

---

## Workflow Summary

### Stock Adjustment Workflow
```
1. Create Adjustment (status: draft)
2. Add Items (system qty, actual qty, variance)
3. Submit Adjustment
4. Approve → Update stock via StockMovement
```

### Stock Transfer Workflow
```
1. Create Transfer (status: draft)
2. Add Items
3. Submit → status: pending
4. Approve → status: in_transit
5. Receive → status: received
```

### Waste Logging Workflow
```
1. Record Waste
2. Select Item & Batch
3. Enter Quantity & Reason
4. Auto-calculate Total Cost
5. Create StockMovement (TYPE_WASTE)
```

---

## Next Steps

Lanjut ke dokumentasi berikutnya:
- [Phase 2: Models - Purchase Management (Part 3)](./phase2-models-3-purchase.md) - PurchaseOrder, GoodsReceive
- [Phase 2: Models - Recipes (Part 4)](./phase2-models-4-recipes.md) - Recipe, RecipeItem
- [Phase 2: Models - Stock & Movement (Part 5)](./phase2-models-5-stock.md) - StockBatch, StockMovement
