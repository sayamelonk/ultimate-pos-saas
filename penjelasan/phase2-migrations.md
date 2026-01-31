# Phase 2: Database Migrations - Inventory, Stock & Recipe Management

## Overview

Phase 2 mendefinisikan struktur database untuk sistem inventory, manajemen stok, dan recipe yang menjadi fondasi dari sistem POS F&B. Migrasi ini dirancang untuk mendukung multi-tenant, multi-outlet dengan tracking batch dan expiry.

---

## Migration Files

### 1. Units Table
**File:** `2026_01_27_121226_create_units_table.php`

Menyimpan data satuan unit untuk inventory items.

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('tenant_id')->nullable();
$table->string('name', 50);                  // Nama unit: "Kilogram", "Gram"
$table->string('abbreviation', 10);          // Singkatan: "kg", "g"
$table->string('type')->default('weight');   // weight, volume, piece, length
$table->uuid('base_unit_id')->nullable();    // Untuk konversi (g -> kg)
$table->decimal('conversion_factor', 12, 6)->default(1); // Faktor konversi
$table->boolean('is_active')->default(true);
$table->timestamps();
```

#### Relationships:
- `tenant_id` → `tenants.id` (cascade)
- `base_unit_id` → `units.id` (set null) - self-referencing untuk konversi unit

#### Indexes:
- `['tenant_id', 'type']` - Filter by tipe unit
- `['tenant_id', 'is_active']` - Filter active units

#### Use Cases:
- **Weight**: kg, g, oz, lb
- **Volume**: L, ml, gal
- **Piece**: pcs, dozen, pack
- **Length**: m, cm, inch

---

### 2. Suppliers Table
**File:** `2026_01_27_121346_create_suppliers_table.php`

Menyimpan data supplier untuk pembelian inventory.

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('tenant_id');
$table->string('code', 20);                  // Kode supplier unik per tenant
$table->string('name');                      // Nama supplier
$table->string('contact_person')->nullable();
$table->string('email')->nullable();
$table->string('phone', 20)->nullable();
$table->string('mobile', 20)->nullable();
$table->text('address')->nullable();
$table->string('city')->nullable();
$table->string('tax_number')->nullable();    // NPWP
$table->string('bank_name')->nullable();
$table->string('bank_account')->nullable();
$table->string('bank_account_name')->nullable();
$table->integer('payment_terms')->default(0); // Hari (NET 30, NET 60)
$table->text('notes')->nullable();
$table->boolean('is_active')->default(true);
$table->timestamps();
```

#### Relationships:
- `tenant_id` → `tenants.id` (cascade)

#### Constraints:
- Unique: `['tenant_id', 'code']`

#### Indexes:
- `['tenant_id', 'is_active']`
- `['tenant_id', 'name']`

---

### 3. Inventory Categories Table
**File:** `2026_01_14_033637_create_inventory_categories_table.php`

Kategorisasi inventory items dengan support untuk hierarchy (parent-child).

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('tenant_id');
$table->uuid('parent_id')->nullable();    // Untuk hierarchy (category dalam category)
$table->string('name');
$table->string('slug');                   // URL-friendly identifier
$table->text('description')->nullable();
$table->integer('sort_order')->default(0);
$table->boolean('is_active')->default(true);
$table->timestamps();
```

#### Relationships:
- `tenant_id` → `tenants.id` (cascade)
- `parent_id` → `inventory_categories.id` (set null) - self-referencing untuk hierarchy

#### Constraints:
- Unique: `['tenant_id', 'slug']`

#### Indexes:
- `['tenant_id', 'is_active']` - Filter active categories
- `['tenant_id', 'parent_id']` - Query child categories

#### Use Cases:
- **Hierarchy**: Proteins → Beef, Chicken, Seafood
- **Slug**: Untuk URL routing dan SEO-friendly URLs
- **Sort Order**: Urutan tampil di UI

---

### 4. Inventory Items Table
**File:** `2026_01_27_121522_create_inventory_items_table.php`

Master data untuk semua item inventory.

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('tenant_id');
$table->uuid('category_id')->nullable();
$table->uuid('unit_id');                     // Unit stok utama
$table->uuid('purchase_unit_id')->nullable(); // Unit pembelian (carton)
$table->string('sku', 50);                   // Stock Keeping Unit
$table->string('barcode', 50)->nullable();
$table->string('name');
$table->text('description')->nullable();
$table->string('image')->nullable();
$table->decimal('purchase_unit_conversion', 12, 4)->default(1); // 1 carton = 24 pcs
$table->decimal('cost_price', 15, 2)->default(0); // Harga pokok rata-rata
$table->decimal('min_stock', 12, 4)->default(0);  // Minimum stok
$table->decimal('max_stock', 12, 4)->nullable();  // Maksimum stok
$table->decimal('reorder_point', 12, 4)->default(0);  // Titik order
$table->decimal('reorder_qty', 12, 4)->default(0);    // Qty reorder
$table->integer('shelf_life_days')->nullable(); // Umur simpan (hari)
$table->boolean('track_batches')->default(false); // Track batch/lot
$table->boolean('is_active')->default(true);
$table->timestamps();
```

