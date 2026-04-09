<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockFrozenWrite
{
    /**
     * Routes allowed even in frozen mode (read-only operations).
     *
     * @var array<string>
     */
    protected array $allowedRoutes = [
        'subscription.*',
        'logout',
        'dashboard',
        'admin.dashboard',
        'locale.switch',
        'profile.*',
        '*.index',
        '*.show',
        '*.export',
        'reports.*',
    ];

    /**
     * Handle an incoming request.
     * Block all write operations (POST, PUT, PATCH, DELETE) for frozen accounts.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tenant) {
            return $next($request);
        }

        // Super admin can do anything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if tenant is frozen
        if (! $user->tenant->isFrozen()) {
            return $next($request);
        }

        // Allow GET requests (read operations)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Allow specific routes even for write operations
        if ($this->isRouteAllowed($request)) {
            return $next($request);
        }

        // Block all write operations for frozen accounts
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dalam mode frozen. Pilih paket berlangganan untuk melanjutkan.',
                'status' => 'frozen',
                'redirect' => route('subscription.plans'),
            ], 403);
        }

        return back()->with('error', 'Akun Anda dalam mode frozen. Anda hanya bisa melihat data. Pilih paket berlangganan untuk melanjutkan.');
    }

    /**
     * Check if current route is in allowed list.
     */
    protected function isRouteAllowed(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return false;
        }

        foreach ($this->allowedRoutes as $pattern) {
            if (str_contains($pattern, '*')) {
                $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).'$/';
                if (preg_match($regex, $routeName)) {
                    return true;
                }
            } elseif ($routeName === $pattern) {
                return true;
            }
        }

        return false;
    }
}
