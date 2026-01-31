# Phase 2: Models - Inventory Management

## Overview

Phase 2 models mengimplementasikan business logic untuk inventory management. Semua models menggunakan UUID sebagai primary key dan trait `HasUuid` untuk auto-generate UUID.

---

## Model Architecture

### Common Traits
```php
use HasFactory, HasUuid;
```

### Common Conventions
- **UUID Primary Key**: Semua model menggunakan UUID
- **Fillable**: Explicit mass assignment protection
- **Casts**: Type casting menggunakan `casts()` method (PHP 8.4+)
- **Relationships**: Type-hinted relationship methods

---

## 1. InventoryCategory Model ⭐

**File:** `app/Models/InventoryCategory.php`

Kategorisasi inventory items dengan support untuk hierarchy (parent-child).

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCategory extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'parent_id',              // Untuk hierarchy (category dalam category)
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(InventoryCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function getFullPathAttribute(): string
    {
        $path = $this->name;
        $parent = $this->parent;

        while ($parent) {
            $path = $parent->name.' > '.$path;
            $parent = $parent->parent;
        }

        return $path;
    }
}
```

### Relationships

#### tenant(): BelongsTo
```php
public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}
```

#### parent(): BelongsTo
```php
public function parent(): BelongsTo
{
    return $this->belongsTo(InventoryCategory::class, 'parent_id');
}
```
Self-referencing relationship untuk hierarchy.

#### children(): HasMany
```php
public function children(): HasMany
{
    return $this->hasMany(InventoryCategory::class, 'parent_id')->orderBy('sort_order');
}
```

#### inventoryItems(): HasMany
```php
public function inventoryItems(): HasMany
{
    return $this->hasMany(InventoryItem::class, 'category_id');
}
```

### Helper Methods

#### isRoot(): bool
```php
public function isRoot(): bool
{
    return $this->parent_id === null;
}
```
Check apakah ini adalah root category (tidak punya parent).

#### getFullPathAttribute(): string
```php
public function getFullPathAttribute(): string
{
    $path = $this->name;
    $parent = $this->parent;

    while ($parent) {
        $path = $parent->name.' > '.$path;
        $parent = $parent->parent;
    }

    return $path;
}
```
Get full path: "Proteins > Beef > Wagyu"

### Usage Examples

```php
// Create root category
$proteins = InventoryCategory::create([
    'tenant_id' => $tenant->id,
    'name' => 'Proteins',
    'slug' => 'proteins',
    'sort_order' => 1,
    'is_active' => true,
]);

// Create subcategory
$beef = InventoryCategory::create([
    'tenant_id' => $tenant->id,
    'parent_id' => $proteins->id,
    'name' => 'Beef',
    'slug' => 'beef',
    'sort_order' => 1,
    'is_active' => true,
]);

// Get full path
echo $beef->full_path; // "Proteins > Beef"

// Get all descendants
$allDescendants = $proteins->allChildren; // Recursive load
```

---

## 2. InventoryItem Model ⭐

**File:** `app/Models/InventoryItem.php`

Master data untuk semua inventory items.

### Fillable Fields

```php
protected $fillable = [
    'tenant_id',
    'category_id',
    'unit_id',                  // Unit stok utama
    'purchase_unit_id',         // Unit pembelian
    'sku',                      // Stock Keeping Unit
    'barcode',
    'name',
    'description',
    'image',
    'purchase_unit_conversion', // 1 carton = 24 pcs
    'cost_price',               // Harga pokok
    'min_stock',                // Minimum stok
    'max_stock',                // Maksimum stok
    'reorder_point',            // Titik reorder
    'reorder_qty',              // Quantity reorder
    'shelf_life_days',          // Umur simpan
    'track_batches',            // Track batch/expiry
    'is_active',
];
```

### Casts

```php
protected function casts(): array
{
    return [
        'purchase_unit_conversion' => 'decimal:4',
        'cost_price' => 'decimal:2',
        'min_stock' => 'decimal:4',
        'max_stock' => 'decimal:4',
        'reorder_point' => 'decimal:4',
        'reorder_qty' => 'decimal:4',
        'shelf_life_days' => 'integer',
        'track_batches' => 'boolean',
        'is_active' => 'boolean',
    ];
}
```

### Key Relationships

```php
public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}

public function category(): BelongsTo
{
    return $this->belongsTo(InventoryCategory::class, 'category_id');
}

public function unit(): BelongsTo
{
    return $this->belongsTo(Unit::class);
}