#### Relationships:
- `tenant_id` → `tenants.id` (cascade)
- `category_id` → `inventory_categories.id` (set null)
- `unit_id` → `units.id` (restrict) - tidak boleh dihapus jika dipakai
- `purchase_unit_id` → `units.id` (set null)

#### Constraints:
- Unique: `['tenant_id', 'sku']`

#### Indexes:
- `['tenant_id', 'barcode']` - Lookup by barcode
- `['tenant_id', 'category_id']` - Filter by category
- `['tenant_id', 'is_active']` - Active items only
- `['tenant_id', 'name']` - Search by name

#### Business Logic:
- **Dual Unit System**: `unit_id` untuk stok, `purchase_unit_id` untuk pembelian
- **Conversion Factor**: Berapa unit stok dalam 1 unit pembelian
  - Contoh: Beli per carton, stok per pcs → conversion_factor = 24
- **Reorder Point**: Trigger untuk purchase order otomatis
- **Batch Tracking**: Untuk item dengan expiry (FEFO - First Expiry First Out)

---

### 5. Supplier Items Table
**File:** `2026_01_27_121540_create_supplier_items_table.php`

Link antara supplier dan inventory item (banyak ke banyak dengan data tambahan).

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('supplier_id');
$table->uuid('inventory_item_id');
$table->string('supplier_sku', 50)->nullable(); // SKU di sisi supplier
$table->string('supplier_item_name')->nullable();
$table->decimal('supplier_price', 15, 2)->default(0);
$table->integer('lead_time_days')->default(0);   // Waktu pengiriman (hari)
$table->integer('min_order_qty')->default(1);    // Minimum order quantity
$table->boolean('is_preferred')->default(false); // Supplier utama
$table->timestamps();
```

#### Relationships:
- `supplier_id` → `suppliers.id` (cascade)
- `inventory_item_id` → `inventory_items.id` (cascade)

#### Constraints:
- Unique: `['supplier_id', 'inventory_item_id']`

#### Use Cases:
- Satu item bisa disuplai oleh banyak supplier
- Tandai supplier utama dengan `is_preferred = true`
- Track harga per supplier untuk negosiasi

---

### 6. Inventory Stocks Table
**File:** `2026_01_27_121558_create_inventory_stocks_table.php`

Tabel ini menyimpan quantity stok per outlet per item. **Penttng**: Satu record per combination outlet + item.

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('outlet_id');
$table->uuid('inventory_item_id');
$table->decimal('quantity', 15, 4)->default(0);       // Quantity on hand
$table->decimal('reserved_qty', 15, 4)->default(0);   // Reserved untuk orders
$table->decimal('avg_cost', 15, 2)->default(0);       // Weighted average cost
$table->decimal('last_cost', 15, 2)->default(0);      // Last purchase cost
$table->timestamp('last_received_at')->nullable();
$table->timestamp('last_issued_at')->nullable();
$table->timestamps();
```

#### Relationships:
- `outlet_id` → `outlets.id` (cascade)
- `inventory_item_id` → `inventory_items.id` (cascade)

#### Constraints:
- Unique: `['outlet_id', 'inventory_item_id']`

#### Indexes:
- `['outlet_id', 'quantity']` - Query low stock

#### Business Logic:
- **Available Quantity** = `quantity` - `reserved_qty`
- **Weighted Average Cost** dihitung saat goods receive:
  ```
  New Avg Cost = ((Old Qty × Old Cost) + (New Qty × New Cost)) / (Old Qty + New Qty)
  ```
- Setiap perubahan stok HARUS melalui tabel ini, jangan langsung update

---

### 7. Stock Batches Table
**File:** `2026_01_14_033644_create_stock_batches_table.php`

