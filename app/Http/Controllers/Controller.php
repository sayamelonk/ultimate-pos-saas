<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Get the current tenant ID for the authenticated user.
     * For Super Admin, this will use the session-stored tenant or abort.
     */
    protected function getTenantId(): string
    {
        $user = auth()->user();

        // For super admin, require tenant_id from request/session
        if ($user->isSuperAdmin()) {
            $tenantId = request()->get('current_tenant_id') ?? session('current_tenant_id');
            if (! $tenantId) {
                abort(403, 'Super Admin must select a tenant first. Please go to Tenant Management and select a tenant to manage.');
            }

            return $tenantId;
        }

        return $user->tenant_id;
    }

    /**
     * Check if current user is Super Admin with a selected tenant context.
     */
    protected function hasTenantContext(): bool
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return session('current_tenant_id') !== null;
        }

        return $user->tenant_id !== null;
    }
}
