<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthorizationController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DiscountController;
use App\Http\Controllers\Api\V1\FloorController;
use App\Http\Controllers\Api\V1\HeldOrderController;
use App\Http\Controllers\Api\V1\MobileSyncController;
use App\Http\Controllers\Api\V1\OutletController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\SubscriptionPlanController;
use App\Http\Controllers\Api\V1\TableController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V2\CashDrawerController;
use App\Http\Controllers\Api\V2\InventoryController;
use App\Http\Controllers\Api\V2\KDSController;
use App\Http\Controllers\Api\V2\OrderController;
use App\Http\Controllers\Api\V2\QrOrderApiController;
use App\Http\Controllers\Api\V2\ReportsController;
use App\Http\Controllers\Api\V2\SettingsController;
use App\Http\Controllers\Api\V2\SyncController;
use App\Http\Controllers\Api\V2\WaiterController;
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
        Route::post('login', [AuthController::class, 'login']);
        Route::post('pin-login', [AuthController::class, 'pinLogin']);
        Route::post('register', [AuthController::class, 'register']);
    });

    // Subscription Plans (Public - for pricing page)
    Route::prefix('subscription-plans')->group(function () {
        Route::get('/', [SubscriptionPlanController::class, 'index']);
        Route::get('compare', [SubscriptionPlanController::class, 'compare']);
        Route::get('slug/{slug}', [SubscriptionPlanController::class, 'showBySlug']);
        Route::get('{id}', [SubscriptionPlanController::class, 'show']);
    });

    // ----------------------------------------------------------
    // Protected Routes (Authentication Required)
    // ----------------------------------------------------------
    Route::middleware(['auth:sanctum'])->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::put('pin', [AuthController::class, 'updatePin']);
        });

        // Outlets
        Route::prefix('outlets')->group(function () {
            Route::get('/', [OutletController::class, 'index']);
            Route::get('{outlet}', [OutletController::class, 'show']);
            Route::post('switch', [OutletController::class, 'switch']);
        });

        // Mobile Sync (for offline-first POS)
        Route::prefix('mobile')->group(function () {
            Route::get('sync/master', [MobileSyncController::class, 'master']);
            Route::get('sync/delta', [MobileSyncController::class, 'delta']);
            Route::post('transactions/bulk', [MobileSyncController::class, 'uploadTransactions']);
            Route::post('sessions/sync', [MobileSyncController::class, 'syncSession']);
            Route::get('customers/search', [MobileSyncController::class, 'searchCustomers']);
        });

        // Categories
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('{category}', [CategoryController::class, 'show']);
            Route::get('{category}/products', [CategoryController::class, 'products']);
        });

        // Products
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::get('search', [ProductController::class, 'search']);
            Route::get('barcode/{barcode}', [ProductController::class, 'byBarcode']);
            Route::get('{product}', [ProductController::class, 'show']);
        });

        // Transactions
        Route::prefix('transactions')->group(function () {
            Route::get('/', [TransactionController::class, 'index']);
            Route::post('calculate', [TransactionController::class, 'calculate']);
            Route::post('checkout', [TransactionController::class, 'checkout']);
            Route::post('send-to-kitchen', [TransactionController::class, 'sendToKitchen']);
            Route::get('{transaction}', [TransactionController::class, 'show']);
            Route::post('{transaction}/pay', [TransactionController::class, 'pay']);
            Route::post('{transaction}/void', [TransactionController::class, 'void']);
            Route::post('{transaction}/refund', [TransactionController::class, 'refund']);
            Route::get('{transaction}/receipt', [TransactionController::class, 'receipt']);
        });

        // Customers
        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomerController::class, 'index']);
            Route::post('/', [CustomerController::class, 'store']);
            Route::get('search', [CustomerController::class, 'search']);
            Route::get('{customer}', [CustomerController::class, 'show']);
            Route::put('{customer}', [CustomerController::class, 'update']);
            Route::get('{customer}/transactions', [CustomerController::class, 'transactions']);
        });

        // Payment Methods
        Route::prefix('payment-methods')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'index']);
            Route::get('types', [PaymentMethodController::class, 'types']);
            Route::post('calculate-charge', [PaymentMethodController::class, 'calculateCharge']);
            Route::get('{paymentMethod}', [PaymentMethodController::class, 'show']);
        });

        // Discounts
        Route::prefix('discounts')->group(function () {
            Route::get('/', [DiscountController::class, 'index']);
            Route::post('validate', [DiscountController::class, 'validateDiscount']);
        });

        // POS Sessions
        Route::prefix('sessions')->group(function () {
            Route::get('current', [SessionController::class, 'current']);
            Route::post('open', [SessionController::class, 'open']);
            Route::post('close', [SessionController::class, 'close']);
            Route::get('{session}/report', [SessionController::class, 'report']);
        });

        // Held Orders
        Route::prefix('held-orders')->group(function () {
            Route::get('/', [HeldOrderController::class, 'index']);
            Route::post('/', [HeldOrderController::class, 'store']);
            Route::get('{heldOrder}', [HeldOrderController::class, 'show']);
            Route::delete('{heldOrder}', [HeldOrderController::class, 'destroy']);
            Route::post('{heldOrder}/restore', [HeldOrderController::class, 'restore']);
        });

        // Authorization (Manager PIN)
        Route::prefix('authorize')->group(function () {
            Route::post('/', [AuthorizationController::class, 'authorize']);
            Route::get('check', [AuthorizationController::class, 'check']);
            Route::get('managers', [AuthorizationController::class, 'managers']);
            Route::get('settings', [AuthorizationController::class, 'settings']);
            Route::get('logs', [AuthorizationController::class, 'logs']);
        });

        // Floors & Tables
        Route::prefix('floors')->group(function () {
            Route::get('/', [FloorController::class, 'index']);
            Route::post('/', [FloorController::class, 'store']);
            Route::get('{floor}', [FloorController::class, 'show']);
            Route::put('{floor}', [FloorController::class, 'update']);
            Route::delete('{floor}', [FloorController::class, 'destroy']);
            Route::get('{floor}/tables', [FloorController::class, 'tables']);
        });

        Route::prefix('tables')->group(function () {
            Route::get('/', [TableController::class, 'index']);
            Route::post('/', [TableController::class, 'store']);
            Route::post('positions', [TableController::class, 'updatePositions']);
            Route::get('{table}', [TableController::class, 'show']);
            Route::put('{table}', [TableController::class, 'update']);
            Route::delete('{table}', [TableController::class, 'destroy']);
            Route::patch('{table}/status', [TableController::class, 'updateStatus']);
            Route::post('{table}/open', [TableController::class, 'open']);
            Route::post('{table}/close', [TableController::class, 'close']);
            Route::get('{table}/sessions', [TableController::class, 'sessions']);
            Route::post('{table}/move', [TableController::class, 'move']);
        });

        // Subscription Management
        Route::prefix('subscription')->group(function () {
            Route::get('/', [SubscriptionController::class, 'show']);
            Route::post('subscribe', [SubscriptionController::class, 'subscribe']);
            Route::post('trial', [SubscriptionController::class, 'startTrial']);
            Route::post('upgrade', [SubscriptionController::class, 'upgrade']);
            Route::get('upgrade/calculate', [SubscriptionController::class, 'calculateUpgrade']);
            Route::post('cancel', [SubscriptionController::class, 'cancel']);
            Route::post('reactivate', [SubscriptionController::class, 'reactivate']);
            Route::get('invoices', [SubscriptionController::class, 'invoices']);
            Route::get('invoices/{id}', [SubscriptionController::class, 'showInvoice']);
            Route::get('features', [SubscriptionController::class, 'features']);
            Route::get('features/{feature}', [SubscriptionController::class, 'checkFeature']);
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
        Route::post('login', [AuthController::class, 'login']);
        Route::post('pin-login', [AuthController::class, 'pinLogin']);
    });

    // Subscription Plans (Public)
    Route::prefix('subscription-plans')->group(function () {
        Route::get('/', [SubscriptionPlanController::class, 'index']);
        Route::get('compare', [SubscriptionPlanController::class, 'compare']);
        Route::get('slug/{slug}', [SubscriptionPlanController::class, 'showBySlug']);
        Route::get('{id}', [SubscriptionPlanController::class, 'show']);
    });

    // KDS Auth (Public - PIN-based login)
    Route::prefix('kds/auth')->group(function () {
        Route::get('outlets', [KDSController::class, 'outlets']);
        Route::post('login', [KDSController::class, 'login']);
    });

    // Waiter Auth (Public - PIN-based login)
    Route::prefix('waiter/auth')->group(function () {
        Route::get('outlets', [WaiterController::class, 'outlets']);
        Route::post('login', [WaiterController::class, 'login']);
    });

    // ----------------------------------------------------------
    // Protected Routes (Authentication Required)
    // ----------------------------------------------------------
    Route::middleware(['auth:sanctum'])->group(function () {

        // Reuse v1 endpoints that don't need changes
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::put('pin', [AuthController::class, 'updatePin']);
        });

        // Outlets - reuse v1
        Route::prefix('outlets')->group(function () {
            Route::get('/', [OutletController::class, 'index']);
            Route::get('{outlet}', [OutletController::class, 'show']);
            Route::post('switch', [OutletController::class, 'switch']);
        });

        // Sync API v2 - Enhanced for offline mode
        Route::prefix('sync')->group(function () {
            Route::get('master', [SyncController::class, 'master']);
            Route::get('delta', [SyncController::class, 'delta']);
            Route::post('transactions', [SyncController::class, 'uploadTransactions']);
            Route::post('sessions', [SyncController::class, 'syncSession']);
            Route::get('customers/search', [SyncController::class, 'searchCustomers']);
        });

        // Categories - reuse v1
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('{category}', [CategoryController::class, 'show']);
            Route::get('{category}/products', [CategoryController::class, 'products']);
        });

        // Products - reuse v1
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::get('search', [ProductController::class, 'search']);
            Route::get('barcode/{barcode}', [ProductController::class, 'byBarcode']);
            Route::get('{product}', [ProductController::class, 'show']);
        });

        // Orders/Transactions API v2 - Enhanced with split payment
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('calculate', [OrderController::class, 'calculate']);
            Route::post('checkout', [OrderController::class, 'checkout']);
            Route::get('{order}', [OrderController::class, 'show']);
            Route::post('{order}/pay', [OrderController::class, 'pay']);
            Route::post('{order}/void', [OrderController::class, 'void']);
            Route::post('{order}/refund', [OrderController::class, 'refund']);
            Route::get('{order}/receipt', [OrderController::class, 'receipt']);
        });

        // Customers - reuse v1
        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomerController::class, 'index']);
            Route::post('/', [CustomerController::class, 'store']);
            Route::get('search', [CustomerController::class, 'search']);
            Route::get('{customer}', [CustomerController::class, 'show']);
            Route::put('{customer}', [CustomerController::class, 'update']);
            Route::get('{customer}/transactions', [CustomerController::class, 'transactions']);
        });

        // Payment Methods - reuse v1
        Route::prefix('payment-methods')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'index']);
            Route::get('types', [PaymentMethodController::class, 'types']);
            Route::post('calculate-charge', [PaymentMethodController::class, 'calculateCharge']);
            Route::get('{paymentMethod}', [PaymentMethodController::class, 'show']);
        });

        // Discounts - reuse v1
        Route::prefix('discounts')->group(function () {
            Route::get('/', [DiscountController::class, 'index']);
            Route::post('validate', [DiscountController::class, 'validateDiscount']);
        });

        // POS Sessions API v2 - Enhanced with history and active-any
        Route::prefix('sessions')->group(function () {
            Route::get('current', [App\Http\Controllers\Api\V2\SessionController::class, 'current']);
            Route::post('open', [App\Http\Controllers\Api\V2\SessionController::class, 'open']);
            Route::post('close', [App\Http\Controllers\Api\V2\SessionController::class, 'close']);
            Route::get('history', [App\Http\Controllers\Api\V2\SessionController::class, 'history']);
            Route::get('active-any', [App\Http\Controllers\Api\V2\SessionController::class, 'activeAny']);
            Route::get('{session}/report', [App\Http\Controllers\Api\V2\SessionController::class, 'report']);
        });

        // Held Orders - reuse v1
        Route::prefix('held-orders')->group(function () {
            Route::get('/', [HeldOrderController::class, 'index']);
            Route::post('/', [HeldOrderController::class, 'store']);
            Route::get('{heldOrder}', [HeldOrderController::class, 'show']);
            Route::delete('{heldOrder}', [HeldOrderController::class, 'destroy']);
            Route::post('{heldOrder}/restore', [HeldOrderController::class, 'restore']);
        });

        // Authorization - reuse v1
        Route::prefix('authorize')->group(function () {
            Route::post('/', [AuthorizationController::class, 'authorize']);
            Route::get('check', [AuthorizationController::class, 'check']);
            Route::get('managers', [AuthorizationController::class, 'managers']);
            Route::get('settings', [AuthorizationController::class, 'settings']);
            Route::get('logs', [AuthorizationController::class, 'logs']);
        });

        // Floors & Tables - reuse v1
        Route::prefix('floors')->group(function () {
            Route::get('/', [FloorController::class, 'index']);
            Route::post('/', [FloorController::class, 'store']);
            Route::get('{floor}', [FloorController::class, 'show']);
            Route::put('{floor}', [FloorController::class, 'update']);
            Route::delete('{floor}', [FloorController::class, 'destroy']);
            Route::get('{floor}/tables', [FloorController::class, 'tables']);
        });

        Route::prefix('tables')->group(function () {
            Route::get('/', [TableController::class, 'index']);
            Route::post('/', [TableController::class, 'store']);
            Route::post('positions', [TableController::class, 'updatePositions']);
            Route::get('{table}', [TableController::class, 'show']);
            Route::put('{table}', [TableController::class, 'update']);
            Route::delete('{table}', [TableController::class, 'destroy']);
            Route::patch('{table}/status', [TableController::class, 'updateStatus']);
            Route::post('{table}/open', [TableController::class, 'open']);
            Route::post('{table}/close', [TableController::class, 'close']);
            Route::get('{table}/sessions', [TableController::class, 'sessions']);
            Route::post('{table}/move', [TableController::class, 'move']);
        });

        // Subscription - reuse v1
        Route::prefix('subscription')->group(function () {
            Route::get('/', [SubscriptionController::class, 'show']);
            Route::post('subscribe', [SubscriptionController::class, 'subscribe']);
            Route::post('trial', [SubscriptionController::class, 'startTrial']);
            Route::post('upgrade', [SubscriptionController::class, 'upgrade']);
            Route::get('upgrade/calculate', [SubscriptionController::class, 'calculateUpgrade']);
            Route::post('cancel', [SubscriptionController::class, 'cancel']);
            Route::post('reactivate', [SubscriptionController::class, 'reactivate']);
            Route::get('invoices', [SubscriptionController::class, 'invoices']);
            Route::get('invoices/{id}', [SubscriptionController::class, 'showInvoice']);
            Route::get('features', [SubscriptionController::class, 'features']);
            Route::get('features/{feature}', [SubscriptionController::class, 'checkFeature']);
        });

        // ==============================================================
        // NEW v2 ENDPOINTS - Cash Drawer, Reports, Settings, Inventory
        // ==============================================================

        // Cash Drawer Management
        Route::prefix('cash-drawer')->group(function () {
            Route::get('status', [CashDrawerController::class, 'status']);
            Route::get('logs', [CashDrawerController::class, 'logs']);
            Route::get('balance', [CashDrawerController::class, 'balance']);
            Route::post('cash-in', [CashDrawerController::class, 'cashIn']);
            Route::post('cash-out', [CashDrawerController::class, 'cashOut']);
            Route::post('open', [CashDrawerController::class, 'open']);
        });

        // Reports
        Route::prefix('reports')->group(function () {
            // Sales Reports
            Route::prefix('sales')->group(function () {
                Route::get('summary', [ReportsController::class, 'salesSummary']);
                Route::get('by-payment-method', [ReportsController::class, 'byPaymentMethod']);
                Route::get('by-category', [ReportsController::class, 'byCategory']);
                Route::get('by-product', [ReportsController::class, 'byProduct']);
                Route::get('hourly', [ReportsController::class, 'hourlySales']);
                Route::get('daily', [ReportsController::class, 'dailySales']);
            });

            // Session Reports
            Route::get('sessions/{session}', [ReportsController::class, 'sessionReport']);
        });

        // Settings
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingsController::class, 'index']);
            Route::get('outlet', [SettingsController::class, 'outlet']);
            Route::get('pos', [SettingsController::class, 'pos']);
            Route::get('authorization', [SettingsController::class, 'authorization']);
            Route::get('receipt', [SettingsController::class, 'receipt']);
            Route::get('printer', [SettingsController::class, 'printer']);
            Route::get('subscription', [SettingsController::class, 'subscription']);
            Route::get('features', [SettingsController::class, 'features']);
            Route::get('features/{feature}', [SettingsController::class, 'checkFeature']);
        });

        // Inventory Management
        Route::prefix('inventory')->group(function () {
            Route::get('items', [InventoryController::class, 'items']);
            Route::get('items/{item}', [InventoryController::class, 'show']);
            Route::get('items/{item}/history', [InventoryController::class, 'history']);
            Route::get('stock', [InventoryController::class, 'stock']);
            Route::get('products/{product}/stock', [InventoryController::class, 'productStock']);
            Route::post('adjustments', [InventoryController::class, 'createAdjustment']);
            Route::get('alerts/low-stock', [InventoryController::class, 'lowStockAlerts']);
        });

        // Waiter App
        Route::prefix('waiter')->group(function () {
            // Auth
            Route::post('auth/logout', [WaiterController::class, 'logout']);

            // Floors
            Route::get('floors', [WaiterController::class, 'floors']);

            // Tables
            Route::get('tables', [WaiterController::class, 'tables']);
            Route::get('tables/{table}', [WaiterController::class, 'showTable']);
            Route::post('tables/{table}/open', [WaiterController::class, 'openTable']);
            Route::post('tables/{table}/close', [WaiterController::class, 'closeTable']);
            Route::patch('tables/{table}/status', [WaiterController::class, 'updateTableStatus']);

            // Menu
            Route::get('menu', [WaiterController::class, 'menu']);
            Route::get('categories', [WaiterController::class, 'categories']);

            // Orders
            Route::get('orders', [WaiterController::class, 'orders']);
            Route::post('orders', [WaiterController::class, 'createOrder']);
            Route::get('orders/{order}', [WaiterController::class, 'showOrder']);
            Route::post('orders/{order}/items', [WaiterController::class, 'addItems']);
            Route::post('orders/{order}/send', [WaiterController::class, 'sendToKitchen']);
            Route::patch('orders/{order}/pickup', [WaiterController::class, 'pickupOrder']);
        });

        // QR Orders
        Route::prefix('qr-orders')->group(function () {
            Route::get('/', [QrOrderApiController::class, 'index']);
            Route::get('count', [QrOrderApiController::class, 'count']);
            Route::get('{qrOrder}', [QrOrderApiController::class, 'show']);
            Route::post('{qrOrder}/approve', [QrOrderApiController::class, 'approve']);
            Route::post('{qrOrder}/complete', [QrOrderApiController::class, 'complete']);
            Route::post('{qrOrder}/cancel', [QrOrderApiController::class, 'cancel']);
        });

        // Kitchen Display System (KDS)
        Route::prefix('kds')->group(function () {
            // Kitchen Orders
            Route::get('orders', [KDSController::class, 'index']);
            Route::get('orders/{order}', [KDSController::class, 'show']);
            Route::post('orders/{order}/start', [KDSController::class, 'start']);
            Route::post('orders/{order}/ready', [KDSController::class, 'ready']);
            Route::post('orders/{order}/served', [KDSController::class, 'served']);
            Route::post('orders/{order}/cancel', [KDSController::class, 'cancel']);
            Route::post('orders/{order}/recall', [KDSController::class, 'recall']);
            Route::post('orders/{order}/bump', [KDSController::class, 'bump']);
            Route::post('orders/{order}/priority', [KDSController::class, 'priority']);
            // Item status
            Route::post('orders/{order}/items/{item}/start', [KDSController::class, 'startItem']);
            Route::post('orders/{order}/items/{item}/ready', [KDSController::class, 'readyItem']);
            // Kitchen Stations
            Route::get('stations', [KDSController::class, 'stations']);
            Route::post('stations', [KDSController::class, 'storeStation']);
            Route::put('stations/{station}', [KDSController::class, 'updateStation']);
            Route::delete('stations/{station}', [KDSController::class, 'destroyStation']);
            // Stats
            Route::get('stats', [KDSController::class, 'stats']);
        });
    });
});
