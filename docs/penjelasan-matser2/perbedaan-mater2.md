# Perbedaan Master-2 dengan Project Current

## Ringkasan

**Master-2** adalah versi yang jauh lebih lengkap dibanding **Current Project**. Master-2 sudah mencakup fitur POS yang lebih lengkap dengan product management, modifiers, combos, dan fitur keamanan tambahan.

## Statistik Perbandingan

| Komponen | Current Project | Master-2 | Selisih |
|----------|----------------|----------|---------|
| **Models** | 38 | 52 | +14 |
| **Controllers** | 27 | 39 | +12 |
| **Migrations** | ~35 | ~65 | +30 |
| **Factories** | 6 | 14 | +8 |

## Fitur Baru di Master-2

### 1. **Product Management** (Sistem Baru)

Master-2 memperkenalkan sistem product yang lebih lengkap:

**Models Baru:**
- `Product` - Produk utama
- `ProductCategory` - Kategori produk
- `ProductVariant` - Variant produk (size, warna, dll)
- `ProductOutlet` - Relasi produk dengan outlet

**Variant System:**
- `VariantGroup` - Grup variant (contoh: Size)
- `VariantOption` - Opsi variant (contoh: S, M, L, XL)

### 2. **Modifier & Combo System**

**Modifiers:**
- `Modifier` - Modifier item (topping, extra)
- `ModifierGroup` - Grup modifier
- `ProductModifierGroup` - Relasi produk dengan modifier

**Combos:**
- `Combo` - Paket combo
- `ComboItem` - Item dalam combo

### 3. **Fitur Keamanan & Authorization**

**Security:**
- `UserPin` - PIN untuk user
- `PinAttempt` - Log percobaan PIN
- `AuthorizationLog` - Log authorization
- `AuthorizationSetting` - Setting authorization

**Controllers:**
- `UserPinController` - Management PIN user
- `AuthorizationController` - Controller authorization

### 4. **Cash Drawer & Held Orders**

**Cash Management:**
- `CashDrawerLog` - Log cash drawer
- `CashDrawerController` - Controller cash drawer

**Order Management:**
- `HeldOrder` - Order ditahan sementara
- `HeldOrderController` - Controller held order

### 5. **Stock Batch Management**

**Inventory Advanced:**
- `StockBatchMovement` - Movement per batch
- `StockBatchController` - Controller stock batch
- `BatchSetting` - Setting batch
- `BatchSettingController` - Controller setting batch

### 6. **Menu Management**

**Services:**
- `Menu/` - Folder service untuk menu management

**Controllers:**
- `Menu/` - Folder controller untuk menu

### 7. **Localization Support**

**Features:**
- Middleware `SetLocale` - Set bahasa user
- Controller `LocaleController` - Controller locale
- Field `locale` di table users

## Migrations Baru di Master-2

### Product System (2026-01-15)
```
2026_01_15_140716_create_product_categories_table.php
2026_01_15_140717_create_products_table.php
2026_01_15_140718_create_variant_groups_table.php
2026_01_15_140719_create_variant_options_table.php
2026_01_15_140720_create_product_variants_table.php
2026_01_15_140721_create_modifier_groups_table.php
2026_01_15_140722_create_modifiers_table.php
2026_01_15_140723_create_product_modifier_groups_table.php
2026_01_15_140724_create_combos_table.php
2026_01_15_140725_create_combo_items_table.php
2026_01_15_140726_create_product_outlets_table.php
```

### Cash Drawer & Authorization (2026-01-20)
```
2026_01_20_133254_create_held_orders_table.php
2026_01_20_133255_create_cash_drawer_logs_table.php
2026_01_20_143639_create_authorization_tables.php
```

### Database Improvements (2026-01-21)
```
2026_01_21_141758_add_code_to_inventory_categories_table.php
2026_01_21_143526_add_type_and_storage_fields_to_inventory_items_table.php
2026_01_21_145246_add_locale_to_users_table.php
```

