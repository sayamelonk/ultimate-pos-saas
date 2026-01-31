<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin (System level - no tenant)
        $superAdmin = Role::create([
            'tenant_id' => null,
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Full system access',
            'is_system' => true,
        ]);
        $superAdmin->permissions()->attach(Permission::pluck('id'));

        // Tenant Owner (All permissions within tenant)
        $tenantOwner = Role::create([
            'tenant_id' => null,
            'name' => 'Tenant Owner',
            'slug' => 'tenant-owner',
            'description' => 'Full access to tenant resources',
            'is_system' => true,
        ]);
        $tenantOwner->permissions()->attach(Permission::pluck('id'));

        // Outlet Manager
        $outletManager = Role::create([
            'tenant_id' => null,
            'name' => 'Outlet Manager',
            'slug' => 'outlet-manager',
            'description' => 'Manage outlet operations',
            'is_system' => true,
        ]);
        $outletManager->permissions()->attach(
            Permission::whereIn('module', [
                'dashboard',
                'pos',
                'orders',
                'products',
                'categories',
                'inventory',
                'tables',
                'kitchen',
                'reports',
            ])->pluck('id')
        );

        // Cashier
        $cashier = Role::create([
            'tenant_id' => null,
            'name' => 'Cashier',
            'slug' => 'cashier',
            'description' => 'POS operations',
            'is_system' => true,
        ]);
        $cashier->permissions()->attach(
            Permission::whereIn('slug', [
                'dashboard.view',
                'pos.access',
                'pos.discount',
                'pos.history',
                'orders.view',
                'orders.create',
                'orders.update',
                'tables.view',
            ])->pluck('id')
        );

        // Waiter
        $waiter = Role::create([
            'tenant_id' => null,
            'name' => 'Waiter',
            'slug' => 'waiter',
            'description' => 'Take orders and serve tables',
            'is_system' => true,
        ]);
        $waiter->permissions()->attach(
            Permission::whereIn('slug', [
                'orders.view',
                'orders.create',
                'orders.update',
                'tables.view',
                'tables.transfer',
            ])->pluck('id')
        );

        // Kitchen Staff
        $kitchenStaff = Role::create([
            'tenant_id' => null,
            'name' => 'Kitchen Staff',
            'slug' => 'kitchen-staff',
            'description' => 'Kitchen display access',
            'is_system' => true,
        ]);
        $kitchenStaff->permissions()->attach(
            Permission::whereIn('slug', [
                'kitchen.kds',
                'orders.view',
            ])->pluck('id')
        );
    }
}