Track batch/lot untuk item dengan expiry atau lot number. Opsional, hanya jika `inventory_items.track_batches = true`.

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('outlet_id');
$table->uuid('inventory_item_id');
$table->string('batch_number', 50);
$table->date('production_date')->nullable();
$table->date('expiry_date')->nullable();
$table->decimal('initial_qty', 15, 4);
$table->decimal('current_qty', 15, 4);
$table->decimal('cost_price', 15, 2)->default(0); // Cost untuk batch ini
$table->uuid('goods_receive_item_id')->nullable(); // Reference ke GR
$table->string('status')->default('available');    // available, depleted, expired, disposed
$table->timestamps();
```

#### Relationships:
- `outlet_id` → `outlets.id` (cascade)
- `inventory_item_id` → `inventory_items.id` (cascade)
- `goods_receive_item_id` → `goods_receive_items.id` (set null)

#### Constraints:
- Unique: `['outlet_id', 'inventory_item_id', 'batch_number']`

#### Indexes:
- `['outlet_id', 'inventory_item_id', 'status']` - Filter available batches
- `['outlet_id', 'expiry_date']` - Expiry alerts

#### Use Cases:
- FEFO (First Expiry First Out) untuk item perishable
- Track recall batch jika ada masalah kualitas
- Compliance untuk industri regulated (makanan, farmasi)

---

### 8. Stock Movements Table
**File:** `2026_01_27_121634_create_stock_movements_table.php**

Audit trail untuk SEMUA pergerakan stok. Setiap in/out harus record di sini.

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('outlet_id');
$table->uuid('inventory_item_id');
$table->uuid('batch_id')->nullable();
$table->string('type'); // in, out, adjustment, transfer_in, transfer_out, waste
$table->string('reference_type')->nullable(); // goods_receive, order, adjustment, transfer, waste
$table->uuid('reference_id')->nullable();
$table->decimal('quantity', 15, 4); // Positive untuk IN, negative untuk OUT
$table->decimal('cost_price', 15, 2)->default(0);
$table->decimal('stock_before', 15, 4);
$table->decimal('stock_after', 15, 4);
$table->text('notes')->nullable();
$table->uuid('created_by')->nullable();
$table->timestamps();
```

#### Relationships:
- `outlet_id` → `outlets.id` (cascade)
- `inventory_item_id` → `inventory_items.id` (cascade)
- `batch_id` → `stock_batches.id` (set null)
- `created_by` → `users.id` (set null)

#### Indexes:
- `['outlet_id', 'inventory_item_id', 'created_at']` - History per item
- `['outlet_id', 'type', 'created_at']` - Filter by movement type
- `['reference_type', 'reference_id']` - Lookup by reference

#### Movement Types:
- **in**: Stock masuk (purchase, return dari customer)
- **out**: Stock keluar (penjualan, usage)
- **adjustment**: Koreksi stok (stock take, correction)
- **transfer_in**: Terima dari outlet lain
- **transfer_out**: Kirim ke outlet lain
- **waste**: Waste / spoilage

#### Best Practice:
- **JANGAN** langsung update `inventory_stocks.quantity`
- **HARUS** gunakan `StockService` yang akan create movement record
- Movement records ini digunakan untuk audit, reporting, dan troubleshooting

---

### 9. Recipes Table
**File:** `2026_01_14_033650_create_recipes_table.php`

Master recipe untuk produk menu. Recipe adalah definisi bahan-bahan yang diperlukan untuk membuat satu produk.

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('tenant_id');
$table->uuid('product_id')->nullable();            // Links ke menu product (FK di Phase 3)
$table->string('name');
$table->text('description')->nullable();
$table->text('instructions')->nullable();          // Preparation instructions
$table->decimal('yield_qty', 12, 4)->default(1);  // Output quantity
$table->uuid('yield_unit_id');                    // Output unit
$table->decimal('estimated_cost', 15, 2)->default(0); // Calculated dari items
$table->integer('prep_time_minutes')->nullable();  // Prep time
$table->integer('cook_time_minutes')->nullable(); // Cooking time
$table->string('version', 20)->default('1.0');     // Recipe version
$table->boolean('is_active')->default(true);
$table->timestamps();
```

#### Relationships:
- `tenant_id` → `tenants.id` (cascade)
- `product_id` → `products.id` (set null) - akan di-add di Phase 3
- `yield_unit_id` → `units.id` (restrict)

#### Indexes:
- `['tenant_id', 'product_id']` - Product link
- `['tenant_id', 'is_active']` - Filter active recipes

