<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OutletController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserPinController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Inventory\BatchSettingController;
use App\Http\Controllers\Inventory\GoodsReceiveController;
use App\Http\Controllers\Inventory\InventoryCategoryController;
use App\Http\Controllers\Inventory\InventoryItemController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\Inventory\RecipeController;
use App\Http\Controllers\Inventory\ReportController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\StockBatchController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\UnitController;
use App\Http\Controllers\Inventory\WasteLogController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Menu\ComboController;
use App\Http\Controllers\Menu\ModifierGroupController;
use App\Http\Controllers\Menu\ProductCategoryController;
use App\Http\Controllers\Menu\ProductController;
use App\Http\Controllers\Menu\VariantGroupController;
use App\Http\Controllers\POS\AuthorizationController;
use App\Http\Controllers\POS\CashDrawerController;
use App\Http\Controllers\POS\HeldOrderController;
use App\Http\Controllers\POS\PosController;
use App\Http\Controllers\POS\SessionController;
use App\Http\Controllers\POS\TransactionController;
use App\Http\Controllers\Pricing\DiscountController;
use App\Http\Controllers\Pricing\PaymentMethodController;
use App\Http\Controllers\Pricing\PriceController;
use Illuminate\Support\Facades\Route;

// Landing Page
Route::get('/', function () {
    return redirect()->route('login');
});

