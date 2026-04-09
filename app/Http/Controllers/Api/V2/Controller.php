<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

abstract class Controller extends BaseController
{
    use ApiResponse;

    /**
     * Get authenticated user
     */
    protected function user(): ?User
    {
        return auth()->user();
    }

    /**
     * Get user's tenant ID
     */
    protected function tenantId(): ?string
    {
        return $this->user()?->tenant_id;
    }

    /**
     * Get current outlet from request header or user's default
     */
    protected function currentOutlet(Request $request): ?Outlet
    {
        $outletId = $request->header('X-Outlet-Id');

        if ($outletId) {
            return Outlet::where('id', $outletId)
                ->where('tenant_id', $this->tenantId())
                ->first();
        }

        return $this->user()?->defaultOutlet();
    }

    /**
     * Get current outlet ID
     */
    protected function currentOutletId(Request $request): ?string
    {
        return $this->currentOutlet($request)?->id;
    }

    /**
     * Check if user can access the outlet
     */
    protected function canAccessOutlet(string $outletId): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->canAccessOutlet($outletId);
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission(string $permission): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission($permission);
    }

    /**
     * Get active POS session for current outlet
     */
    protected function getActiveSession(Request $request): ?PosSession
    {
        $outlet = $this->currentOutlet($request);

        if (! $outlet) {
            return null;
        }

        return PosSession::where('outlet_id', $outlet->id)
            ->where('status', PosSession::STATUS_OPEN)
            ->latest('opened_at')
            ->first();
    }

    /**
     * Get session for current user at outlet
     */
    protected function getUserSession(Request $request): ?PosSession
    {
        $outlet = $this->currentOutlet($request);
        $user = $this->user();

        if (! $outlet || ! $user) {
            return null;
        }

        return PosSession::where('outlet_id', $outlet->id)
            ->where('user_id', $user->id)
            ->where('status', PosSession::STATUS_OPEN)
            ->first();
    }

    /**
     * Parse date range from request
     *
     * @return array{from: Carbon, to: Carbon}
     */
    protected function parseDateRange(Request $request): array
    {
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfDay();

        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        return compact('from', 'to');
    }

    /**
     * Get user's permissions as array
     */
    protected function userPermissions(): array
    {
        $user = $this->user();

        if (! $user) {
            return [];
        }

        if ($user->isSuperAdmin()) {
            return ['*'];
        }

        return $user->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->unique()
            ->values()
            ->toArray();
    }
}
