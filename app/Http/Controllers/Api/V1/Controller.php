<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Outlet;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Ultimate POS API',
    description: 'API documentation for Ultimate POS SaaS - Mobile POS Integration',
    contact: new OA\Contact(name: 'API Support', email: 'support@ultimatepos.com')
)]
#[OA\Server(url: '/api/v1', description: 'API v1')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Enter your Sanctum token'
)]
#[OA\Tag(name: 'Auth', description: 'Authentication endpoints')]
#[OA\Tag(name: 'Outlets', description: 'Outlet management')]
#[OA\Tag(name: 'Categories', description: 'Product categories')]
#[OA\Tag(name: 'Products', description: 'Product management')]
#[OA\Tag(name: 'Transactions', description: 'Transaction/Sales management')]
#[OA\Tag(name: 'Customers', description: 'Customer management')]
#[OA\Tag(name: 'Discounts', description: 'Discount management')]
#[OA\Tag(name: 'Sessions', description: 'POS Session management')]
#[OA\Tag(name: 'Mobile Sync', description: 'Mobile offline sync endpoints')]
#[OA\Tag(name: 'Floors & Tables', description: 'Floor and table management')]
#[OA\Tag(name: 'Held Orders', description: 'Held orders management')]
#[OA\Tag(name: 'Payment Methods', description: 'Payment method listing')]
#[OA\Tag(name: 'Authorization', description: 'Manager PIN authorization')]
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

        // Super admin has all permissions
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission($permission);
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

        // Super admin has all permissions
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