// Locale Switcher (available for all users)
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Auth Routes
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Tenant Management (Super Admin only)
        Route::resource('tenants', TenantController::class);
        Route::post('/tenants/{tenant}/switch', [TenantController::class, 'switchTenant'])->name('tenants.switch');
        Route::post('/tenants/clear', [TenantController::class, 'clearTenant'])->name('tenants.clear');

        // Outlet Management
        Route::resource('outlets', OutletController::class);

        // User Management
        Route::resource('users', UserController::class);
        Route::get('/users-outlets-by-tenant/{tenant}', [UserController::class, 'getOutletsByTenant'])->name('users.outlets-by-tenant');

        // Role & Permission Management
        Route::resource('roles', RoleController::class);
        Route::get('/roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
        Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');

        // User PIN Management
        Route::get('/users/{user}/pin', [UserPinController::class, 'edit'])->name('users.pin.edit');
        Route::put('/users/{user}/pin', [UserPinController::class, 'update'])->name('users.pin.update');
        Route::delete('/users/{user}/pin', [UserPinController::class, 'destroy'])->name('users.pin.destroy');
        Route::get('/my-pin', [UserPinController::class, 'editOwn'])->name('my-pin');
        Route::put('/my-pin', [UserPinController::class, 'updateOwn'])->name('my-pin.update');

        // Authorization Settings & Logs
        Route::get('/authorization/settings', [AuthorizationController::class, 'settings'])->name('authorization.settings');
        Route::put('/authorization/settings', [AuthorizationController::class, 'updateSettings'])->name('authorization.settings.update');
        Route::get('/authorization/logs', [AuthorizationController::class, 'logs'])->name('authorization.logs');
    });

    // Inventory Routes
    Route::prefix('inventory')->name('inventory.')->group(function () {
        // Units
        Route::resource('units', UnitController::class);

        // Suppliers
        Route::resource('suppliers', SupplierController::class);

        // Categories
        Route::resource('categories', InventoryCategoryController::class);

        // Inventory Items
        Route::resource('items', InventoryItemController::class);

        // Stock Management
        Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::get('/stocks/{stock}', [StockController::class, 'show'])->name('stocks.show');
        Route::get('/stocks-movements', [StockController::class, 'movements'])->name('stocks.movements');
        Route::get('/stocks-batches', [StockController::class, 'batches'])->name('stocks.batches');
        Route::get('/stocks-low', [StockController::class, 'lowStock'])->name('stocks.low');
        Route::get('/stocks-expiring', [StockController::class, 'expiringItems'])->name('stocks.expiring');

        // Purchase Orders
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('/purchase-orders/{purchaseOrder}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

        // Goods Receive
        Route::resource('goods-receives', GoodsReceiveController::class);
        Route::post('/goods-receives/{goodsReceive}/complete', [GoodsReceiveController::class, 'complete'])->name('goods-receives.complete');
        Route::post('/goods-receives/{goodsReceive}/cancel', [GoodsReceiveController::class, 'cancel'])->name('goods-receives.cancel');

        // Stock Adjustments
        Route::resource('stock-adjustments', StockAdjustmentController::class);
        Route::post('/stock-adjustments/{stockAdjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('stock-adjustments.approve');
        Route::post('/stock-adjustments/{stockAdjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('stock-adjustments.reject');
        Route::get('/stock-take', [StockAdjustmentController::class, 'stockTake'])->name('stock-adjustments.stock-take');
        Route::get('/stock-for-outlet', [StockAdjustmentController::class, 'getStockForOutlet'])->name('stock-adjustments.stock-for-outlet');

        // Stock Transfers
        Route::resource('stock-transfers', StockTransferController::class);
        Route::post('/stock-transfers/{stockTransfer}/approve', [StockTransferController::class, 'approve'])->name('stock-transfers.approve');
        Route::post('/stock-transfers/{stockTransfer}/ship', [StockTransferController::class, 'ship'])->name('stock-transfers.ship');
        Route::post('/stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive'])->name('stock-transfers.receive');
        Route::post('/stock-transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');
        Route::get('/source-stock', [StockTransferController::class, 'getSourceStock'])->name('stock-transfers.source-stock');

        // Recipes
        Route::resource('recipes', RecipeController::class);
        Route::post('/recipes/{recipe}/duplicate', [RecipeController::class, 'duplicate'])->name('recipes.duplicate');
        Route::post('/recipes/{recipe}/recalculate', [RecipeController::class, 'recalculateCost'])->name('recipes.recalculate');
        Route::get('/recipes-cost-analysis', [RecipeController::class, 'costAnalysis'])->name('recipes.cost-analysis');

        // Waste Logs
        Route::resource('waste-logs', WasteLogController::class)->except(['edit', 'update']);
        Route::get('/waste-report', [WasteLogController::class, 'report'])->name('waste-logs.report');

        // Stock Batches
        Route::get('/batches', [StockBatchController::class, 'index'])->name('batches.index');
        Route::get('/batches/create', [StockBatchController::class, 'create'])->name('batches.create');
        Route::post('/batches', [StockBatchController::class, 'store'])->name('batches.store');
        Route::get('/batches/expiry-report', [StockBatchController::class, 'expiryReport'])->name('batches.expiry-report');
        Route::get('/batches/settings', [BatchSettingController::class, 'edit'])->name('batches.settings');
        Route::put('/batches/settings', [BatchSettingController::class, 'update'])->name('batches.settings.update');
        Route::get('/batches/{batch}', [StockBatchController::class, 'show'])->name('batches.show');
        Route::patch('/batches/{batch}/adjust', [StockBatchController::class, 'adjust'])->name('batches.adjust');
        Route::patch('/batches/{batch}/mark-expired', [StockBatchController::class, 'markExpired'])->name('batches.mark-expired');
        Route::patch('/batches/{batch}/dispose', [StockBatchController::class, 'dispose'])->name('batches.dispose');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/stock-valuation', [ReportController::class, 'stockValuation'])->name('stock-valuation');
            Route::get('/stock-movement', [ReportController::class, 'stockMovement'])->name('stock-movement');
            Route::get('/cogs', [ReportController::class, 'cogs'])->name('cogs');
            Route::get('/food-cost', [ReportController::class, 'foodCost'])->name('food-cost');
        });
    });

    // Dashboard alias
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Menu Management Routes
    Route::prefix('menu')->name('menu.')->group(function () {
        // Product Categories
        Route::resource('categories', ProductCategoryController::class);
        Route::post('/categories/reorder', [ProductCategoryController::class, 'reorder'])->name('categories.reorder');

        // Variant Groups
        Route::resource('variant-groups', VariantGroupController::class);

        // Modifier Groups
        Route::resource('modifier-groups', ModifierGroupController::class);

        // Products
        Route::resource('products', ProductController::class);
        Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
        Route::post('/products/{product}/generate-variants', [ProductController::class, 'generateVariants'])->name('products.generate-variants');
        Route::post('/products/bulk-update-prices', [ProductController::class, 'bulkUpdatePrices'])->name('products.bulk-update-prices');

        // Combos
        Route::resource('combos', ComboController::class);
    });

    // Customer Routes
    Route::resource('customers', CustomerController::class);
    Route::post('/customers/{customer}/add-points', [CustomerController::class, 'addPoints'])->name('customers.add-points');

    // Pricing Routes
    Route::prefix('pricing')->name('pricing.')->group(function () {
        // Payment Methods
        Route::resource('payment-methods', PaymentMethodController::class);

        // Discounts
        Route::resource('discounts', DiscountController::class);

        // Prices
        Route::get('/prices', [PriceController::class, 'index'])->name('prices.index');
        Route::get('/prices/bulk-edit', [PriceController::class, 'bulkEdit'])->name('prices.bulk-edit');
        Route::post('/prices/bulk-update', [PriceController::class, 'bulkUpdate'])->name('prices.bulk-update');
        Route::post('/prices/copy', [PriceController::class, 'copy'])->name('prices.copy');
    });

    // POS Routes
    Route::prefix('pos')->name('pos.')->group(function () {
        // Main POS
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/items', [PosController::class, 'getItems'])->name('items');
        Route::get('/customers', [PosController::class, 'searchCustomers'])->name('customers');
        Route::post('/calculate', [PosController::class, 'calculate'])->name('calculate');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
        Route::get('/receipt/{transaction}', [PosController::class, 'receipt'])->name('receipt');

        // Sessions
        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/open', [SessionController::class, 'openForm'])->name('sessions.open');
        Route::post('/sessions', [SessionController::class, 'open'])->name('sessions.store');
        Route::get('/sessions/{session}/close', [SessionController::class, 'closeForm'])->name('sessions.close');
        Route::post('/sessions/{session}/close', [SessionController::class, 'close'])->name('sessions.close.store');
        Route::get('/sessions/{session}/report', [SessionController::class, 'report'])->name('sessions.report');

        // Held Orders
        Route::get('/held-orders', [HeldOrderController::class, 'index'])->name('held-orders.index');
        Route::post('/held-orders', [HeldOrderController::class, 'store'])->name('held-orders.store');
        Route::get('/held-orders/{heldOrder}', [HeldOrderController::class, 'show'])->name('held-orders.show');
        Route::put('/held-orders/{heldOrder}', [HeldOrderController::class, 'update'])->name('held-orders.update');
        Route::delete('/held-orders/{heldOrder}', [HeldOrderController::class, 'destroy'])->name('held-orders.destroy');
        Route::post('/held-orders/{heldOrder}/recall', [HeldOrderController::class, 'recall'])->name('held-orders.recall');

        // Cash Drawer
        Route::get('/cash-drawer', [CashDrawerController::class, 'index'])->name('cash-drawer.index');
        Route::post('/cash-drawer/cash-in', [CashDrawerController::class, 'cashIn'])->name('cash-drawer.cash-in');
        Route::post('/cash-drawer/cash-out', [CashDrawerController::class, 'cashOut'])->name('cash-drawer.cash-out');
        Route::get('/cash-drawer/report', [CashDrawerController::class, 'report'])->name('cash-drawer.report');

        // Authorization (PIN verification for void/refund/etc)
        Route::post('/auth/check', [AuthorizationController::class, 'checkRequired'])->name('auth.check');
        Route::post('/auth/verify', [AuthorizationController::class, 'verify'])->name('auth.verify');
    });

    // Transaction Routes
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        Route::get('/{transaction}/refund', [TransactionController::class, 'refund'])->name('refund');
        Route::post('/{transaction}/refund', [TransactionController::class, 'refundStore'])->name('refund.store');
        Route::post('/{transaction}/void', [TransactionController::class, 'void'])->name('void');
    });
});

// Test route for dialog debugging
Route::get('/test-dialog', function () {
    return view('test-dialog');
});
