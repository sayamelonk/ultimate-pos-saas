<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DowngradeService
{
    /**
     * Handle downgrade from one plan to another.
     * Archive excess resources based on new plan limits.
     */
    public function handleDowngrade(Tenant $tenant, SubscriptionPlan $newPlan): array
    {
        $results = [
            'products_archived' => 0,
            'users_deactivated' => 0,
            'outlets_archived' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            // Archive excess products
            if ($newPlan->max_products !== -1) {
                $results['products_archived'] = $this->archiveExcessProducts($tenant, $newPlan->max_products);
            }

            // Deactivate excess users
            if ($newPlan->max_users !== -1) {
                $results['users_deactivated'] = $this->deactivateExcessUsers($tenant, $newPlan->max_users);
            }

            // Archive excess outlets
            if ($newPlan->max_outlets !== -1) {
                $results['outlets_archived'] = $this->archiveExcessOutlets($tenant, $newPlan->max_outlets);
            }

            DB::commit();

            Log::info('Downgrade completed', [
                'tenant_id' => $tenant->id,
                'new_plan' => $newPlan->slug,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();

            Log::error('Downgrade failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Archive excess products, keeping the most recently updated ones.
     */
    protected function archiveExcessProducts(Tenant $tenant, int $limit): int
    {
        $currentCount = Product::where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->count();

        if ($currentCount <= $limit) {
            return 0;
        }

        $toArchive = $currentCount - $limit;

        // Get IDs of products to keep (most recently updated)
        $keepIds = Product::where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->pluck('id');

        // Archive the rest
        $archived = Product::where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->whereNotIn('id', $keepIds)
            ->update([
                'deleted_at' => now(),
                'is_active' => false,
            ]);

        Log::info('Products archived due to downgrade', [
            'tenant_id' => $tenant->id,
            'archived_count' => $archived,
            'new_limit' => $limit,
        ]);

        return $archived;
    }

    /**
     * Deactivate excess users, keeping owner and most recently active ones.
     */
    protected function deactivateExcessUsers(Tenant $tenant, int $limit): int
    {
        // Don't count tenant owner in limit
        $owner = $tenant->users()
            ->whereHas('roles', function ($q) {
                $q->where('slug', 'tenant-owner');
            })
            ->first();

        $currentCount = User::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        if ($currentCount <= $limit) {
            return 0;
        }

        $toDeactivate = $currentCount - $limit;

        // Keep owner and most recently active users
        $keepIds = User::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->when($owner, fn ($q) => $q->where('id', '!=', $owner->id))
            ->orderBy('last_login_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->limit($limit - 1) // -1 for owner
            ->pluck('id');

        if ($owner) {
            $keepIds->push($owner->id);
        }

        // Deactivate the rest
        $deactivated = User::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotIn('id', $keepIds)
            ->update(['is_active' => false]);

        Log::info('Users deactivated due to downgrade', [
            'tenant_id' => $tenant->id,
            'deactivated_count' => $deactivated,
            'new_limit' => $limit,
        ]);

        return $deactivated;
    }

    /**
     * Archive excess outlets, keeping the main/first outlet and most active ones.
     */
    protected function archiveExcessOutlets(Tenant $tenant, int $limit): int
    {
        $currentCount = Outlet::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        if ($currentCount <= $limit) {
            return 0;
        }

        // Keep the main outlet (first created or marked as main) and most active ones
        $mainOutlet = Outlet::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'asc')
            ->first();

        $keepIds = Outlet::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->when($mainOutlet, fn ($q) => $q->where('id', '!=', $mainOutlet->id))
            ->orderBy('updated_at', 'desc')
            ->limit($limit - 1) // -1 for main outlet
            ->pluck('id');

        if ($mainOutlet) {
            $keepIds->push($mainOutlet->id);
        }

        // Archive the rest
        $archived = Outlet::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotIn('id', $keepIds)
            ->update(['is_active' => false]);

        Log::info('Outlets archived due to downgrade', [
            'tenant_id' => $tenant->id,
            'archived_count' => $archived,
            'new_limit' => $limit,
        ]);

        return $archived;
    }

    /**
     * Check if tenant can downgrade to a specific plan.
     * Returns info about what will be archived.
     */
    public function canDowngrade(Tenant $tenant, SubscriptionPlan $newPlan): array
    {
        $currentProducts = Product::where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->count();

        $currentUsers = User::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        $currentOutlets = Outlet::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        $result = [
            'can_downgrade' => true,
            'products' => [
                'current' => $currentProducts,
                'limit' => $newPlan->max_products,
                'to_archive' => $newPlan->max_products !== -1
                    ? max(0, $currentProducts - $newPlan->max_products)
                    : 0,
            ],
            'users' => [
                'current' => $currentUsers,
                'limit' => $newPlan->max_users,
                'to_deactivate' => $newPlan->max_users !== -1
                    ? max(0, $currentUsers - $newPlan->max_users)
                    : 0,
            ],
            'outlets' => [
                'current' => $currentOutlets,
                'limit' => $newPlan->max_outlets,
                'to_archive' => $newPlan->max_outlets !== -1
                    ? max(0, $currentOutlets - $newPlan->max_outlets)
                    : 0,
            ],
        ];

        return $result;
    }

    /**
     * Restore archived resources when upgrading.
     */
    public function handleUpgrade(Tenant $tenant, SubscriptionPlan $newPlan): array
    {
        $results = [
            'products_restored' => 0,
            'users_activated' => 0,
            'outlets_activated' => 0,
        ];

        // Restore products if new limit allows
        if ($newPlan->max_products === -1) {
            $results['products_restored'] = Product::where('tenant_id', $tenant->id)
                ->onlyTrashed()
                ->whereNotNull('deleted_at')
                ->restore();
        } else {
            $canRestore = $newPlan->max_products - Product::where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->count();

            if ($canRestore > 0) {
                $restored = Product::where('tenant_id', $tenant->id)
                    ->onlyTrashed()
                    ->orderBy('deleted_at', 'desc')
                    ->limit($canRestore)
                    ->pluck('id');

                Product::whereIn('id', $restored)->restore();
                $results['products_restored'] = $restored->count();
            }
        }

        Log::info('Upgrade restoration completed', [
            'tenant_id' => $tenant->id,
            'new_plan' => $newPlan->slug,
            'results' => $results,
        ]);

        return $results;
    }
}
