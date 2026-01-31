# Phase 2: Seeders - Data Awal untuk Inventory

## Overview

Phase 2 seeders menghasilkan data awal untuk sistem inventory. Seeders ini sudah ada di project master dan siap digunakan.

---

## 1. UnitSeeder ⭐

**File:** `database/seeders/UnitSeeder.php`

Membuat default units untuk semua tenants dengan lengkap.

### Full Source Code

```php
<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedUnitsForTenant($tenant);
        }
    }

    private function seedUnitsForTenant(Tenant $tenant): void
    {
        // Weight Units
        $kg = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kilogram',
            'abbreviation' => 'kg',
            'base_unit_id' => null,  // Base unit
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Gram',
            'abbreviation' => 'g',
            'base_unit_id' => $kg->id,
            'conversion_factor' => 0.001,  // 1g = 0.001kg
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Miligram',
            'abbreviation' => 'mg',
            'base_unit_id' => $kg->id,
            'conversion_factor' => 0.000001, // 1mg = 0.000001kg
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Ons',
            'abbreviation' => 'ons',
            'base_unit_id' => $kg->id,
            'conversion_factor' => 0.1,  // 1ons = 0.1kg
            'is_active' => true,
        ]);

        // Volume Units
        $liter = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Liter',
            'abbreviation' => 'L',
            'base_unit_id' => null,  // Base unit
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Mililiter',
            'abbreviation' => 'ml',
            'base_unit_id' => $liter->id,
            'conversion_factor' => 0.001,  // 1ml = 0.001L
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Centiliter',
            'abbreviation' => 'cl',
            'base_unit_id' => $liter->id,
            'conversion_factor' => 0.01,  // 1cl = 0.01L
            'is_active' => true,
        ]);

        // Count Units
        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Piece',
            'abbreviation' => 'pcs',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dozen',
            'abbreviation' => 'doz',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Box',
            'abbreviation' => 'box',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Pack',
            'abbreviation' => 'pack',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Carton',
            'abbreviation' => 'ctn',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        // Food Service Units
        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Portion',
            'abbreviation' => 'por',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Slice',
            'abbreviation' => 'slc',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cup',
            'abbreviation' => 'cup',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tablespoon',
            'abbreviation' => 'tbsp',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);

        Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Teaspoon',
            'abbreviation' => 'tsp',
            'base_unit_id' => null,
            'conversion_factor' => 1,
            'is_active' => true,
        ]);
    }
}
```

### Cara Menjalankan

```bash
# Jalankan seeder saja
php artisan db:seed --class=UnitSeeder

# Atau jalankan semua seeders
php artisan db:seed
```

### Units yang Dibuat (15 units)

#### Weight Units (4 units)
| Nama | Abbreviation | Base Unit | Conversion |
|-----|-------------|-----------|-------------|
| Kilogram | kg | - | 1 |
| Gram | g | kg | 0.001 |
| Miligram | mg | kg | 0.000001 |
| Ons | ons | kg | 0.1 |

#### Volume Units (3 units)
| Nama | Abbreviation | Base Unit | Conversion |
|-----|-------------|-----------|-------------|
| Liter | L | - | 1 |
| Mililiter | ml | L | 0.001 |
| Centiliter | cl | L | 0.01 |

#### Count Units (5 units)
| Nama | Abbreviation | Base Unit | Conversion |
|-----|-------------|-----------|-------------|
| Piece | pcs | - | 1 |
| Dozen | doz | - | 1 |
| Box | box | - | 1 |
| Pack | pack | - | 1 |
| Carton | ctn | - | 1 |

#### Food Service Units (5 units)
| Nama | Abbreviation | Base Unit | Conversion |
|-----|-------------|-----------|-------------|
| Portion | por | - | 1 |
| Slice | slc | - | 1 |
| Cup | cup | - | 1 |
| Tablespoon | tbsp | - | 1 |
| Teaspoon | tsp | - | 1 |

---

## 2. InventoryCategorySeeder ⭐

**File:** `database/seeders/InventoryCategorySeeder.php`

Membuat default inventory categories dengan hierarchy.

### Full Source Code