## Factories Baru di Master-2

Master-2 menambahkan factories baru:
- `CustomerFactory.php`
- `InventoryCategoryFactory.php`
- `InventoryItemFactory.php`
- `InventoryStockFactory.php`
- `PaymentMethodFactory.php`
- `PriceFactory.php`
- `SupplierFactory.php`
- `SupplierItemFactory.php`
- `UnitFactory.php`

## Routes Tambahan

Master-2 memiliki routes yang lebih lengkap:
- Routes untuk product management
- Routes untuk variant & modifier
- Routes untuk combo system
- Routes untuk authorization
- Routes untuk held orders
- Routes untuk cash drawer
- Routes untuk menu management
- Routes untuk PIN management

## Views Baru di Master-2

### Admin Views
- `admin/authorization/` - Authorization management
- `admin/users/pin.blade.php` - Management PIN
- `admin/users/pin-self.blade.php` - Set PIN sendiri

### Components
- `components/form-group.blade.php` - Form group component
- `components/pin-modal.blade.php` - Modal PIN

### Inventory Views
- `inventory/batches/` - Stock batch management

### Menu Views
- `menu/` - Menu management views

## Composer.json Differences

Master-2 menambahkan dependency:
```json
"phpunit/php-code-coverage": "^11.0"
```

Dan update script dev untuk queue:
```json
"php artisan queue:listen --tries=1 --timeout=0"
```

## Kesimpulan & Rekomendasi

### Apa yang Perlu Dilakukan?

1. **Migrasi Product System**
   - Copy semua migrations dari 2026-01-15
   - Copy models: Product, ProductCategory, ProductVariant, ProductOutlet
   - Copy variant system: VariantGroup, VariantOption

2. **Migrasi Modifier & Combo**
   - Copy models: Modifier, ModifierGroup, Combo, ComboItem
   - Copy migration files terkait
   - Copy controllers dan views

3. **Migrasi Security Features**
   - Copy models: UserPin, PinAttempt, AuthorizationLog, AuthorizationSetting
   - Copy UserPinController dan AuthorizationController
   - Copy views untuk PIN dan authorization

4. **Migrasi Cash Drawer & Held Orders**
   - Copy models: CashDrawerLog, HeldOrder
   - Copy controllers terkait
   - Copy views terkait

5. **Migrasi Stock Batch Advanced**
   - Copy model: StockBatchMovement
   - Copy BatchSetting model dan controller
   - Copy views untuk batch management

6. **Migrasi Menu System**
   - Copy Menu services dan controllers
   - Copy menu views

7. **Migrasi Localization**
   - Copy SetLocale middleware
   - Copy LocaleController
   - Add locale field ke users table

### Strategi Migrasi

**Opsi 1: Copy Manual Bertahap**
- Copy file per kategori (models, controllers, views)
- Test setiap kategori sebelum lanjut
- Lebih aman tapi lebih lama

**Opsi 2: Replace Seluruh Project**
- Copy semua file dari master-2
- Sesuaikan config dan environment
- Lebih cepat tapi perlu testing menyeluruh

**Opsi 3: Hybrid (Recommended)**
- Copy database structure (migrations)
- Copy models dan services
- Pelan-pelan copy controllers dan views
- Test per feature

### Priority Order

1. **HIGH PRIORITY** (Core Features)
   - Product System
   - Variant System
   - Modifier & Combo

2. **MEDIUM PRIORITY** (Security & UX)
   - User PIN & Authorization
   - Held Orders
   - Cash Drawer

3. **LOW PRIORITY** (Enhancements)
   - Menu Management
   - Localization
   - Stock Batch Advanced

## Catatan Penting

- Master-2 menggunakan format migration `0001_01_01_` untuk core tables
- Current menggunakan format `2026_01_22_` untuk semua migrations
- Perlu hati-hati saat merge migration untuk konflik
- Factory di Master-2 lebih lengkap, sebaiknya di-copy semua
- Composer dependencies perlu di-sync
