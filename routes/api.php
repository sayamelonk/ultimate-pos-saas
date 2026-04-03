<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "api" middleware group.
|
| API Version: v1
| Base URL: /api/v1
|
*/

// ============================================================
// API v1 Routes
// ============================================================

Route::prefix('v1')->group(function () {

    // ----------------------------------------------------------
    // Public Routes (No Authentication Required)
    // ----------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
        Route::post('pin-login', [\App\Http\Controllers\Api\V1\AuthController::class, 'pinLogin']);
    });

    // Subscription Plans (Public - for pricing page)
    Route::prefix('subscription-plans')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\SubscriptionPlanController::class, 'index']);
        Route::get('compare', [\App\Http\Controllers\Api\V1\SubscriptionPlanController::class, 'compare']);
        Route::get('slug/{slug}', [\App\Http\Controllers\Api\V1\SubscriptionPlanController::class, 'showBySlug']);
        Route::get('{id}', [\App\Http\Controllers\Api\V1\SubscriptionPlanController::class, 'show']);
    });

    // ----------------------------------------------------------
    // Protected Routes (Authentication Required)
    // ----------------------------------------------------------
    Route::middleware(['auth:sanctum'])->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
            Route::get('me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me']);
            Route::put('profile', [\App\Http\Controllers\Api\V1\AuthController::class, 'updateProfile']);
            Route::put('pin', [\App\Http\Controllers\Api\V1\AuthController::class, 'updatePin']);
        });

        // Outlets
        Route::prefix('outlets')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\OutletController::class, 'index']);
            Route::get('{outlet}', [\App\Http\Controllers\Api\V1\OutletController::class, 'show']);
            Route::post('switch', [\App\Http\Controllers\Api\V1\OutletController::class, 'switch']);
        });

        // Mobile Sync (for offline-first POS)
        Route::prefix('mobile')->group(function () {
            Route::get('sync/master', [\App\Http\Controllers\Api\V1\MobileSyncController::class, 'master']);
            Route::get('sync/delta', [\App\Http\Controllers\Api\V1\MobileSyncController::class, 'delta']);
            Route::post('transactions/bulk', [\App\Http\Controllers\Api\V1\MobileSyncController::class, 'uploadTransactions']);
            Route::post('sessions/sync', [\App\Http\Controllers\Api\V1\MobileSyncController::class, 'syncSession']);
            Route::get('customers/search', [\App\Http\Controllers\Api\V1\MobileSyncController::class, 'searchCustomers']);
        });

        // Categories
        Route::prefix('categories')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\CategoryController::class, 'index']);
            Route::get('{category}', [\App\Http\Controllers\Api\V1\CategoryController::class, 'show']);
            Route::get('{category}/products', [\App\Http\Controllers\Api\V1\CategoryController::class, 'products']);
        });

        // Products
        Route::prefix('products')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\ProductController::class, 'index']);
            Route::get('search', [\App\Http\Controllers\Api\V1\ProductController::class, 'search']);
            Route::get('barcode/{barcode}', [\App\Http\Controllers\Api\V1\ProductController::class, 'byBarcode']);
            Route::get('{product}', [\App\Http\Controllers\Api\V1\ProductController::class, 'show']);
        });

        // Transactions
        Route::prefix('transactions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\TransactionController::class, 'index']);
            Route::post('calculate', [\App\Http\Controllers\Api\V1\TransactionController::class, 'calculate']);
            Route::post('checkout', [\App\Http\Controllers\Api\V1\TransactionController::class, 'checkout']);
            Route::get('{transaction}', [\App\Http\Controllers\Api\V1\TransactionController::class, 'show']);
            Route::post('{transaction}/void', [\App\Http\Controllers\Api\V1\TransactionController::class, 'void']);
            Route::post('{transaction}/refund', [\App\Http\Controllers\Api\V1\TransactionController::class, 'refund']);
            Route::get('{transaction}/receipt', [\App\Http\Controllers\Api\V1\TransactionController::class, 'receipt']);
        });

        // Customers
        Route::prefix('customers')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\CustomerController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\CustomerController::class, 'store']);
            Route::get('search', [\App\Http\Controllers\Api\V1\CustomerController::class, 'search']);
            Route::get('{customer}', [\App\Http\Controllers\Api\V1\CustomerController::class, 'show']);
            Route::put('{customer}', [\App\Http\Controllers\Api\V1\CustomerController::class, 'update']);
            Route::get('{customer}/transactions', [\App\Http\Controllers\Api\V1\CustomerController::class, 'transactions']);
        });

        // Payment Methods
        Route::prefix('payment-methods')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\PaymentMethodController::class, 'index']);
            Route::get('types', [\App\Http\Controllers\Api\V1\PaymentMethodController::class, 'types']);
            Route::post('calculate-charge', [\App\Http\Controllers\Api\V1\PaymentMethodController::class, 'calculateCharge']);
            Route::get('{paymentMethod}', [\App\Http\Controllers\Api\V1\PaymentMethodController::class, 'show']);
        });

        // Discounts
        Route::prefix('discounts')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\DiscountController::class, 'index']);
            Route::post('validate', [\App\Http\Controllers\Api\V1\DiscountController::class, 'validateDiscount']);
        });

        // POS Sessions
        Route::prefix('sessions')->group(function () {
            Route::get('current', [\App\Http\Controllers\Api\V1\SessionController::class, 'current']);
            Route::post('open', [\App\Http\Controllers\Api\V1\SessionController::class, 'open']);
            Route::post('close', [\App\Http\Controllers\Api\V1\SessionController::class, 'close']);
            Route::get('{session}/report', [\App\Http\Controllers\Api\V1\SessionController::class, 'report']);
        });

        // Held Orders
        Route::prefix('held-orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\HeldOrderController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\HeldOrderController::class, 'store']);
            Route::get('{heldOrder}', [\App\Http\Controllers\Api\V1\HeldOrderController::class, 'show']);
            Route::delete('{heldOrder}', [\App\Http\Controllers\Api\V1\HeldOrderController::class, 'destroy']);
            Route::post('{heldOrder}/restore', [\App\Http\Controllers\Api\V1\HeldOrderController::class, 'restore']);
        });

        // Authorization (Manager PIN)
        Route::prefix('authorize')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\V1\AuthorizationController::class, 'authorize']);
            Route::get('check', [\App\Http\Controllers\Api\V1\AuthorizationController::class, 'check']);
            Route::get('managers', [\App\Http\Controllers\Api\V1\AuthorizationController::class, 'managers']);
            Route::get('settings', [\App\Http\Controllers\Api\V1\AuthorizationController::class, 'settings']);
            Route::get('logs', [\App\Http\Controllers\Api\V1\AuthorizationController::class, 'logs']);
        });

        // Floors & Tables
        Route::prefix('floors')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\FloorController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\FloorController::class, 'store']);
            Route::get('{floor}', [\App\Http\Controllers\Api\V1\FloorController::class, 'show']);
            Route::put('{floor}', [\App\Http\Controllers\Api\V1\FloorController::class, 'update']);
            Route::delete('{floor}', [\App\Http\Controllers\Api\V1\FloorController::class, 'destroy']);
            Route::get('{floor}/tables', [\App\Http\Controllers\Api\V1\FloorController::class, 'tables']);
        });

        Route::prefix('tables')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\TableController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\TableController::class, 'store']);
            Route::post('positions', [\App\Http\Controllers\Api\V1\TableController::class, 'updatePositions']);
            Route::get('{table}', [\App\Http\Controllers\Api\V1\TableController::class, 'show']);
            Route::put('{table}', [\App\Http\Controllers\Api\V1\TableController::class, 'update']);
            Route::delete('{table}', [\App\Http\Controllers\Api\V1\TableController::class, 'destroy']);
            Route::patch('{table}/status', [\App\Http\Controllers\Api\V1\TableController::class, 'updateStatus']);
            Route::post('{table}/open', [\App\Http\Controllers\Api\V1\TableController::class, 'open']);
            Route::post('{table}/close', [\App\Http\Controllers\Api\V1\TableController::class, 'close']);
            Route::get('{table}/sessions', [\App\Http\Controllers\Api\V1\TableController::class, 'sessions']);
            Route::post('{table}/move', [\App\Http\Controllers\Api\V1\TableController::class, 'move']);
        });

        // Subscription Management
        Route::prefix('subscription')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'show']);
            Route::post('subscribe', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'subscribe']);
            Route::post('trial', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'startTrial']);
            Route::post('upgrade', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'upgrade']);
            Route::get('upgrade/calculate', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'calculateUpgrade']);
            Route::post('cancel', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'cancel']);
            Route::post('reactivate', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'reactivate']);
            Route::get('invoices', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'invoices']);
            Route::get('invoices/{id}', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'showInvoice']);
            Route::get('features', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'features']);
            Route::get('features/{feature}', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'checkFeature']);
        });
    });
});

