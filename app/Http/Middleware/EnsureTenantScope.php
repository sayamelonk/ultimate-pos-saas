<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantScope
{
    /**
     * Handle an incoming request.
     *
     * Ensures that non-super-admin users can only access data within their tenant.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super admins can access everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Ensure user has a tenant (either directly or via outlet)
        $tenantId = $user->tenant_id;

        // If no direct tenant_id, try to get from default outlet
        if (! $tenantId) {
            $defaultOutlet = $user->outlets()->wherePivot('is_default', true)->first();
            if ($defaultOutlet) {
                $tenantId = $defaultOutlet->tenant_id;
                // Update user's tenant_id for consistency
                $user->update(['tenant_id' => $tenantId]);
            }
        }

        // Still no tenant? Try first assigned outlet
        if (! $tenantId) {
            $firstOutlet = $user->outlets()->first();
            if ($firstOutlet) {
                $tenantId = $firstOutlet->tenant_id;
                // Update user's tenant_id for consistency
                $user->update(['tenant_id' => $tenantId]);
            }
        }

        if (! $tenantId) {
            abort(403, 'You are not associated with any tenant.');
        }

        // Store tenant ID in request for easy access
        $request->merge(['current_tenant_id' => $tenantId]);

        // Share tenant with views
        view()->share('currentTenant', $user->tenant()->first());

        return $next($request);
    }
}
