# Phase 2: Models - Recipes (Part 4)

## Overview

Dokumentasi ini mencakup models untuk **Recipe Management**: Recipe dan RecipeItem. Ini adalah models yang meng-handle resep produk jadi (finished goods) dari bahan-bahan mentah (raw materials).

---

## 1. Recipe Model ⭐

**File:** `app/Models/Recipe.php`

Resep untuk membuat produk jadi dari inventory items (raw materials).

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'description',
        'instructions',
        'yield_qty',
        'yield_unit_id',
        'estimated_cost',
        'prep_time_minutes',
        'cook_time_minutes',
        'version',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'yield_qty' => 'decimal:4',
            'estimated_cost' => 'decimal:2',
            'prep_time_minutes' => 'integer',
            'cook_time_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): ?BelongsTo
    {
        // Product model will be added in Phase 3
        if (! class_exists(Product::class)) {
            return null;
        }

        return $this->belongsTo(Product::class);
    }

    public function yieldUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'yield_unit_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class)->orderBy('sort_order');
    }

    public function getTotalTimeMinutes(): int
    {
        return ($this->prep_time_minutes ?? 0) + ($this->cook_time_minutes ?? 0);
    }

    public function calculateCost(): float
    {
        return $this->items->sum(function ($item) {
            return $item->calculateCost();
        });
    }

    public function getCostPerUnit(): float
    {
        if ($this->yield_qty <= 0) {
            return 0;
        }

        return $this->estimated_cost / $this->yield_qty;
    }
}
```

### Key Fields

| Field | Description | Example |
|-------|-------------|---------|
| `product_id` | Link ke produk jadi (Phase 3) | null saat ini |
| `yield_qty` | Quantity hasil resep | 10.0000 (porsi) |
| `yield_unit_id` | Unit hasil resep | porsi |
| `estimated_cost` | Total biaya bahan | 150,000 |
| `prep_time_minutes` | Waktu persiapan | 15 menit |
| `cook_time_minutes` | Waktu masak | 30 menit |
| `version` | Versi resep | 1, 2, 3... |
| `is_active` | Status aktif | true |

### Helper Methods

#### getTotalTimeMinutes(): int
Hitung total waktu (prep + cook).

```php
$totalTime = $recipe->getTotalTimeMinutes(); // prep + cook
```

#### calculateCost(): float
Hitung total biaya semua bahan dengan waste percentage.

```php
$totalCost = $recipe->calculateCost();
// Sum semua items dengan gross quantity (dengan waste)
```

#### getCostPerUnit(): float
Hitung biaya per unit hasil (yield).

```php
// Estimated cost: 150,000, Yield: 10 porsi
$costPerUnit = $recipe->getCostPerUnit(); // 15,000 per porsi
```

### Usage Examples

```php
// Create recipe untuk Nasi Goreng
$recipe = Recipe::create([
    'tenant_id' => $tenant->id,
    'product_id' => null, // Akan di-link di Phase 3
    'name' => 'Nasi Goreng Spesial',
    'description' => 'Nasi goreng dengan telur, ayam, dan sayuran',
    'instructions' => '1. Tumis bumbu\n2. Masukkan nasi\n3. Aduk rata\n4. Sajikan',
    'yield_qty' => 10.0000,        // 10 porsi
    'yield_unit_id' => $porsi->id,
    'estimated_cost' => 150000,    // Akan dihitung ulang
    'prep_time_minutes' => 15,     // 15 menit persiapan
    'cook_time_minutes' => 30,     // 30 menit masak
    'version' => 1,
    'is_active' => true,
]);

// Add ingredients
RecipeItem::create([
    'recipe_id' => $recipe->id,
    'inventory_item_id' => $nasi->id,
    'unit_id' => $porsi->id,
    'quantity' => 10.0000,
    'waste_percentage' => 0.00,
    'sort_order' => 1,
]);

RecipeItem::create([
    'recipe_id' => $recipe->id,
    'inventory_item_id' => $telur->id,
    'unit_id' => $butir->id,
    'quantity' => 12.0000,
    'waste_percentage' => 5.00,     // 5% waste (telur pecah dll)
    'sort_order' => 2,
]);

