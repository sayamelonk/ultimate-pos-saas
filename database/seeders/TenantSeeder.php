<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin (no tenant)
        $superAdminRole = Role::where('slug', 'super-admin')->first();

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@ultimatepos.com'],
            [
                'tenant_id' => null,
                'password' => Hash::make('password'),
                'pin' => '123456',
                'name' => 'Super Admin',
                'is_active' => true,
            ]
        );
        if ($superAdmin->wasRecentlyCreated) {
            $superAdmin->roles()->attach($superAdminRole->id);
        }

        // Create Demo Tenant
        $tenant = Tenant::firstOrCreate(
            ['code' => 'DEMO001'],
            [
                'name' => 'Demo Restaurant',
                'email' => 'demo@restaurant.com',
                'phone' => '08123456789',
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'tax_percentage' => 11.00,
                'service_charge_percentage' => 5.00,
                'subscription_plan' => 'premium',
                'max_outlets' => 5,
                'is_active' => true,
            ]
        );

        // Create Demo Outlet
        $outlet = Outlet::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'address' => 'Jl. Sudirman No. 123',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '12190',
                'phone' => '021-1234567',
                'email' => 'main@restaurant.com',
                'opening_time' => '08:00',
                'closing_time' => '22:00',
                'is_active' => true,
            ]
        );

        // Create Tenant Owner
        $tenantOwnerRole = Role::where('slug', 'tenant-owner')->first();

        $owner = User::firstOrCreate(
            ['email' => 'owner@demo.com'],
            [
                'tenant_id' => $tenant->id,
                'password' => Hash::make('password'),
                'pin' => '111111',
                'name' => 'Demo Owner',
                'is_active' => true,
            ]
        );
        if ($owner->wasRecentlyCreated) {
            $owner->roles()->attach($tenantOwnerRole->id);
            $owner->outlets()->attach($outlet->id, ['is_default' => true]);
        }

        // Create Outlet Manager
        $managerRole = Role::where('slug', 'outlet-manager')->first();

        $manager = User::firstOrCreate(
            ['email' => 'manager@demo.com'],
            [
                'tenant_id' => $tenant->id,
                'password' => Hash::make('password'),
                'pin' => '222222',
                'name' => 'Demo Manager',
                'is_active' => true,
            ]
        );
        if ($manager->wasRecentlyCreated) {
            $manager->roles()->attach($managerRole->id);
            $manager->outlets()->attach($outlet->id, ['is_default' => true]);
        }

        // Create Cashier
        $cashierRole = Role::where('slug', 'cashier')->first();

        $cashier = User::firstOrCreate(
            ['email' => 'cashier@demo.com'],
            [
                'tenant_id' => $tenant->id,
                'password' => Hash::make('password'),
                'pin' => '333333',
                'name' => 'Demo Cashier',
                'is_active' => true,
            ]
        );
        if ($cashier->wasRecentlyCreated) {
            $cashier->roles()->attach($cashierRole->id);
            $cashier->outlets()->attach($outlet->id, ['is_default' => true]);
        }

        // Create Waiter
        $waiterRole = Role::where('slug', 'waiter')->first();

        $waiter = User::firstOrCreate(
            ['email' => 'waiter@demo.com'],
            [
                'tenant_id' => $tenant->id,
                'password' => Hash::make('password'),
                'pin' => '444444',
                'name' => 'Demo Waiter',
                'is_active' => true,
            ]
        );
        if ($waiter->wasRecentlyCreated) {
            $waiter->roles()->attach($waiterRole->id);
            $waiter->outlets()->attach($outlet->id, ['is_default' => true]);
        }

        // Create Kitchen Staff
        $kitchenRole = Role::where('slug', 'kitchen-staff')->first();

        $kitchen = User::firstOrCreate(
            ['email' => 'kitchen@demo.com'],
            [
                'tenant_id' => $tenant->id,
                'password' => Hash::make('password'),
                'pin' => '555555',
                'name' => 'Demo Kitchen',
                'is_active' => true,
            ]
        );
        if ($kitchen->wasRecentlyCreated) {
            $kitchen->roles()->attach($kitchenRole->id);
            $kitchen->outlets()->attach($outlet->id, ['is_default' => true]);
        }
    }
}
