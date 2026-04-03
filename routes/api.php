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
    });
});