#### Use Cases:
- Recipe untuk nasi goreng: nasi, telur, kecap, bawang, dll
- Recipe untuk kopi: espresso, susu, gula
- Recipe bisa untuk 1 porsi atau batch (misal 10 porsi)
- Version control untuk recipe modifications

---

### 10. Recipe Items Table
**File:** `2026_01_14_033651_create_recipe_items_table.php`

Detail item yang diperlukan untuk sebuah recipe.

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('recipe_id');
$table->uuid('inventory_item_id');
$table->decimal('quantity', 12, 4);               // Amount needed per yield
$table->uuid('unit_id');                          // Unit untuk ingredient ini
$table->decimal('waste_percentage', 5, 2)->default(0); // Expected waste %
$table->text('notes')->nullable();                // Preparation notes
$table->integer('sort_order')->default(0);
$table->timestamps();
```

#### Relationships:
- `recipe_id` → `recipes.id` (cascade)
- `inventory_item_id` → `inventory_items.id` (cascade)
- `unit_id` → `units.id` (restrict)

#### Constraints:
- Unique: `['recipe_id', 'inventory_item_id']`

#### Indexes:
- `['recipe_id', 'sort_order']` - Display order

#### Business Logic:
- **Effective Cost** = `quantity` × `cost_price` × (1 + `waste_percentage`/100)
- **Waste Percentage**: Untuk account prep loss (misal kulit buah, tulang)
- **Notes**: Instruksi khusus untuk ingredient ini
- Contoh: Recipe Burger
  - Beef Patty: 150g
  - Bun: 1 pcs
  - Cheese: 1 slice
  - Lettuce: 20g

---

### 11. Purchase Orders Table
**File:** `2026_01_27_121750_create_purchase_orders_table.php`

Purchase Order (PO) ke supplier.

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('tenant_id');
$table->uuid('outlet_id');
$table->uuid('supplier_id');
$table->string('order_number', 50);             // PO-2025-001
$table->date('order_date');
$table->date('expected_date')->nullable();      // Expected delivery date
$table->decimal('subtotal', 15, 2)->default(0);
$table->decimal('tax_amount', 15, 2)->default(0);
$table->decimal('discount_amount', 15, 2)->default(0);
$table->decimal('total_amount', 15, 2)->default(0);
$table->string('status')->default('draft');     // draft, sent, confirmed, received, cancelled
$table->text('notes')->nullable();
$table->uuid('created_by')->nullable();
$table->timestamps();
```

#### Relationships:
- `tenant_id` → `tenants.id` (cascade)
- `outlet_id` → `outlets.id` (cascade)
- `supplier_id` → `suppliers.id` (cascade)
- `created_by` → `users.id` (set null)

#### Status Workflow:
1. **draft**: PO dibuat, belum dikirim
2. **sent**: PO dikirim ke supplier
3. **confirmed**: Supplier konfirmasi
4. **received**: Barang diterima (goods receive)
5. **cancelled**: PO dibatalkan

---

### 12. Purchase Order Items Table
**File:** `2026_01_27_121814_create_purchase_order_items_table.php`

Detail item dalam Purchase Order.

#### Schema:
```php
$table->uuid('id')->primary();
$table->uuid('purchase_order_id');
$table->uuid('inventory_item_id');
$table->decimal('quantity', 12, 4);             // Quantity yang dipesan
$table->uuid('unit_id');
$table->decimal('unit_price', 15, 2)->default(0);
$table->decimal('discount_percentage', 5, 2)->default(0);
$table->decimal('tax_percentage', 5, 2)->default(0);
$table->decimal('subtotal', 15, 2)->default(0);
$table->decimal('received_qty', 12, 4)->default(0); // Quantity yang sudah diterima
$table->timestamps();
```

#### Relationships:
- `purchase_order_id` → `purchase_orders.id` (cascade)
- `inventory_item_id` → `inventory_items.id` (cascade)
- `unit_id` → `units.id` (cascade)

#### Business Logic:
- `subtotal` = `quantity` × `unit_price` × (1 - `discount_percentage`/100)
- `received_qty` diupdate saat goods receive
- Bisa partial receive (bisa beberapa kali)

---

## Database Diagram (ERD)