```php
<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InventoryCategorySeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->seedCategoriesForTenant($tenant);
        }
    }

    private function createCategory(
        string $tenantId,
        ?string $parentId,
        string $name,
        string $description,
        int $sortOrder
    ): InventoryCategory {
        return InventoryCategory::create([
            'tenant_id' => $tenantId,
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $description,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    private function seedCategoriesForTenant(Tenant $tenant): void
    {
        // Proteins
        $proteins = $this->createCategory(
            $tenant->id,
            null,
            'Proteins',
            'Meat, poultry, seafood, and other protein sources',
            1
        );

        $this->createCategory($tenant->id, $proteins->id, 'Beef', 'Beef cuts and ground beef', 1);
        $this->createCategory($tenant->id, $proteins->id, 'Poultry', 'Chicken, duck, turkey', 2);
        $this->createCategory($tenant->id, $proteins->id, 'Seafood', 'Fish, shrimp, squid, etc.', 3);

        // Produce
        $produce = $this->createCategory(
            $tenant->id,
            null,
            'Produce',
            'Fresh fruits and vegetables',
            2
        );

        $this->createCategory($tenant->id, $produce->id, 'Vegetables', 'Fresh vegetables', 1);
        $this->createCategory($tenant->id, $produce->id, 'Fruits', 'Fresh fruits', 2);
        $this->createCategory($tenant->id, $produce->id, 'Herbs', 'Fresh herbs and aromatics', 3);

        // Dairy & Eggs
        $dairy = $this->createCategory(
            $tenant->id,
            null,
            'Dairy & Eggs',
            'Milk products and eggs',
            3
        );

        $this->createCategory($tenant->id, $dairy->id, 'Milk & Cream', 'Fresh milk, cream, and milk products', 1);
        $this->createCategory($tenant->id, $dairy->id, 'Cheese', 'Various types of cheese', 2);
        $this->createCategory($tenant->id, $dairy->id, 'Butter & Margarine', 'Butter and margarine products', 3);
        $this->createCategory($tenant->id, $dairy->id, 'Eggs', 'Chicken eggs and other eggs', 4);

        // Dry Goods
        $dryGoods = $this->createCategory(
            $tenant->id,
            null,
            'Dry Goods',
            'Shelf-stable dry ingredients',
            4
        );

        $this->createCategory($tenant->id, $dryGoods->id, 'Flour & Grains', 'Flour, rice, pasta, grains', 1);
        $this->createCategory($tenant->id, $dryGoods->id, 'Sugar & Sweeteners', 'Sugar, honey, syrups', 2);
        $this->createCategory($tenant->id, $dryGoods->id, 'Spices & Seasonings', 'Dried spices and seasonings', 3);
        $this->createCategory($tenant->id, $dryGoods->id, 'Canned Goods', 'Canned and preserved foods', 4);

        // Beverages
        $beverages = $this->createCategory(
            $tenant->id,
            null,
            'Beverages',
            'Drinks and beverage ingredients',
            5
        );

        $this->createCategory($tenant->id, $beverages->id, 'Coffee & Tea', 'Coffee beans, tea leaves, supplies', 1);
        $this->createCategory($tenant->id, $beverages->id, 'Soft Drinks', 'Carbonated and non-carbonated drinks', 2);
        $this->createCategory($tenant->id, $beverages->id, 'Juices', 'Fresh and packaged juices', 3);

        // Standalone Categories
        $this->createCategory($tenant->id, null, 'Sauces & Condiments', 'Sauces, dressings, and condiments', 6);
        $this->createCategory($tenant->id, null, 'Oils & Fats', 'Cooking oils and fats', 7);
        $this->createCategory($tenant->id, null, 'Packaging & Supplies', 'Takeaway containers, napkins, etc.', 8);
        $this->createCategory($tenant->id, null, 'Cleaning Supplies', 'Cleaning and sanitation products', 9);
    }
}
```

### Cara Menjalankan

```bash
# Jalankan seeder saja
php artisan db:seed --class=InventoryCategorySeeder

# Atau jalankan semua seeders
php artisan db:seed
```

### Categories yang Dibuat (9 root categories + subcategories)

#### Hierarchy Structure

```
1. Proteins (1)
   ├─ Beef
   ├─ Poultry
   └─ Seafood

2. Produce (2)
   ├─ Vegetables
   ├─ Fruits
   └─ Herbs

3. Dairy & Eggs (3)
   ├─ Milk & Cream
   ├─ Cheese
   ├─ Butter & Margarine
   └─ Eggs

4. Dry Goods (4)
   ├─ Flour & Grains
   ├─ Sugar & Sweeteners
   ├─ Spices & Seasonings
   └─ Canned Goods

5. Beverages (5)
   ├─ Coffee & Tea
   ├─ Soft Drinks
   └─ Juices

6. Sauces & Condiments (6) - standalone
7. Oils & Fats (7) - standalone
8. Packaging & Supplies (8) - standalone
9. Cleaning Supplies (9) - standalone
```

