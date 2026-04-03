<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Routes that are allowed without active subscription.
     *
     * @var array<string>
     */
    protected array $allowedRoutes = [
        'subscription.*',
        'logout',
        'locale.switch',
        'dashboard',
        'admin.dashboard',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if ($this->isRouteAllowed($request)) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            return $next($request);
        }

        $subscription = $tenant->activeSubscription;

        if (! $subscription || ! $subscription->isActive()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Subscription required',
                    'redirect' => route('subscription.plans'),
                ], 402);
            }

            return redirect()->route('subscription.plans')
                ->with('error', 'Silakan berlangganan untuk mengakses fitur ini.');
        }

        return $next($request);
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
