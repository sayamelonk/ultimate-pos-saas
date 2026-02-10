# Panduan Migrasi Master-2 ke Current Project
## Panduan Langkah-demi-Langkah Tanpa Bantuan AI

---

## 📋 DAFTAR ISI

1. [Persiapan Awal](#1-persiapan-awal)
2. [Backup Project](#2-backup-project)
3. [Migrasi Database (Migrations)](#3-migrasi-database-migrations)
4. [Migrasi Models](#4-migrasi-models)
5. [Migrasi Factories](#5-migrasi-factories)
6. [Migrasi Controllers](#6-migrasi-controllers)
7. [Migrasi Services](#7-migrasi-services)
8. [Migrasi Middleware](#8-migrasi-middleware)
9. [Migrasi Views](#9-migrasi-views)
10. [Update Routes](#10-update-routes)
11. [Update Dependencies](#11-update-dependencies)
12. [Testing & Verifikasi](#12-testing--verifikasi)
13. [Troubleshooting](#13-troubleshooting)

---

## 1. PERSIAPAN AWAL

### 1.1. Pastikan Anda di Branch yang Tepat

```bash
# Cek branch saat ini
git branch

# Jika belum di future/master-2, pindah ke branch tersebut
git checkout future/master-2

# Pastikan working directory clean
git status
```

**Expected Output:** `nothing to commit, working tree clean`

### 1.2. Siapkan Path ke Master-2

```bash
# Set variable untuk path master-2 (macOS/Linux)
export MASTER2_PATH="/Users/rikihikmianto/FlutterProjects/POS SaaS Multi Merchant - Bangun Dari Nol hingga Rilis di Play Store/web/2-ultimate-pos-saas-master"

# Verifikasi path exists
ls -la "$MASTER2_PATH"

# Set variable untuk current project
export CURRENT_PATH="/Users/rikihikmianto/FlutterProjects/POS SaaS Multi Merchant - Bangun Dari Nol hingga Rilis di Play Store/ultimate-pos-saas"

# Verifikasi path
ls -la "$CURRENT_PATH"
```

**Note:** Jika menggunakan Windows Command Prompt, gunakan:
```cmd
set MASTER2_PATH="C:\Path\To\2-ultimate-pos-saas-master"
set CURRENT_PATH="C:\Path\To\ultimate-pos-saas"
```

### 1.3. Checklist Sebelum Mulai

- [ ] Git branch sudah benar
- [ ] Working directory clean
- [ ] Path master-2 dan current sudah diverifikasi
- [ ] Database backup sudah dibuat (lihat section 2)

---

## 2. BACKUP PROJECT

### 2.1. Backup Database

```bash
# Export database saat ini
# Sesuaikan nama database
mysqldump -u root -p ultimate_pos_saas > backup_before_migration_$(date +%Y%m%d_%H%M%S).sql

# Atau jika menggunakan PostgreSQL
pg_dump ultimate_pos_saas > backup_before_migration_$(date +%Y%m%d_%H%M%S).sql
```

### 2.2. Backup Project Files

```bash
# Buat backup dari current project
cd /Users/rikihikmianto/FlutterProjects/POS\ SaaS\ Multi\ Merchant\ -\ Bangun\ Dari\ Nol\ hingga\ Rilis\ di\ Play\ Store/
cp -r ultimate-pos-saas ultimate-pos-saas-backup-$(date +%Y%m%d)

# Verifikasi backup terbuat
ls -la | grep ultimate-pos-saas-backup
```

### 2.3. Commit Changes Terakhir

```bash
cd "$CURRENT_PATH"

# Pastikan semua changes sudah commit
git add .
git commit -m "Backup before migration to master-2"
```

---

## 3. MIGRASI DATABASE (MIGRATIONS)

### 3.1. Hapus Migrations Lama di Current Project

**⚠️ WARNING:** Hanya lakukan ini jika database masih development/sudah backup!

```bash
cd "$CURRENT_PATH"

# Buat folder temporary untuk migrations lama
mkdir -p database/migrations_old

# Pindahkan migrations lama
# HANYA migrations yang BUKAN 0001_01_01_ format
mv database/migrations/2026_*.php database/migrations_old/

# Verifikasi
ls database/migrations/
# Harusnya hanya tersisa migrations dari master-1
```

### 3.2. Copy Migrations dari Master-2

```bash
# Copy semua migrations dari master-2
cp -r "$MASTER2_PATH/database/migrations/"* "$CURRENT_PATH/database/migrations/"

# Verifikasi migrations tercopy
ls -la "$CURRENT_PATH/database/migrations/" | wc -l
# Expected: 60+ files
```

### 3.3. Check Migration Files

```bash
# Lihat semua migration files baru
ls "$CURRENT_PATH/database/migrations/" | sort

# Pastikan migrations berikut ada:
# - 0001_01_01_000000_create_tenants_table.php
# - 2026_01_14_* files (inventory)
# - 2026_01_15_* files (product system)
# - 2026_01_20_* files (cash drawer & authorization)
# - 2026_01_21_* files (improvements)
```

### 3.4. Jalankan Fresh Migration

**⚠️ WARNING:** Ini akan DROP semua tabel!

```bash
cd "$CURRENT_PATH"

# Drop semua tabel dan migrasi ulang
php artisan migrate:fresh

# Atau jika ingin preserve data:
php artisan migrate
```

### 3.5. Verifikasi Database

```bash
# Cek semua tabel terbuat
php artisan tinker

# Di tinker, jalankan:
DB::select('SHOW TABLES');

# Expected output: 60+ tables including:
# - tenants, users, outlets, roles, permissions
# - suppliers, units, inventory_categories, inventory_items
# - products, product_categories, product_variants
# - variant_groups, variant_options
# - modifiers, modifier_groups
# - combos, combo_items
# - user_pins, pin_attempts, authorization_logs
# - held_orders, cash_drawer_logs
# - stock_batch_movements, batch_settings
# - transactions, transaction_items, etc.

exit
```

---

## 4. MIGRASI MODELS

### 4.1. Copy Models Baru dari Master-2

```bash
cd "$CURRENT_PATH"

# Models yang perlu dicopy (BARU):
MODELS_NEW=(
    "Authorization.php"
    "AuthorizationLog.php"
    "AuthorizationSetting.php"
    "BatchSetting.php"
    "CashDrawerLog.php"
    "Combo.php"
    "ComboItem.php"
    "HeldOrder.php"
    "Modifier.php"
    "ModifierGroup.php"
    "PinAttempt.php"
    "Product.php"
    "ProductCategory.php"
    "ProductOutlet.php"
    "ProductVariant.php"
    "StockBatchMovement.php"
    "UserPin.php"
    "VariantGroup.php"
    "VariantOption.php"
)

# Copy models baru
for model in "${MODELS_NEW[@]}"; do
    cp "$MASTER2_PATH/app/Models/$model" "$CURRENT_PATH/app/Models/"
    echo "Copied: $model"
done
```

### 4.2. Copy Models yang Di-Update

```bash
# Models yang perlu di-replace (UPDATE):
MODELS_UPDATE=(
    "InventoryCategory.php"
    "InventoryItem.php"
    "Outlet.php"
    "Permission.php"
    "Recipe.php"
    "RecipeItem.php"
    "Role.php"
    "StockBatch.php"
    "StockMovement.php"
    "Tenant.php"
    "User.php"
)

# Backup models lama dulu
for model in "${MODELS_UPDATE[@]}"; do
    cp "$CURRENT_PATH/app/Models/$model" "$CURRENT_PATH/app/Models/${model}.backup"
    cp "$MASTER2_PATH/app/Models/$model" "$CURRENT_PATH/app/Models/"
    echo "Updated: $model"
done
```

### 4.3. Verifikasi Models

```bash
# Hitung jumlah models
ls -1 "$CURRENT_PATH/app/Models/"*.php | wc -l
# Expected: 52 models

# List semua models
ls -1 "$CURRENT_PATH/app/Models/"*.php | xargs -n1 basename | sort
```

### 4.4. Test Models dengan Tinker

```bash
php artisan tinker

# Test beberapa models baru:
$tenant = \App\Models\Tenant::first();
$product = \App\Models\Product::first();
$variant = \App\Models\VariantGroup::first();

// Test relasi
$product->category; // should work
$product->variants; // should work

exit
```

---

## 5. MIGRASI FACTORIES

### 5.1. Copy Semua Factories dari Master-2

```bash
cd "$CURRENT_PATH"

# Copy semua factories
cp -r "$MASTER2_PATH/database/factories/"* "$CURRENT_PATH/database/factories/"

# Verifikasi
ls -1 "$CURRENT_PATH/database/factories/"*.php | wc -l
# Expected: 14+ factories
```

### 5.2. Daftar Factories yang Harus Ada

```bash
# Cek factories baru
ls "$CURRENT_PATH/database/factories/" | grep -E "(Customer|Inventory|Payment|Price|Supplier|Unit|Factory)"
```

Expected factories:
- `CustomerFactory.php`
- `InventoryCategoryFactory.php`
- `InventoryItemFactory.php`
- `InventoryStockFactory.php`
- `OutletFactory.php`
- `PaymentMethodFactory.php`
- `PermissionFactory.php`
- `PriceFactory.php`
- `RoleFactory.php`
- `SupplierFactory.php`
- `SupplierItemFactory.php`
- `TenantFactory.php`
- `UnitFactory.php`
- `UserFactory.php`

### 5.3. Test Factories

```bash
php artisan tinker

// Test create data dengan factory
\App\Models\Tenant::factory()->create();
\App\Models\Product::factory()->create();
\App\Models\Customer::factory()->create();

exit
```

---

## 6. MIGRASI CONTROLLERS

### 6.1. Copy Controllers Baru (Admin)

```bash
cd "$CURRENT_PATH"

# Admin Controllers - NEW
ADMIN_CONTROLLERS_NEW=(
    "app/Http/Controllers/Admin/UserPinController.php"
)

for controller in "${ADMIN_CONTROLLERS_NEW[@]}"; do
    cp "$MASTER2_PATH/$controller" "$CURRENT_PATH/$controller"
    echo "Copied: $controller"
done
```

### 6.2. Copy Controllers Baru (Inventory)

```bash
# Inventory Controllers - NEW
INVENTORY_CONTROLLERS_NEW=(
    "app/Http/Controllers/Inventory/BatchSettingController.php"
    "app/Http/Controllers/Inventory/StockBatchController.php"
)

for controller in "${INVENTORY_CONTROLLERS_NEW[@]}"; do
    cp "$MASTER2_PATH/$controller" "$CURRENT_PATH/$controller"
    echo "Copied: $controller"
done
```

### 6.3. Copy Controllers Baru (POS)

```bash
# POS Controllers - NEW
POS_CONTROLLERS_NEW=(
    "app/Http/Controllers/POS/AuthorizationController.php"
    "app/Http/Controllers/POS/CashDrawerController.php"
    "app/Http/Controllers/POS/HeldOrderController.php"
)

for controller in "${POS_CONTROLLERS_NEW[@]}"; do
    cp "$MASTER2_PATH/$controller" "$CURRENT_PATH/$controller"
    echo "Copied: $controller"
done
```

### 6.4. Copy Controllers Baru (Menu)

```bash
# Menu Controllers - NEW (entire folder)
mkdir -p "$CURRENT_PATH/app/Http/Controllers/Menu"
cp -r "$MASTER2_PATH/app/Http/Controllers/Menu/"* "$CURRENT_PATH/app/Http/Controllers/Menu/"
```

### 6.5. Copy Controllers yang Di-Update

```bash
# Backup dan replace controllers yang di-update
CONTROLLERS_UPDATE=(
    "app/Http/Controllers/Admin/DashboardController.php"
    "app/Http/Controllers/Admin/OutletController.php"
    "app/Http/Controllers/Admin/RoleController.php"
    "app/Http/Controllers/Admin/TenantController.php"
    "app/Http/Controllers/Admin/UserController.php"
    "app/Http/Controllers/Auth/LoginController.php"
    "app/Http/Controllers/Auth/RegisterController.php"
    "app/Http/Controllers/Inventory/GoodsReceiveController.php"
    "app/Http/Controllers/Inventory/InventoryCategoryController.php"
    "app/Http/Controllers/Inventory/InventoryItemController.php"
    "app/Http/Controllers/Inventory/PurchaseOrderController.php"
    "app/Http/Controllers/Inventory/RecipeController.php"
    "app/Http/Controllers/Inventory/ReportController.php"
    "app/Http/Controllers/Inventory/StockAdjustmentController.php"
    "app/Http/Controllers/Inventory/StockController.php"
    "app/Http/Controllers/Inventory/StockTransferController.php"
    "app/Http/Controllers/Inventory/SupplierController.php"
    "app/Http/Controllers/Inventory/UnitController.php"
    "app/Http/Controllers/Inventory/WasteLogController.php"
    "app/Http/Controllers/POS/PosController.php"
    "app/Http/Controllers/POS/TransactionController.php"
    "app/Http/Controllers/Pricing/DiscountController.php"
    "app/Http/Controllers/Pricing/PaymentMethodController.php"
)

for controller in "${CONTROLLERS_UPDATE[@]}"; do
    # Backup
    cp "$CURRENT_PATH/$controller" "$CURRENT_PATH/${controller}.backup"
    # Copy new
    cp "$MASTER2_PATH/$controller" "$CURRENT_PATH/$controller"
    echo "Updated: $controller"
done
```

### 6.6. Copy Controller Baru (Root)

```bash
# New root controllers
cp "$MASTER2_PATH/app/Http/Controllers/LocaleController.php" "$CURRENT_PATH/app/Http/Controllers/"
```

### 6.7. Verifikasi Controllers

```bash
# Count controllers
find "$CURRENT_PATH/app/Http/Controllers" -name "*.php" | wc -l
# Expected: 39+ controllers

# List controllers by folder
find "$CURRENT_PATH/app/Http/Controllers" -name "*.php" | sort
```

---

## 7. MIGRASI SERVICES

### 7.1. Copy Authorization Services

```bash
cd "$CURRENT_PATH"

# Copy Authorization services folder
mkdir -p "$CURRENT_PATH/app/Services/Authorization"
cp -r "$MASTER2_PATH/app/Services/Authorization/"* "$CURRENT_PATH/app/Services/Authorization/"
```

### 7.2. Copy Menu Services

```bash
# Copy Menu services folder
mkdir -p "$CURRENT_PATH/app/Services/Menu"
cp -r "$MASTER2_PATH/app/Services/Menu/"* "$CURRENT_PATH/app/Services/Menu/"
```

### 7.3. Update Inventory Services

```bash
# Backup dulu
cp "$CURRENT_PATH/app/Services/Inventory/PurchaseOrderService.php" "$CURRENT_PATH/app/Services/Inventory/PurchaseOrderService.php.backup"
cp "$CURRENT_PATH/app/Services/Inventory/RecipeCostService.php" "$CURRENT_PATH/app/Services/Inventory/RecipeCostService.php.backup"
cp "$CURRENT_PATH/app/Services/Inventory/StockAdjustmentService.php" "$CURRENT_PATH/app/Services/Inventory/StockAdjustmentService.php.backup"
cp "$CURRENT_PATH/app/Services/Inventory/StockTransferService.php" "$CURRENT_PATH/app/Services/Inventory/StockTransferService.php.backup"

# Copy yang baru
cp "$MASTER2_PATH/app/Services/Inventory/PurchaseOrderService.php" "$CURRENT_PATH/app/Services/Inventory/"
cp "$MASTER2_PATH/app/Services/Inventory/RecipeCostService.php" "$CURRENT_PATH/app/Services/Inventory/"
cp "$MASTER2_PATH/app/Services/Inventory/StockAdjustmentService.php" "$CURRENT_PATH/app/Services/Inventory/"
cp "$MASTER2_PATH/app/Services/Inventory/StockTransferService.php" "$CURRENT_PATH/app/Services/Inventory/"
```

### 7.4. Verifikasi Services

```bash
# List semua services
find "$CURRENT_PATH/app/Services" -name "*.php" | sort

# Expected folders:
# - Authorization/
# - Inventory/
# - Menu/
```

---

## 8. MIGRASI MIDDLEWARE

### 8.1. Copy Middleware Baru

```bash
cd "$CURRENT_PATH"

# Copy new middleware
cp "$MASTER2_PATH/app/Http/Middleware/SetLocale.php" "$CURRENT_PATH/app/Http/Middleware/"
```

### 8.2. Update Middleware yang Ada

```bash
# Backup dulu
cp "$CURRENT_PATH/app/Http/Middleware/CheckPermission.php" "$CURRENT_PATH/app/Http/Middleware/CheckPermission.php.backup"
cp "$CURRENT_PATH/app/Http/Middleware/EnsureTenantScope.php" "$CURRENT_PATH/app/Http/Middleware/EnsureTenantScope.php.backup"

# Copy yang baru
cp "$MASTER2_PATH/app/Http/Middleware/CheckPermission.php" "$CURRENT_PATH/app/Http/Middleware/"
cp "$MASTER2_PATH/app/Http/Middleware/EnsureTenantScope.php" "$CURRENT_PATH/app/Http/Middleware/"
```

### 8.3. Register Middleware di bootstrap/app.php

```bash
# Edit file bootstrap/app.php
nano "$CURRENT_PATH/bootstrap/app.php"
# ATAU gunakan editor favorit Anda: code, vim, dll
```

Tambahkan middleware berikut di bagian middleware:

```php
->withMiddleware(function (Middleware $middleware) {
    // ... existing middleware ...

    // Tambahkan ini:
    $middleware->append(\App\Http\Middleware\SetLocale::class);
})
```

---

## 9. MIGRASI VIEWS

### 9.1. Copy Admin Views Baru

```bash
cd "$CURRENT_PATH"

# Authorization views
mkdir -p "$CURRENT_PATH/resources/views/admin/authorization"
cp -r "$MASTER2_PATH/resources/views/admin/authorization/"* "$CURRENT_PATH/resources/views/admin/authorization/"

# User PIN views
cp "$MASTER2_PATH/resources/views/admin/users/pin.blade.php" "$CURRENT_PATH/resources/views/admin/users/"
cp "$MASTER2_PATH/resources/views/admin/users/pin-self.blade.php" "$CURRENT_PATH/resources/views/admin/users/"
```

### 9.2. Copy Inventory Views Baru

```bash
# Batches views
mkdir -p "$CURRENT_PATH/resources/views/inventory/batches"
cp -r "$MASTER2_PATH/resources/views/inventory/batches/"* "$CURRENT_PATH/resources/views/inventory/batches/"
```

### 9.3. Copy Menu Views

```bash
# Menu folder
mkdir -p "$CURRENT_PATH/resources/views/menu"
cp -r "$MASTER2_PATH/resources/views/menu/"* "$CURRENT_PATH/resources/views/menu/"
```

### 9.4. Copy Component Views Baru

```bash
# New components
cp "$MASTER2_PATH/resources/views/components/form-group.blade.php" "$CURRENT_PATH/resources/views/components/"
cp "$MASTER2_PATH/resources/views/components/pin-modal.blade.php" "$CURRENT_PATH/resources/views/components/"
```

### 9.5. Update Views yang Ada

```bash
# Backup dan replace views yang di-update
# HATI-HATI: Views mungkin sudah Anda kustomisasi!

# Example untuk beberapa views:
VIEWS_UPDATE=(
    "resources/views/admin/dashboard.blade.php"
    "resources/views/admin/tenants/index.blade.php"
    "resources/views/admin/tenants/show.blade.php"
    "resources/views/admin/users/index.blade.php"
    "resources/views/admin/users/create.blade.php"
    "resources/views/partials/header.blade.php"
    "resources/views/partials/sidebar.blade.php"
    "resources/views/pos/index.blade.php"
)

for view in "${VIEWS_UPDATE[@]}"; do
    # Backup dulu
    cp "$CURRENT_PATH/$view" "$CURRENT_PATH/${view}.backup"
    # Copy new (HAPUS komentar di bawah jika ingin copy)
    # cp "$MASTER2_PATH/$view" "$CURRENT_PATH/$view"
    echo "Consider updating: $view"
done
```

**⚠️ NOTE:** Untuk views yang di-update, sangat disarankan untuk:
1. Bandingkan manual dengan `diff`
2. Pilih changes yang Anda butuhkan
3. Jangan replace langsung jika sudah ada kustomisasi

### 9.6. Verifikasi Views

```bash
# Count blade files
find "$CURRENT_PATH/resources/views" -name "*.blade.php" | wc -l
# Expected: 100+ blade files

# List views by folder
find "$CURRENT_PATH/resources/views" -type d | sort
```

---

## 10. UPDATE ROUTES

### 10.1. Backup Routes Lama

```bash
cd "$CURRENT_PATH"

cp "$CURRENT_PATH/routes/web.php" "$CURRENT_PATH/routes/web.php.backup"
```

### 10.2. Bandingkan Routes

```bash
# Lihat perbedaan routes
diff "$MASTER2_PATH/routes/web.php" "$CURRENT_PATH/routes/web.php"
```

### 10.3. Update Routes Manual

Buka file `routes/web.php` dan tambahkan routes berikut:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Admin\UserPinController;
use App\Http\Controllers\POS\AuthorizationController;
use App\Http\Controllers\POS\CashDrawerController;
use App\Http\Controllers\POS\HeldOrderController;
use App\Http\Controllers\Inventory\BatchSettingController;
use App\Http\Controllers\Inventory\StockBatchController;

// ... existing routes ...

// Locale routes
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

// Admin - User PIN
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:manage-users'])->group(function () {
    Route::get('/users/{user}/pin', [UserPinController::class, 'show'])->name('users.pin.show');
    Route::post('/users/{user}/pin', [UserPinController::class, 'store'])->name('users.pin.store');
    Route::get('/pin', [UserPinController::class, 'editSelf'])->name('users.pin.self');
    Route::put('/pin', [UserPinController::class, 'updateSelf'])->name('users.pin.self.update');
});

// POS - Authorization
Route::prefix('pos')->name('pos.')->middleware(['auth'])->group(function () {
    Route::post('/authorize', [AuthorizationController::class, 'authorize'])->name('authorize');
});

// POS - Cash Drawer
Route::prefix('pos')->name('pos.')->middleware(['auth'])->group(function () {
    Route::get('/cash-drawer', [CashDrawerController::class, 'index'])->name('cash-drawer.index');
    Route::post('/cash-drawer/open', [CashDrawerController::class, 'open'])->name('cash-drawer.open');
    Route::post('/cash-drawer/close', [CashDrawerController::class, 'close'])->name('cash-drawer.close');
});

// POS - Held Orders
Route::prefix('pos')->name('pos.')->middleware(['auth'])->group(function () {
    Route::get('/held-orders', [HeldOrderController::class, 'index'])->name('held-orders.index');
    Route::post('/held-orders', [HeldOrderController::class, 'store'])->name('held-orders.store');
    Route::delete('/held-orders/{heldOrder}', [HeldOrderController::class, 'destroy'])->name('held-orders.destroy');
    Route::post('/held-orders/{heldOrder}/restore', [HeldOrderController::class, 'restore'])->name('held-orders.restore');
});

// Inventory - Batch Settings
Route::prefix('inventory')->name('inventory.')->middleware(['auth'])->group(function () {
    Route::get('/batch-settings', [BatchSettingController::class, 'index'])->name('batch-settings.index');
    Route::post('/batch-settings', [BatchSettingController::class, 'store'])->name('batch-settings.store');
});

// Inventory - Stock Batches
Route::prefix('inventory')->name('inventory.')->middleware(['auth'])->group(function () {
    Route::get('/stock-batches', [StockBatchController::class, 'index'])->name('stock-batches.index');
    Route::get('/stock-batches/{stockBatch}', [StockBatchController::class, 'show'])->name('stock-batches.show');
});
```

### 10.4. Clear Route Cache

```bash
php artisan route:clear
php artisan route:cache

# Verifikasi routes baru terdaftar
php artisan route:list | grep -E "(locale|pin|authorize|cash|held|batch)"
```

---

## 11. UPDATE DEPENDENCIES

### 11.1. Update composer.json

```bash
cd "$CURRENT_PATH"

# Backup composer.json
cp composer.json composer.json.backup
```

Edit `composer.json` dan tambahkan:

```json
{
    "require-dev": {
        "phpunit/php-code-coverage": "^11.0"
    }
}
```

### 11.2. Update Scripts di composer.json

Di bagian `scripts`, update `dev` script:

```json
{
    "scripts": {
        "dev": [
            "Composer\\Config::disableProcessTimeout",
            "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite --kill-others"
        ]
    }
}
```

**Note:** Tambahkan `--timeout=0` ke queue command.

### 11.3. Install Dependencies

```bash
cd "$CURRENT_PATH"

# Install new dependencies
composer update

# Atau jika ingin lebih cepat:
composer require phpunit/php-code-coverage
```

---

## 12. TESTING & VERIFIKASI

### 12.1. Clear All Caches

```bash
cd "$CURRENT_PATH"

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 12.2. Test Database Connection

```bash
php artisan tinker

// Test connection
DB::connection()->getPdo();
// Expected: PDO object (no error)

// Test models
$tenantCount = \App\Models\Tenant::count();
$productCount = \App\Models\Product::count();

echo "Tenants: $tenantCount\n";
echo "Products: $productCount\n";

exit
```

### 12.3. Test Routes

```bash
# List all routes
php artisan route:list

# Cek routes baru terdaftar
php artisan route:list | grep -E "(locale|pin|authorize|cash|held)"

# Expected output:
// POST   locale        locale.update
// GET    users/{user}/pin        admin.users.pin.show
// POST   users/{user}/pin        admin.users.pin.store
// POST   pos/authorize        pos.authorize
// GET    pos/cash-drawer       pos.cash-drawer.index
// etc.
```

### 12.4. Test Controllers

```bash
# Test dengan artisan (tanpa menjalankan server)
php artisan route:list --columns=uri,name

# Cek syntax errors
php -l app/Http/Controllers/Admin/UserPinController.php
php -l app/Http/Controllers/POS/AuthorizationController.php
php -l app/Http/Controllers/POS/CashDrawerController.php

# Expected: No syntax errors
```

### 12.5. Run Development Server

```bash
cd "$CURRENT_PATH"

# Start dev server
php artisan serve

# Di browser lain, test:
# http://localhost:8000
# http://localhost:8000/login
# http://localhost:8000/admin/dashboard
```

### 12.6. Test Specific Features

#### Test 1: User PIN
```bash
# Login sebagai admin
# Buka: http://localhost:8000/admin/users
# Klik user dan coba set PIN
# Expected: Form PIN muncul dan bisa disimpan
```

#### Test 2: Product System
```bash
php artisan tinker

// Create test product
$category = \App\Models\ProductCategory::factory()->create();
$product = \App\Models\Product::factory()->create([
    'product_category_id' => $category->id
]);

// Create variant group
$sizeGroup = \App\Models\VariantGroup::factory()->create([
    'name' => 'Size'
]);

// Add options to variant
\App\Models\VariantOption::factory()->create([
    'variant_group_id' => $sizeGroup->id,
    'name' => 'S'
]);

\App\Models\VariantOption::factory()->create([
    'variant_group_id' => $sizeGroup->id,
    'name' => 'M'
]);

// Link variant to product
$product->variants()->attach($sizeGroup->id);

exit
```

#### Test 3: Authorization
```bash
php artisan tinker

// Create authorization setting
\App\Models\AuthorizationSetting::factory()->create();

// Test authorization log
$user = \App\Models\User::first();
$log = \App\Models\AuthorizationLog::factory()->create([
    'user_id' => $user->id
]);

exit
```

### 12.7. Run Tests (Jika Ada)

```bash
cd "$CURRENT_PATH"

# Run PHPUnit tests
php artisan test

# Atau
./vendor/bin/phpunit
```

---

## 13. TROUBLESHOOTING

### 13.1. Error: Class Not Found

**Problem:**
```
Error: Class 'App\Models\Product' not found
```

**Solution:**
```bash
# Clear autoload
composer dump-autoload

# Clear config cache
php artisan config:clear
```

### 13.2. Error: Table Not Found

**Problem:**
```
SQLSTATE[42S02]: Table not found: 1146 Table 'database.products' doesn't exist
```

**Solution:**
```bash
# Check migrations
php artisan migrate:status

# Rerun migrations
php artisan migrate:fresh

# Atau run specific migration
php artisan migrate --path=database/migrations/2026_01_15_140717_create_products_table.php
```

### 13.3. Error: Route Not Defined

**Problem:**
```
Route [admin.users.pin.show] not defined
```

**Solution:**
```bash
# Clear route cache
php artisan route:clear

# Check if route exists
php artisan route:list | grep "users.pin"

# If not found, check routes/web.php
# Make sure routes are properly defined
```

### 13.4. Error: View Not Found

**Problem:**
```
View [admin.users.pin] not found
```

**Solution:**
```bash
# Clear view cache
php artisan view:clear

# Check if view exists
ls resources/views/admin/users/pin.blade.php

# If not found, copy from master-2
cp "$MASTER2_PATH/resources/views/admin/users/pin.blade.php" "$CURRENT_PATH/resources/views/admin/users/"
```

### 13.5. Error: Permission Denied

**Problem:**
```
Permission denied on database/migrations/
```

**Solution:**
```bash
# Fix permissions
chmod -R 755 "$CURRENT_PATH/database/migrations"
chmod -R 755 "$CURRENT_PATH/app"
chmod -R 755 "$CURRENT_PATH/resources"

# If using sudo (not recommended)
# sudo chown -R $USER:$USER "$CURRENT_PATH"
```

### 13.6. Error: Middleware Not Found

**Problem:**
```
Target class [SetLocale] does not exist
```

**Solution:**
```bash
# Check if middleware exists
ls app/Http/Middleware/SetLocale.php

# If not found, copy from master-2
cp "$MASTER2_PATH/app/Http/Middleware/SetLocale.php" "$CURRENT_PATH/app/Http/Middleware/"

# Clear config cache
php artisan config:clear
```

### 13.7. Rollback jika Gagal

```bash
cd "$CURRENT_PATH"

# Rollback migrations
php artisan migrate:rollback

# Restore backup files
cp app/Models/*.backup app/Models/
cp app/Http/Controllers/**/*.backup app/Http/Controllers/

# Delete new files (example)
# rm app/Models/Product.php

# Or restore entire backup
cd ..
cp -r ultimate-pos-saas-backup-DATE/* ultimate-pos-saas/
```

---

## 14. CHECKLIST FINAL

Setelah selesai migrasi, pastikan:

- [ ] Semua migrations berjalan sukses
- [ ] Semua models ter-load tanpa error
- [ ] Semua controllers tidak ada syntax error
- [ ] Semua routes terdaftar
- [ ] Semua views bisa di-render
- [ ] Tidak ada class not found error
- [ ] Tidak ada table not found error
- [ ] Development server berjalan normal
- [ ] Login/logout berfungsi
- [ ] CRUD basic berfungsi (tenants, users, outlets)
- [ ] Fitur baru bisa diakses (PIN, products, variants)

---

## 15. COMMIT CHANGES

Jika semua sudah berjalan baik:

```bash
cd "$CURRENT_PATH"

# Add semua changes
git add .

# Commit dengan message yang jelas
git commit -m "Migrate to master-2

- Add product system (products, categories, variants)
- Add modifier & combo system
- Add user PIN & authorization
- Add cash drawer & held orders
- Add batch settings & stock batch movements
- Add menu management
- Add localization support
- Update all controllers, services, views
- Add new factories
- Update migrations to 60+ tables
- Update composer dependencies"

# Push ke remote
git push origin future/master-2
```

---

## 16. LANGKAH SELANJUTNYA

Setelah migrasi berhasil:

1. **Merge ke Main** (jika sudah siap)
   ```bash
   git checkout main
   git merge future/master-2
   git push origin main
   ```

2. **Testing di Production**
   - Backup production database
   - Test di staging environment dulu
   - Deploy ke production

3. **Dokumentasi**
   - Update README
   - Tambahkan dokumentasi fitur baru
   - Buat user guide untuk fitur PIN, authorization, dll

4. **Training**
   - Training tim untuk fitur baru
   - Buat video tutorial (opsional)

---

## 17. RESOURCE TAMBAHAN

### Command Cheat Sheet

```bash
# Database
php artisan migrate:fresh          # Drop & rerun migrations
php artisan migrate:rollback       # Rollback last migration
php artisan migrate:status         # Check migration status

# Models
php artisan tinker                 # Test models & relationships

# Routes
php artisan route:list             # List all routes
php artisan route:clear            # Clear route cache

# Cache
php artisan cache:clear            # Clear all cache
php artisan config:clear           # Clear config cache
php artisan view:clear             # Clear view cache
php artisan route:cache            # Re-cache routes

# Development
php artisan serve                  # Start dev server
composer dump-autoload             # Regenerate autoload files
```

### File Locations Reference

```
app/
├── Models/                    (52 models)
├── Http/
│   ├── Controllers/           (39 controllers)
│   │   ├── Admin/
│   │   ├── Auth/
│   │   ├── Customer/
│   │   ├── Inventory/
│   │   ├── Menu/              (NEW)
│   │   ├── POS/
│   │   └── Pricing/
│   └── Middleware/            (+ SetLocale)
├── Services/
│   ├── Authorization/         (NEW)
│   ├── Inventory/
│   └── Menu/                  (NEW)

database/
├── factories/                 (14 factories)
└── migrations/                (65+ migrations)

resources/views/
├── admin/
│   ├── authorization/         (NEW)
│   └── users/
│       ├── pin.blade.php      (NEW)
│       └── pin-self.blade.php (NEW)
├── components/
│   ├── form-group.blade.php   (NEW)
│   └── pin-modal.blade.php    (NEW)
├── inventory/
│   └── batches/               (NEW)
└── menu/                      (NEW)
```

---

## 18. CONTACT & SUPPORT

Jika mengalami masalah:

1. Cek [Troubleshooting](#13-troubleshooting)
2. Cek dokumentasi Laravel: https://laravel.com/docs
3. Cek error logs: `storage/logs/laravel.log`

---

**📝 Note:** Dokumen ini dibuat untuk migrasi dari master-2 (2026-01-22) ke current project. Pastikan untuk mengikuti setiap langkah dengan teliti dan melakukan backup sebelum memulai.

**⚠️ Disclaimer:** Selalu backup database dan project sebelum melakukan migrasi besar. Penulis tidak bertanggung jawab atas kehilangan data.

---

**Tanggal Dibuat:** 2026-02-11
**Versi:** 1.0
**Untuk:** ultimate-pos-saas project
