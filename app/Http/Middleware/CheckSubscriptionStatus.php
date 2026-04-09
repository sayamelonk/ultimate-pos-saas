<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * Check if tenant's subscription is active (trial, active, or in grace period).
     * Frozen accounts can only view data, not create/modify.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $mode = 'read'): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tenant) {
            return $next($request);
        }

        $tenant = $user->tenant;
        $subscription = $tenant->activeSubscription;

        // No subscription - redirect to subscribe page
        if (! $subscription) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No active subscription. Please subscribe to continue.',
                    'redirect' => route('subscription.plans'),
                ], 403);
            }

            return redirect()->route('subscription.plans')
                ->with('warning', 'Please subscribe to continue using the system.');
        }

        // Account is frozen - can only read data
        if ($tenant->isFrozen() && $mode === 'write') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account is frozen. Please renew your subscription to create or modify data.',
                    'status' => 'frozen',
                    'redirect' => route('subscription.plans'),
                ], 403);
            }

            return back()->with('error', 'Your account is frozen. Please renew your subscription to create or modify data.');
        }

        // Check if subscription allows system use
        if (! $subscription->canUseSystem()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your subscription has expired. Please renew to continue.',
                    'status' => 'expired',
                    'redirect' => route('subscription.plans'),
                ], 403);
            }

            return redirect()->route('subscription.plans')
                ->with('warning', 'Your subscription has expired. Please renew to continue.');
        }

        return $next($request);
    }
}