public function purchaseUnit(): BelongsTo
{
    return $this->belongsTo(Unit::class, 'purchase_unit_id');
}

public function stocks(): HasMany
{
    return $this->hasMany(InventoryStock::class);
}

public function getStockForOutlet(string $outletId): ?InventoryStock
{
    return $this->stocks()->where('outlet_id', $outletId)->first();
}

public function isLowStock(string $outletId): bool
{
    $stock = $this->getStockForOutlet($outletId);
    return $stock && $stock->quantity <= $this->reorder_point;
}
```

### Usage

```php
$item = InventoryItem::create([
    'tenant_id' => $tenant->id,
    'category_id' => $category->id,
    'unit_id' => $kg->id,
    'purchase_unit_id' => $sack->id,
    'sku' => 'FLOUR-001',
    'name' => 'Tepung Terigu',
    'purchase_unit_conversion' => 25, // 1 sack = 25 kg
    'cost_price' => 85000,
    'min_stock' => 50,
    'reorder_point' => 100,
]);
```

---

## 3. InventoryStock Model ⭐

**File:** `app/Models/InventoryStock.php`

Tabel ini menyimpan quantity stok per outlet per item. **Satu record per combination outlet + item**.

### Fillable Fields

```php
protected $fillable = [
    'outlet_id',
    'inventory_item_id',
    'quantity',              // Quantity on hand
    'reserved_qty',          // Reserved untuk orders
    'avg_cost',              // Weighted average cost
    'last_cost',             // Last purchase cost
    'last_received_at',
    'last_issued_at',
];
```

### Casts

```php
protected function casts(): array
{
    return [
        'quantity' => 'decimal:4',
        'reserved_qty' => 'decimal:4',
        'avg_cost' => 'decimal:2',
        'last_cost' => 'decimal:2',
        'last_received_at' => 'datetime',
        'last_issued_at' => 'datetime',
    ];
}
```

### Helper Methods

```php
public function getAvailableQuantity(): float
{
    return $this->quantity - $this->reserved_qty;
}

public function isLowStock(): bool
{
    return $this->quantity <= $this->inventoryItem->reorder_point;
}

public function getStockValue(): float
{
    return $this->quantity * $this->avg_cost;
}
```

---

## 4. Unit Model ⭐

**File:** `app/Models/Unit.php`

Representasi satuan unit untuk inventory (kg, gram, pcs, dll).

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'name',
        'abbreviation',
        'base_unit_id',      // Untuk konversi (g -> kg)
        'conversion_factor', // How many base units in this unit
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function isBaseUnit(): bool
    {
        return $this->base_unit_id === null;
    }

    public function convertToBase(float $quantity): float
    {
        return $quantity * $this->conversion_factor;
    }

    public function convertFromBase(float $quantity): float
    {
        return $quantity / $this->conversion_factor;
    }
}
```

### Usage

```php
// Create base unit
$kg = Unit::create([
    'tenant_id' => $tenant->id,
    'name' => 'Kilogram',
    'abbreviation' => 'kg',
    'conversion_factor' => 1,
]);

// Create derived unit
$gram = Unit::create([
    'tenant_id' => $tenant->id,
    'name' => 'Gram',
    'abbreviation' => 'g',
    'base_unit_id' => $kg->id,
    'conversion_factor' => 0.001, // 1g = 0.001kg
]);

// Convert 5000g to kg
$kg->convertFromBase(5000); // 5.0
```

---

## Eager Loading Best Practices

Untuk menghindari N+1 query problems:

```php
// ❌ Bad - N+1 problem
$items = InventoryItem::all();
foreach ($items as $item) {
    echo $item->category->name;
}

// ✅ Good - Eager loading
$items = InventoryItem::with('category', 'unit')->paginate(15);
foreach ($items as $item) {
    echo $item->category->name;
}
```

---

## Query Scopes

Tambahkan query scopes di models untuk query yang sering dipakai:

```php
// Dalam InventoryItem model
public function scopeActive($query)
{
    return $query->where('is_active', true);
}

public function scopeLowStock($query, $outletId)
{
    return $query->whereHas('stocks', function ($q) use ($outletId) {
        $q->where('outlet_id', $outletId)
          ->where('quantity', '<=', 'reorder_point');
    });
}
```

---

## Next Steps

Lihat dokumentasi berikutnya:
- [Phase 2: Seeders](./phase2-seeders.md) - Data awal
- [Phase 2: Controllers](./phase2-controllers.md) - HTTP layer
- [Phase 2: Views](./phase2-views.md) - UI templates