// Hitung ulang cost
$recipe->update(['estimated_cost' => $recipe->calculateCost()]);
echo $recipe->getCostPerUnit(); // Biaya per porsi
echo $recipe->getTotalTimeMinutes(); // 45 menit total
```

---

## 2. RecipeItem Model ⭐

**File:** `app/Models/RecipeItem.php`

Detail bahan dalam resep (ingredient).

### Full Source Code

```php
<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'recipe_id',
        'inventory_item_id',
        'unit_id',
        'quantity',
        'waste_percentage',
        'sort_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'waste_percentage' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function getGrossQuantity(): float
    {
        $wasteFactor = 1 + ($this->waste_percentage / 100);

        return $this->quantity * $wasteFactor;
    }

    public function calculateCost(): float
    {
        if (! $this->inventoryItem) {
            return 0;
        }

        return $this->getGrossQuantity() * $this->inventoryItem->cost_price;
    }

    public function getCostPerYield(): float
    {
        $costPerUnit = $this->calculateCost();

        if (! $this->recipe || $this->recipe->yield_qty <= 0) {
            return $costPerUnit;
        }

        return $costPerUnit / $this->recipe->yield_qty;
    }
}
```

### Key Fields

| Field | Description | Example |
|-------|-------------|---------|
| `quantity` | Quantity bersih yang dibutuhkan | 10.0000 kg |
| `waste_percentage` | Persentase waste (susut) | 5.00 (%) |
| `sort_order` | Urutan bahan | 1, 2, 3... |

### Waste Percentage Concept

**Waste percentage** adalah persentase kehilangan bahan saat preparation (misal: kupas, potong, dll).

**Contoh:**
- Recipe butuh: **10 kg** beras bersih
- Waste percentage: **5%** (beras pecah, kotoran)
- Gross quantity yang dibutuhkan: **10.5 kg** (termasuk waste)

### Helper Methods

#### getGrossQuantity(): float
Hitung gross quantity dengan memperhitungkan waste.

```php
// Quantity: 10 kg, Waste: 5%
$grossQty = $item->getGrossQuantity(); // 10.5 kg (10 × 1.05)
```

**Formula:**
```php
$grossQuantity = $quantity × (1 + $waste_percentage / 100)
```

#### calculateCost(): float
Hitung biaya bahan dengan gross quantity.

```php
// Gross Qty: 10.5 kg, Cost Price: 15,000/kg
$cost = $item->calculateCost(); // 157,500 (10.5 × 15,000)
```

**Formula:**
```php
$cost = $grossQuantity × $inventoryItem->cost_price
```

#### getCostPerYield(): float
Hitung kontribusi biaya ini per unit yield resep.

```php
// Item cost: 157,500, Recipe yield: 10 porsi
$costPerYield = $item->getCostPerYield(); // 15,750 per porsi
```

### Usage Examples

```php
// Example: Ayam untuk Nasi Goreng
RecipeItem::create([
    'recipe_id' => $recipe->id,
    'inventory_item_id' => $ayam->id,
    'unit_id' => $kg->id,
    'quantity' => 2.0000,        // Butuh 2 kg daging ayam bersih
    'waste_percentage' => 20.00, // 20% waste (tulang, kulit)
    'sort_order' => 3,
    'notes' => 'Daging dada saja',
]);

// Gross quantity yang dibutuhkan:
echo $item->getGrossQuantity(); // 2.4 kg (2 × 1.20)

// Biaya dengan gross quantity:
// 2.4 kg × 45,000/kg = 108,000
echo $item->calculateCost(); // 108,000

// Kontribusi biaya per porsi (10 porsi):
echo $item->getCostPerYield(); // 10,800 per porsi
```

### Complete Recipe Example

```php
// Recipe: Nasi Goreng Spesial (10 porsi)
$recipe = Recipe::create([
    'name' => 'Nasi Goreng Spesial',
    'yield_qty' => 10,
    'yield_unit_id' => $porsi->id,
]);

// Ingredients:
// 1. Nasi: 10 porsi, 0% waste
RecipeItem::create([
    'inventory_item_id' => $nasi->id,
    'quantity' => 10,
    'waste_percentage' => 0,
    'sort_order' => 1,
]);
// Gross: 10 porsi, Cost: 10 × 5,000 = 50,000

// 2. Telur: 12 butir, 5% waste
RecipeItem::create([
    'inventory_item_id' => $telur->id,
    'quantity' => 12,
    'waste_percentage' => 5,
    'sort_order' => 2,
]);
// Gross: 12.6 butir, Cost: 12.6 × 3,000 = 37,800

// 3. Ayam: 2 kg, 20% waste
RecipeItem::create([
    'inventory_item_id' => $ayam->id,
    'quantity' => 2,
    'waste_percentage' => 20,
    'sort_order' => 3,
]);
// Gross: 2.4 kg, Cost: 2.4 × 45,000 = 108,000

// 4. Bawang: 0.2 kg, 10% waste
RecipeItem::create([
    'inventory_item_id' => $bawang->id,
    'quantity' => 0.2,
    'waste_percentage' => 10,
    'sort_order' => 4,
]);
// Gross: 0.22 kg, Cost: 0.22 × 30,000 = 6,600