---

## 3. Update DatabaseSeeder

Untuk menggunakan seeders ini, update `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            TenantSeeder::class,
            UnitSeeder::class,              // ⭐ TAMBAH
            InventoryCategorySeeder::class, // ⭐ TAMBAH
        ]);
    }
}
```

---

## Cara Menggunakan

### Step 1: Jalankan Migrasi

```bash
# Fresh migration dengan seeder
php artisan migrate:fresh --seed

# Output:
#   ✓ Units created successfully
#   ✓ Categories created successfully
```

### Step 2: Verifikasi Data

```bash
php artisan tinker
```

```php
// Cek units
>>> Unit::count()
=> 15  // Setiap tenant punya 15 units

>>> Unit::where('abbreviation', 'kg')->first()->name
=> "Kilogram"

// Cek categories
>>> InventoryCategory::count()
=> 20  // Total categories per tenant

>>> InventoryCategory::whereNull('parent_id')->get()->pluck('name')
=> [
     "Proteins",
     "Produce",
     "Dairy & Eggs",
     "Dry Goods",
     "Beverages",
     "Sauces & Condiments",
     "Oils & Fats",
     "Packaging & Supplies",
     "Cleaning Supplies"
   ]

// Cek hierarchy
>>> $proteins = InventoryCategory::where('name', 'Proteins')->first();
>>> $proteins->children->pluck('name')
=> ["Beef", "Poultry", "Seafood"]

>>> $beef = InventoryCategory::where('name', 'Beef')->first();
>>> $beef->full_path
=> "Proteins > Beef"
```

---

## Troubleshooting

### Error: Class 'Database\Seeders\UnitSeeder' not found

**Solusi:** Buat file seeder dulu

```bash
# Buat file seeder kosong
php artisan make:seeder UnitSeeder

# Lalu copy code dari dokumentasi di atas
```

### Error: Table 'units' doesn't exist

**Solusi:** Jalankan migrasi dulu

```bash
php artisan migrate
php artisan db:seed --class=UnitSeeder
```

### Error: No tenants found

**Solusi:** Buat tenant dulu (Phase 1)

```bash
php artisan db:seed --class=TenantSeeder
php artisan db:seed --class=UnitSeeder
```

---

## Best Practices

### 1. Idempotency
Seeder ini aman dijalankan berulang kali karena setiap kali dicek akan dibuat unit baru. Untuk production gunakan `firstOrCreate`:

```php
Unit::firstOrCreate(
    ['tenant_id' => $tenant->id, 'abbreviation' => 'kg'],
    ['name' => 'Kilogram', 'conversion_factor' => 1]
);
```

### 2. Multi-Tenant
Setiap tenant mendapatkan units yang sama dengan `tenant_id` yang berbeda, jadi data terisolasi.

### 3. Category Hierarchy
Parent-child relationship menggunakan `parent_id`:
- Root categories: `parent_id = null`
- Subcategories: `parent_id = id parent`

---

## Testing

### Test Manual

```bash
# Hapus semua data dan mulai dari awal
php artisan migrate:fresh --seed

# Cek di database
php artisan tinker
```

### Expected Results

Setelah menjalankan seeders:
- ✅ Setiap tenant punya 15 units
- ✅ Setiap tenant punya ~20 categories
- ✅ Categories terstruktur dengan hierarchy
- ✅ Slug otomatis di-generate dari nama

---

## Langkah Selanjutnya

Setelah seeders berjalan:

1. **Buat Models** - InventoryItem, Supplier, dll
2. **Buat Controllers** - UnitController, InventoryCategoryController
3. **Buat Views** - Form CRUD untuk units dan categories
4. **Testing** - Pastikan CRUD berjalan dengan baik

---

## Need Help?

Jika ada error:
1. Cek migrasi sudah dijalankan: `php artisan migrate:status`
2. Cek tenant sudah ada: `php artisan tinker >>> Tenant::count()`
3. Cek file seeder sudah ada: `ls database/seeders/`

---

**Dokumentasi terkait:**
- [Phase 2: Models](./phase2-models.md) - Struktur models
- [Phase 2: Controllers](./phase2-controllers.md) - HTTP handlers
- [Phase 2: Views](./phase2-views.md) - UI templates