```
┌─────────────┐     ┌──────────────────┐     ┌─────────────┐
│   tenants   │────▶│ inventory_items  │◀────│  categories │
└─────────────┘     └──────────────────┘     └─────────────┘
                           │
                           │ has many
                           ▼
                    ┌──────────────┐
                    │inventory_stocks│
                    └──────────────┘
                           │
                           │ tracks movements
                           ▼
                    ┌────────────────┐
                    │stock_movements │
                    └────────────────┘

┌─────────────┐     ┌──────────────────┐     ┌─────────────┐
│  suppliers  │────▶│ supplier_items   │◀────│ inventory   │
└─────────────┘     └──────────────────┘     │   _items    │
                                             └─────────────┘
                                                   │
                                                   │ used in
                                                   ▼
                                            ┌─────────────┐
                                            │recipe_items │
                                            └─────────────┘
                                                   ▲
                                                   │
                                                   │
                                            ┌─────────────┐
                                            │  recipes    │
                                            └─────────────┘

┌─────────────┐     ┌──────────────────┐     ┌─────────────┐
│  outlets    │────▶│inventory_stocks  │◀──── │ stock_batches│
└─────────────┘     └──────────────────┘     └─────────────┘
                           │
                           │
                           ▼
                    ┌────────────────┐
                    │stock_movements │
                    └────────────────┘

┌─────────────┐     ┌──────────────────┐     ┌─────────────┐
│  suppliers  │────▶│purchase_orders   │────▶│purchase_    │
└─────────────┘     └──────────────────┘     │order_items  │
                           │                   └─────────────┘
                           │
                           │ received via
                           ▼
                    ┌──────────────────┐
                    │goods_receives    │ (future)
                    └──────────────────┘
```

---

## Key Design Decisions

### 1. UUID vs Auto-Increment ID
Semua table menggunakan UUID untuk:
- Security: Tidak mudah ditebak
- Distributed systems: Mudah merge data dari berbagai source
- Multi-tenancy: Isolation yang lebih baik

### 2. Dual Unit System
Inventory item punya 2 unit:
- **unit_id**: Unit untuk stok dan recipe
- **purchase_unit_id**: Unit untuk pembelian (lebih besar)

Contoh:
- Beli: carton (24 pcs)
- Stok: pcs
- Recipe: pcs atau gram

### 3. Weighted Average Cost
Menggunakan weighted average cost alih-alih FIFO/FEFO di level item:
- Lebih sederhana
- Stabil meskipun harga fluktuatif
- Batch tracking opsional untuk industri yang membutuhkan FEFO

### 4. Separate Stock Movement Table
Movement terpisah dari stock:
- Audit trail lengkap
- Reporting yang lebih mudah
- Reversible operations

### 5. Tenant & Outlet Scoping
Semua data di-scope oleh tenant dan outlet:
- Multi-tenancy yang aman
- Data isolation yang jelas
- Performance yang baik dengan proper indexing

---

## Best Practices

### 1. Migration Dependencies
Pastikan migrasi dijalankan dalam urutan yang benar:
1. Units (independent)
2. Suppliers (butuh tenants)
3. Categories (butuh tenants)
4. Inventory Items (butuh units, categories)
5. Supplier Items (butuh suppliers, inventory_items)
6. Inventory Stocks (butuh outlets, inventory_items)
7. Stock Batches (butuh outlets, inventory_items)
8. Stock Movements (butuh semua di atas)
9. Recipes (butuh tenants)
10. Recipe Items (butuh recipes, inventory_items)
11. Purchase Orders (butuh suppliers, outlets)
12. Purchase Order Items (butuh purchase_orders, inventory_items)

### 2. Indexing Strategy
Index compound untuk query umum:
- Filter + sort: `['tenant_id', 'is_active']`
- Foreign key lookup: `['outlet_id', 'inventory_item_id']`
- Reporting: `['outlet_id', 'type', 'created_at']`

### 3. Data Integrity
Gunakan foreign key constraints:
- `cascade`: Untuk child yang memang harus ikut terhapus
- `restrict`: Untuk reference yang penting (unit)
- `set null`: Untuk optional reference

---

## Next Steps

Setelah migrasi dijalankan:
1. Jalankan `php artisan migrate` untuk membuat semua tabel
2. Buat seeders untuk data awal (units, suppliers, categories)
3. Buat models dengan relationships yang proper
4. Buat services untuk business logic (StockService, RecipeService)
5. Implement controllers dan views

Lihat dokumentasi berikutnya:
- [Phase 2: Models & Relationships](./phase2-models.md)
- [Phase 2: Seeders](./phase2-seeders.md)