// ============================================================
// API v2 Routes - Enhanced for Flutter POS
// ============================================================

Route::prefix('v2')->group(function () {

    // ----------------------------------------------------------
    // Public Routes (No Authentication Required)
    // ----------------------------------------------------------

    // Auth - reuse v1 endpoints for login
    Route::prefix('auth')->group(function () {
        Route::post('login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
        Route::post('pin-login', [\App\Http\Controllers\Api\V1\AuthController::class, 'pinLogin']);
    });

    // Subscription Plans (Public)
    Route::prefix('subscription-plans')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\SubscriptionPlanController::class, 'index']);
        Route::get('compare', [\App\Http\Controllers\Api\V1\SubscriptionPlanController::class, 'compare']);
        Route::get('slug/{slug}', [\App\Http\Controllers\Api\V1\SubscriptionPlanController::class, 'showBySlug']);
        Route::get('{id}', [\App\Http\Controllers\Api\V1\SubscriptionPlanController::class, 'show']);
    });

    // ----------------------------------------------------------
    // Protected Routes (Authentication Required)
    // ----------------------------------------------------------
    Route::middleware(['auth:sanctum'])->group(function () {

        // Reuse v1 endpoints that don't need changes
        Route::prefix('auth')->group(function () {
            Route::post('logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
            Route::get('me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me']);
            Route::put('profile', [\App\Http\Controllers\Api\V1\AuthController::class, 'updateProfile']);
            Route::put('pin', [\App\Http\Controllers\Api\V1\AuthController::class, 'updatePin']);
        });

        // Outlets - reuse v1
        Route::prefix('outlets')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\OutletController::class, 'index']);
            Route::get('{outlet}', [\App\Http\Controllers\Api\V1\OutletController::class, 'show']);
            Route::post('switch', [\App\Http\Controllers\Api\V1\OutletController::class, 'switch']);
        });

        // Sync API v2 - Enhanced for offline mode
        Route::prefix('sync')->group(function () {
            Route::get('master', [\App\Http\Controllers\Api\V2\SyncController::class, 'master']);
            Route::get('delta', [\App\Http\Controllers\Api\V2\SyncController::class, 'delta']);
            Route::post('transactions', [\App\Http\Controllers\Api\V2\SyncController::class, 'uploadTransactions']);
            Route::post('sessions', [\App\Http\Controllers\Api\V2\SyncController::class, 'syncSession']);
            Route::get('customers/search', [\App\Http\Controllers\Api\V2\SyncController::class, 'searchCustomers']);
        });

        // Categories - reuse v1
        Route::prefix('categories')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\CategoryController::class, 'index']);
            Route::get('{category}', [\App\Http\Controllers\Api\V1\CategoryController::class, 'show']);
            Route::get('{category}/products', [\App\Http\Controllers\Api\V1\CategoryController::class, 'products']);
        });

        // Products - reuse v1
        Route::prefix('products')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\ProductController::class, 'index']);
            Route::get('search', [\App\Http\Controllers\Api\V1\ProductController::class, 'search']);
            Route::get('barcode/{barcode}', [\App\Http\Controllers\Api\V1\ProductController::class, 'byBarcode']);
            Route::get('{product}', [\App\Http\Controllers\Api\V1\ProductController::class, 'show']);
        });

        // Orders/Transactions API v2 - Enhanced with split payment
        Route::prefix('orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V2\OrderController::class, 'index']);
            Route::post('calculate', [\App\Http\Controllers\Api\V2\OrderController::class, 'calculate']);
            Route::post('checkout', [\App\Http\Controllers\Api\V2\OrderController::class, 'checkout']);
            Route::get('{order}', [\App\Http\Controllers\Api\V2\OrderController::class, 'show']);
            Route::post('{order}/void', [\App\Http\Controllers\Api\V2\OrderController::class, 'void']);
            Route::post('{order}/refund', [\App\Http\Controllers\Api\V2\OrderController::class, 'refund']);
            Route::get('{order}/receipt', [\App\Http\Controllers\Api\V2\OrderController::class, 'receipt']);
        });

        // Customers - reuse v1
        Route::prefix('customers')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\CustomerController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\CustomerController::class, 'store']);
            Route::get('search', [\App\Http\Controllers\Api\V1\CustomerController::class, 'search']);
            Route::get('{customer}', [\App\Http\Controllers\Api\V1\CustomerController::class, 'show']);
            Route::put('{customer}', [\App\Http\Controllers\Api\V1\CustomerController::class, 'update']);
            Route::get('{customer}/transactions', [\App\Http\Controllers\Api\V1\CustomerController::class, 'transactions']);
        });

        // Payment Methods - reuse v1
        Route::prefix('payment-methods')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\PaymentMethodController::class, 'index']);
            Route::get('types', [\App\Http\Controllers\Api\V1\PaymentMethodController::class, 'types']);
            Route::post('calculate-charge', [\App\Http\Controllers\Api\V1\PaymentMethodController::class, 'calculateCharge']);
            Route::get('{paymentMethod}', [\App\Http\Controllers\Api\V1\PaymentMethodController::class, 'show']);
        });

        // Discounts - reuse v1
        Route::prefix('discounts')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\DiscountController::class, 'index']);
            Route::post('validate', [\App\Http\Controllers\Api\V1\DiscountController::class, 'validateDiscount']);
        });

        // POS Sessions API v2 - Enhanced with history and active-any
        Route::prefix('sessions')->group(function () {
            Route::get('current', [\App\Http\Controllers\Api\V2\SessionController::class, 'current']);
            Route::post('open', [\App\Http\Controllers\Api\V2\SessionController::class, 'open']);
            Route::post('close', [\App\Http\Controllers\Api\V2\SessionController::class, 'close']);
            Route::get('history', [\App\Http\Controllers\Api\V2\SessionController::class, 'history']);
            Route::get('active-any', [\App\Http\Controllers\Api\V2\SessionController::class, 'activeAny']);
            Route::get('{session}/report', [\App\Http\Controllers\Api\V2\SessionController::class, 'report']);
        });

        // Held Orders - reuse v1
        Route::prefix('held-orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\HeldOrderController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\HeldOrderController::class, 'store']);
            Route::get('{heldOrder}', [\App\Http\Controllers\Api\V1\HeldOrderController::class, 'show']);
            Route::delete('{heldOrder}', [\App\Http\Controllers\Api\V1\HeldOrderController::class, 'destroy']);
            Route::post('{heldOrder}/restore', [\App\Http\Controllers\Api\V1\HeldOrderController::class, 'restore']);
        });

        // Authorization - reuse v1
        Route::prefix('authorize')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\V1\AuthorizationController::class, 'authorize']);
            Route::get('check', [\App\Http\Controllers\Api\V1\AuthorizationController::class, 'check']);
            Route::get('managers', [\App\Http\Controllers\Api\V1\AuthorizationController::class, 'managers']);
            Route::get('settings', [\App\Http\Controllers\Api\V1\AuthorizationController::class, 'settings']);
            Route::get('logs', [\App\Http\Controllers\Api\V1\AuthorizationController::class, 'logs']);
        });

        // Floors & Tables - reuse v1
        Route::prefix('floors')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\FloorController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\FloorController::class, 'store']);
            Route::get('{floor}', [\App\Http\Controllers\Api\V1\FloorController::class, 'show']);
            Route::put('{floor}', [\App\Http\Controllers\Api\V1\FloorController::class, 'update']);
            Route::delete('{floor}', [\App\Http\Controllers\Api\V1\FloorController::class, 'destroy']);
            Route::get('{floor}/tables', [\App\Http\Controllers\Api\V1\FloorController::class, 'tables']);
        });

        Route::prefix('tables')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\TableController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\TableController::class, 'store']);
            Route::post('positions', [\App\Http\Controllers\Api\V1\TableController::class, 'updatePositions']);
            Route::get('{table}', [\App\Http\Controllers\Api\V1\TableController::class, 'show']);
            Route::put('{table}', [\App\Http\Controllers\Api\V1\TableController::class, 'update']);
            Route::delete('{table}', [\App\Http\Controllers\Api\V1\TableController::class, 'destroy']);
            Route::patch('{table}/status', [\App\Http\Controllers\Api\V1\TableController::class, 'updateStatus']);
            Route::post('{table}/open', [\App\Http\Controllers\Api\V1\TableController::class, 'open']);
            Route::post('{table}/close', [\App\Http\Controllers\Api\V1\TableController::class, 'close']);
            Route::get('{table}/sessions', [\App\Http\Controllers\Api\V1\TableController::class, 'sessions']);
            Route::post('{table}/move', [\App\Http\Controllers\Api\V1\TableController::class, 'move']);
        });

        // Subscription - reuse v1
        Route::prefix('subscription')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'show']);
            Route::post('subscribe', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'subscribe']);
            Route::post('trial', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'startTrial']);
            Route::post('upgrade', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'upgrade']);
            Route::get('upgrade/calculate', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'calculateUpgrade']);
            Route::post('cancel', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'cancel']);
            Route::post('reactivate', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'reactivate']);
            Route::get('invoices', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'invoices']);
            Route::get('invoices/{id}', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'showInvoice']);
            Route::get('features', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'features']);
            Route::get('features/{feature}', [\App\Http\Controllers\Api\V1\SubscriptionController::class, 'checkFeature']);
        });

        // ==============================================================
        // NEW v2 ENDPOINTS - Cash Drawer, Reports, Settings, Inventory
        // ==============================================================

        // Cash Drawer Management
        Route::prefix('cash-drawer')->group(function () {
            Route::get('status', [\App\Http\Controllers\Api\V2\CashDrawerController::class, 'status']);
            Route::get('logs', [\App\Http\Controllers\Api\V2\CashDrawerController::class, 'logs']);
            Route::get('balance', [\App\Http\Controllers\Api\V2\CashDrawerController::class, 'balance']);
            Route::post('cash-in', [\App\Http\Controllers\Api\V2\CashDrawerController::class, 'cashIn']);
            Route::post('cash-out', [\App\Http\Controllers\Api\V2\CashDrawerController::class, 'cashOut']);
            Route::post('open', [\App\Http\Controllers\Api\V2\CashDrawerController::class, 'open']);
        });

        // Reports
        Route::prefix('reports')->group(function () {
            // Sales Reports
            Route::prefix('sales')->group(function () {
                Route::get('summary', [\App\Http\Controllers\Api\V2\ReportsController::class, 'salesSummary']);
                Route::get('by-payment-method', [\App\Http\Controllers\Api\V2\ReportsController::class, 'byPaymentMethod']);
                Route::get('by-category', [\App\Http\Controllers\Api\V2\ReportsController::class, 'byCategory']);
                Route::get('by-product', [\App\Http\Controllers\Api\V2\ReportsController::class, 'byProduct']);
                Route::get('hourly', [\App\Http\Controllers\Api\V2\ReportsController::class, 'hourlySales']);
                Route::get('daily', [\App\Http\Controllers\Api\V2\ReportsController::class, 'dailySales']);
            });

            // Session Reports
            Route::get('sessions/{session}', [\App\Http\Controllers\Api\V2\ReportsController::class, 'sessionReport']);
        });

        // Settings
        Route::prefix('settings')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V2\SettingsController::class, 'index']);
            Route::get('outlet', [\App\Http\Controllers\Api\V2\SettingsController::class, 'outlet']);
            Route::get('pos', [\App\Http\Controllers\Api\V2\SettingsController::class, 'pos']);
            Route::get('authorization', [\App\Http\Controllers\Api\V2\SettingsController::class, 'authorization']);
            Route::get('receipt', [\App\Http\Controllers\Api\V2\SettingsController::class, 'receipt']);
            Route::get('printer', [\App\Http\Controllers\Api\V2\SettingsController::class, 'printer']);
            Route::get('subscription', [\App\Http\Controllers\Api\V2\SettingsController::class, 'subscription']);
            Route::get('features', [\App\Http\Controllers\Api\V2\SettingsController::class, 'features']);
            Route::get('features/{feature}', [\App\Http\Controllers\Api\V2\SettingsController::class, 'checkFeature']);
        });

        // Inventory Management
        Route::prefix('inventory')->group(function () {
            Route::get('items', [\App\Http\Controllers\Api\V2\InventoryController::class, 'items']);
            Route::get('items/{item}', [\App\Http\Controllers\Api\V2\InventoryController::class, 'show']);
            Route::get('items/{item}/history', [\App\Http\Controllers\Api\V2\InventoryController::class, 'history']);
            Route::get('stock', [\App\Http\Controllers\Api\V2\InventoryController::class, 'stock']);
            Route::get('products/{product}/stock', [\App\Http\Controllers\Api\V2\InventoryController::class, 'productStock']);
            Route::post('adjustments', [\App\Http\Controllers\Api\V2\InventoryController::class, 'createAdjustment']);
            Route::get('alerts/low-stock', [\App\Http\Controllers\Api\V2\InventoryController::class, 'lowStockAlerts']);
        });
    });
});
