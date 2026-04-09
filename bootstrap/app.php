<?php

use App\Http\Middleware\BlockFrozenWrite;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckSubscriptionFeature;
use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantScope;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'tenant' => EnsureTenantScope::class,
            'permission' => CheckPermission::class,
            'subscription' => EnsureActiveSubscription::class,
            'subscription.status' => CheckSubscriptionStatus::class,
            'feature' => CheckSubscriptionFeature::class,
            'frozen.block' => BlockFrozenWrite::class,
            'super-admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            Log::channel('daily')->error('═══════════════════════════════════════════════════════');
            Log::channel('daily')->error('AUTHENTICATION FAILED');
            Log::channel('daily')->error('═══════════════════════════════════════════════════════');
            Log::channel('daily')->error('URL: '.$request->fullUrl());
            Log::channel('daily')->error('Method: '.$request->method());
            Log::channel('daily')->error('Headers:', [
                'Authorization' => $request->header('Authorization') ? 'Bearer ***'.substr($request->header('Authorization'), -10) : 'MISSING',
                'X-Outlet-Id' => $request->header('X-Outlet-Id') ?? 'MISSING',
            ]);
            Log::channel('daily')->error('Exception: '.$e->getMessage());
            Log::channel('daily')->error('Guards: '.implode(', ', $e->guards()));
            Log::channel('daily')->error('═══════════════════════════════════════════════════════');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'debug' => [
                        'has_authorization_header' => $request->hasHeader('Authorization'),
                        'token_prefix' => $request->header('Authorization') ? substr($request->header('Authorization'), 0, 10) : null,
                    ],
                ], 401);
            }
        });
    })->create();