// Total estimated cost:
echo $recipe->calculateCost(); // 202,400

// Cost per porsi:
echo $recipe->getCostPerUnit(); // 20,240 per porsi
```

---

## Recipe Workflow Summary

### Creating Recipe Flow

```
1. Create Recipe
   ├─ Set name, description
   ├─ Set yield (quantity hasil)
   ├─ Set yield unit
   └─ Set time estimates (prep + cook)

2. Add Ingredients (RecipeItem)
   ├─ Select inventory item
   ├─ Enter quantity (net)
   ├─ Set waste percentage
   └─ Set sort order

3. Calculate Costs
   ├─ calculateCost() untuk total
   ├─ getCostPerUnit() per yield
   └─ Update estimated_cost

4. Version Management
   └─ Increment version untuk perubahan
```

### Waste Calculation Flow

```
1. Tentukan Net Quantity (quantity bersih yang dibutuhkan)
2. Tentukan Waste Percentage (persentase susut)
3. Hitung Gross Quantity:
   gross = net × (1 + waste% / 100)
4. Hitung Cost:
   cost = gross × cost_price
5. Hitung Cost per Yield:
   cost_per_yield = cost / recipe_yield_qty
```

### Example: Calculating Waste

**Scenario:** Membuat jus jeruk (10 gelas)

```
Net quantity needed: 10 gelas jeruk
Waste percentage: 20% (kulit, biji)

Gross quantity calculation:
gross = 10 × (1 + 20/100)
gross = 10 × 1.2
gross = 12 gelas

Cost calculation:
cost_price = 15,000/gelas
cost = 12 × 15,000 = 180,000

Cost per yield:
cost_per_gelas = 180,000 / 10 = 18,000/gelas
```

---

## Recipe & Inventory Integration

### When Product is Sold (Phase 3):

Jika produk yang menggunakan recipe dijual, sistem akan:

1. **Deduct Raw Materials:**
   ```php
   foreach ($recipe->items as $item) {
       $grossQty = $item->getGrossQuantity();

       // Deduct from inventory
       $stock = InventoryStock::where([
           'tenant_id' => $tenant->id,
           'outlet_id' => $outlet->id,
           'inventory_item_id' => $item->inventory_item_id,
       ])->first();

       $stock->decrement('quantity', $grossQty);

       // Create stock movement
       StockMovement::create([
           'type' => StockMovement::TYPE_OUT,
           'quantity' => $grossQty,
           'reference_type' => Order::class,
           'reference_id' => $order->id,
       ]);
   }
   ```

2. **Using FEFO (First Expired First Out):**
   ```php
   // Deduct from oldest batch first
   $batches = StockBatch::where('inventory_item_id', $item->inventory_item_id)
       ->where('remaining_qty', '>', 0)
       ->orderBy('expiry_date', 'asc')
       ->get();

   foreach ($batches as $batch) {
       // Deduct from batch until quantity is fulfilled
   }
   ```

---

## Recipe Best Practices

### 1. **Version Management**

Setiap ada perubahan recipe, increment version:

```php
// Update recipe
$recipe->update([
    'version' => $recipe->version + 1,
    'is_active' => true,
]);

// Deactivate old version if needed
```

### 2. **Accurate Waste Percentage**

Lakukan pengukuran waste yang akurat:

```
Waste % = (Gross - Net) / Gross × 100

Example:
- Beli: 10 kg ayam (gross)
- Bersih setelah dipotong: 8 kg (net)
- Waste: 2 kg (tulang, kulit)

Waste % = (10 - 8) / 10 × 100 = 20%
```

### 3. **Regular Cost Updates**

Update `estimated_cost` secara berkala:

```php
// Scheduler: Daily/Weekly
Recipe::where('is_active', true)->get()->each(function ($recipe) {
    $recipe->update([
        'estimated_cost' => $recipe->calculateCost(),
    ]);
});
```

### 4. **Sort Order untuk Instructions**

Gunakan `sort_order` untuk mengurutkan langkah memasak:

```php
// 1. Nasi (sort_order: 1) - base
// 2. Telur (sort_order: 2) - protein
// 3. Ayam (sort_order: 3) - topping
// 4. Bawang (sort_order: 4) - seasoning
```

---

## Next Steps

Lanjut ke dokumentasi berikutnya:
- [Phase 2: Models - Stock & Movement (Part 5)](./phase2-models-5-stock.md) - StockBatch, StockMovement

Kembali ke:
- [Phase 2: Models - Master Data (Part 1)](./phase2-models-1-master-data.md)
- [Phase 2: Models - Operations (Part 2)](./phase2-models-2-operations.md)
- [Phase 2: Models - Purchase Management (Part 3)](./phase2-models-3-purchase.md)
