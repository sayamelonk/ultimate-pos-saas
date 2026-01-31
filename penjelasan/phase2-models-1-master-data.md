# Phase 2: Models - Master Data (Part 1)

## Overview

Dokumentasi ini mencakup models untuk **Master Data Management**: Units, Suppliers, Inventory Categories, dan Inventory Items. Ini adalah foundational models yang menjadi dasar dari sistem inventory.

---

## 1. Unit Model ⭐

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
        'type',
        'base_unit_id',
        'conversion_factor',
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

    public function supplierItems(): HasMany
    {
        return $this->hasMany(SupplierItem::class);
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

### Relationships

| Relationship | Type | Description |
|--------------|------|-------------|
| `tenant()` | BelongsTo | Unit belongs to tenant |
| `baseUnit()` | BelongsTo | Parent unit untuk derived units |
| `derivedUnits()` | HasMany | Child units dari base unit ini |
| `inventoryItems()` | HasMany | Items yang menggunakan unit ini |
| `supplierItems()` | HasMany | Supplier items dengan unit ini |

### Helper Methods

#### isBaseUnit(): bool
Cek apakah ini adalah base unit (tidak punya parent).

```php
if ($unit->isBaseUnit()) {
    // Ini base unit (kg, L, pcs)
}
```

#### convertToBase(float $quantity): float
Convert quantity ke base unit.

```php
$grams = 5000;
$kg->convertFromBase($grams); // 5.0 kg
```

#### convertFromBase(float $quantity): float
Convert dari base unit ke unit ini.

```php
$kgQty = 5;
$grams->convertToBase($kgQty); // 5000 grams
```

### Usage Examples

```php
// Create base unit
$kg = Unit::create([
    'tenant_id' => $tenant->id,
    'name' => 'Kilogram',
    'abbreviation' => 'kg',
    'type' => 'weight',
    'conversion_factor' => 1,
    'is_active' => true,
]);

// Create derived unit
$gram = Unit::create([
    'tenant_id' => $tenant->id,
    'name' => 'Gram',
    'abbreviation' => 'g',
    'type' => 'weight',
    'base_unit_id' => $kg->id,
    'conversion_factor' => 0.001, // 1g = 0.001kg
    'is_active' => true,
]);

// Convert 5000g to kg
$kgQty = $gram->convertToBase(5000); // 5.0 kg
```

---

## 2. Supplier Model ⭐

**File:** `app/Models/Supplier.php`

Master data supplier untuk inventory procurement.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'tax_number',
        'bank_name',
        'bank_account',
        'bank_account_name',
        'payment_terms',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function supplierItems(): HasMany
    {
        return $this->hasMany(SupplierItem::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
```

### Relationships

| Relationship | Type | Description |
|--------------|------|-------------|
| `tenant()` | BelongsTo | Supplier belongs to tenant |
| `supplierItems()` | HasMany | Items yang disuplai supplier ini |
| `purchaseOrders()` | HasMany | Purchase orders ke supplier ini |

### Usage Examples

```php
$supplier = Supplier::create([
    'tenant_id' => $tenant->id,
    'code' => 'SUP-001',
    'name' => 'PT Food Supplier',
    'contact_person' => 'John Doe',
    'email' => 'john@foodsupplier.com',
    'phone' => '08123456789',
    'payment_terms' => 30, // NET 30 days
    'is_active' => true,
]);

// Get all items from this supplier
$items = $supplier->supplierItems;

// Get purchase order history
$orders = $supplier->purchaseOrders()->latest()->get();
```

---

## 3. SupplierItem Model ⭐

**File:** `app/Models/SupplierItem.php`

Pivot table antara Supplier dan InventoryItem dengan data tambahan (harga, lead time, dll).

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'supplier_id',
        'inventory_item_id',
        'supplier_sku',
        'unit_id',
        'unit_conversion',
        'price',
        'lead_time_days',
        'min_order_qty',
        'is_preferred',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_conversion' => 'decimal:4',
            'price' => 'decimal:2',
            'lead_time_days' => 'integer',
            'min_order_qty' => 'decimal:4',
            'is_preferred' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function getPriceInStockUnit(): float
    {
        return $this->price / $this->unit_conversion;
    }
}
```

### Relationships

| Relationship | Type | Description |
|--------------|------|-------------|
| `supplier()` | BelongsTo | Supplier yang menjual item ini |
| `inventoryItem()` | BelongsTo | Inventory item yang disuplai |
| `unit()` | BelongsTo | Unit untuk pricing ini |

### Helper Methods

#### getPriceInStockUnit(): float
Convert price ke stock unit (jika supplier jual dalam unit berbeda).

```php
// Supplier jual dalam carton (24 pcs)
// Harga per carton: 240,000
$pricePerPcs = $supplierItem->getPriceInStockUnit(); // 10,000 per pcs
```

### Usage Examples

```php
// Link item ke supplier
SupplierItem::create([
    'supplier_id' => $supplier->id,
    'inventory_item_id' => $item->id,
    'supplier_sku' => 'SUP-FLUOR-001',
    'unit_id' => $carton->id,
    'unit_conversion' => 25, // 1 carton = 25 kg
    'price' => 850000, // 850k per carton
    'lead_time_days' => 3, // 3 hari delivery
    'min_order_qty' => 100, // min 100 carton
    'is_preferred' => true, // Supplier utama
    'is_active' => true,
]);

// Cek harga dalam stock unit (per kg)
$pricePerKg = $supplierItem->getPriceInStockUnit();
```

---

## 4. InventoryCategory Model ⭐

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
        'parent_id',
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

| Relationship | Type | Description |
|--------------|------|-------------|
| `tenant()` | BelongsTo | Category belongs to tenant |
| `parent()` | BelongsTo | Parent category (self-referencing) |
| `children()` | HasMany | Child categories |
| `inventoryItems()` | HasMany | Items dalam category ini |

### Helper Methods

#### isRoot(): bool
Cek apakah ini root category (tidak punya parent).

#### getFullPathAttribute(): string
Get full path hierarchy: "Proteins > Beef > Wagyu"

```php
echo $category->full_path; // "Proteins > Beef > Wagyu"
```

### Usage Examples

```php
// Create root category
$proteins = InventoryCategory::create([
    'tenant_id' => $tenant->id,
    'name' => 'Proteins',
    'slug' => 'proteins',
    'description' => 'Meat, poultry, seafood',
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

// Get all items in category (termasuk subcategories)
$items = $proteins->inventoryItems()->with('category')->get();
```

---

## 5. InventoryItem Model ⭐

**File:** `app/Models/InventoryItem.php`

Master data untuk semua inventory items.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'unit_id',
        'purchase_unit_id',
        'sku',
        'barcode',
        'name',
        'description',
        'image',
        'purchase_unit_conversion',
        'cost_price',
        'min_stock',
        'max_stock',
        'reorder_point',
        'reorder_qty',
        'shelf_life_days',
        'track_batches',
        'is_active',
    ];

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

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function supplierItems(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_items')
            ->withPivot('supplier_sku', 'unit_id', 'unit_conversion', 'price', 'lead_time_days', 'min_order_qty', 'is_preferred', 'is_active')
            ->withTimestamps();
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
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

    public function getTotalStock(?array $outletIds = null): float
    {
        return $this->stocks()
            ->when($outletIds, fn ($q) => $q->whereIn('outlet_id', $outletIds))
            ->sum('quantity');
    }

    public function getTotalStockValue(): float
    {
        return $this->stocks()->sum(fn ($stock) => $stock->quantity * $stock->avg_cost);
    }
}
```

### Key Relationships

| Relationship | Type | Description |
|--------------|------|-------------|
| `category()` | BelongsTo | Item category |
| `unit()` | BelongsTo | Stock unit (utama) |
| `purchaseUnit()` | BelongsTo | Purchase unit (carton, sack) |
| `stocks()` | HasMany | Stock di semua outlets |
| `stockBatches()` | HasMany | Batch records untuk FEFO |
| `stockMovements()` | HasMany | Movement history |
| `supplierItems()` | BelongsToMany | Suppliers yang jual item ini |
| `recipeItems()` | HasMany | Recipe yang pakai item ini |

### Helper Methods

#### getStockForOutlet(string $outletId): ?InventoryStock
Get stock untuk specific outlet.

```php
$stock = $item->getStockForOutlet($outlet->id);
echo $stock->quantity; // Stock quantity di outlet ini
```

#### isLowStock(string $outletId): bool
Cek apakah stock di outlet ini sudah low.

```php
if ($item->isLowStock($outlet->id)) {
    // Send alert
}
```

#### getTotalStock(?array $outletIds = null): float
Get total stock across all (atau specific) outlets.

```php
$totalStock = $item->getTotalStock(); // All outlets
$totalStock = $item->getTotalStock([$outlet1->id, $outlet2->id]); // Specific outlets
```

### Usage Examples

```php
$item = InventoryItem::create([
    'tenant_id' => $tenant->id,
    'category_id' => $category->id,
    'unit_id' => $kg->id,
    'purchase_unit_id' => $sack->id,
    'sku' => 'FLOUR-001',
    'barcode' => '899100210001',
    'name' => 'Tepung Terigu Premium',
    'purchase_unit_conversion' => 25, // 1 sack = 25 kg
    'cost_price' => 85000, // 85k per kg
    'min_stock' => 50,
    'max_stock' => 500,
    'reorder_point' => 100,
    'reorder_qty' => 200,
    'shelf_life_days' => 180, // 6 months
    'track_batches' => true,
    'is_active' => true,
]);

// Check stock di outlet
$stock = $item->getStockForOutlet($outlet->id);
if ($item->isLowStock($outlet->id)) {
    // Send reorder alert
}

// Get total value
$totalValue = $item->getTotalStockValue();
```

---

## 6. InventoryStock Model ⭐

**File:** `app/Models/InventoryStock.php`

Tabel ini menyimpan quantity stok per outlet per item. **Satu record per combination outlet + item**.

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'outlet_id',
        'inventory_item_id',
        'quantity',
        'reserved_qty',
        'avg_cost',
        'last_cost',
        'last_received_at',
        'last_issued_at',
    ];

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

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

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

    public function getStockValueAtLastCost(): float
    {
        return $this->quantity * $this->last_cost;
    }
}
```

### Relationships

| Relationship | Type | Description |
|--------------|------|-------------|
| `outlet()` | BelongsTo | Outlet lokasi stock |
| `inventoryItem()` | BelongsTo | Item yang di-stock |

### Helper Methods

#### getAvailableQuantity(): float
Get quantity yang available (tidak termasuk reserved).

```php
$available = $stock->getAvailableQuantity(); // quantity - reserved_qty
```

#### isLowStock(): bool
Cek apakah stock sudah rendah.

```php
if ($stock->isLowStock()) {
    // Trigger reorder alert
}
```

#### getStockValue(): float
Get total stock value menggunakan weighted average cost.

```php
$value = $stock->getStockValue(); // quantity × avg_cost
```

### Usage Examples

```php
// Get atau create stock record untuk item di outlet
$stock = InventoryStock::firstOrCreate(
    [
        'outlet_id' => $outlet->id,
        'inventory_item_id' => $item->id,
    ],
    [
        'quantity' => 0,
        'reserved_qty' => 0,
        'avg_cost' => 0,
        'last_cost' => 0,
    ]
);

// Update stock (via service, jangan langsung update!)
$stock->update([
    'quantity' => 100,
    'avg_cost' => 85000,
]);

// Check available quantity
$available = $stock->getAvailableQuantity();

// Check if low stock
if ($stock->isLowStock()) {
    // Send notification
}
```

---

## Next Steps

Lanjut ke dokumentasi berikutnya:
- [Phase 2: Models - Stock Tracking (Part 2)](./phase2-models-2-stock.md) - StockBatch, StockMovement
- [Phase 2: Models - Operations (Part 3)](./phase2-models-3-operations.md) - StockAdjustment, Transfer, Waste
- [Phase 2: Models - Purchase Management (Part 4)](./phase2-models-4-purchase.md) - PurchaseOrder, GoodsReceive
- [Phase 2: Models - Recipes (Part 5)](./phase2-models-5-recipes.md) - Recipe, RecipeItem
