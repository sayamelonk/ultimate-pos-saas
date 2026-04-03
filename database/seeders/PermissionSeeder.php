<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard'],

            // POS
            ['name' => 'Access POS', 'slug' => 'pos.access', 'module' => 'pos'],
            ['name' => 'Apply Discount', 'slug' => 'pos.discount', 'module' => 'pos'],
            ['name' => 'Void Order', 'slug' => 'pos.void', 'module' => 'pos'],
            ['name' => 'Refund Payment', 'slug' => 'pos.refund', 'module' => 'pos'],
            ['name' => 'Open Cash Drawer', 'slug' => 'pos.drawer', 'module' => 'pos'],
            ['name' => 'View Order History', 'slug' => 'pos.history', 'module' => 'pos'],

            // Orders
            ['name' => 'View Orders', 'slug' => 'orders.view', 'module' => 'orders'],
            ['name' => 'Create Orders', 'slug' => 'orders.create', 'module' => 'orders'],
            ['name' => 'Update Orders', 'slug' => 'orders.update', 'module' => 'orders'],
            ['name' => 'Cancel Orders', 'slug' => 'orders.cancel', 'module' => 'orders'],

            // Products
            ['name' => 'View Products', 'slug' => 'products.view', 'module' => 'products'],
            ['name' => 'Create Products', 'slug' => 'products.create', 'module' => 'products'],
            ['name' => 'Update Products', 'slug' => 'products.update', 'module' => 'products'],
            ['name' => 'Delete Products', 'slug' => 'products.delete', 'module' => 'products'],

            // Categories
            ['name' => 'View Categories', 'slug' => 'categories.view', 'module' => 'categories'],
            ['name' => 'Create Categories', 'slug' => 'categories.create', 'module' => 'categories'],
            ['name' => 'Update Categories', 'slug' => 'categories.update', 'module' => 'categories'],
            ['name' => 'Delete Categories', 'slug' => 'categories.delete', 'module' => 'categories'],

            // Inventory
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'module' => 'inventory'],
            ['name' => 'Manage Stock', 'slug' => 'inventory.manage', 'module' => 'inventory'],
            ['name' => 'Stock Adjustment', 'slug' => 'inventory.adjust', 'module' => 'inventory'],
            ['name' => 'Purchase Orders', 'slug' => 'inventory.purchase', 'module' => 'inventory'],

            // Tables
            ['name' => 'View Tables', 'slug' => 'tables.view', 'module' => 'tables'],
            ['name' => 'Manage Tables', 'slug' => 'tables.manage', 'module' => 'tables'],
            ['name' => 'Transfer Tables', 'slug' => 'tables.transfer', 'module' => 'tables'],
            ['name' => 'Merge Tables', 'slug' => 'tables.merge', 'module' => 'tables'],

            // Kitchen
            ['name' => 'Access KDS', 'slug' => 'kitchen.kds', 'module' => 'kitchen'],
            ['name' => 'Manage Kitchen', 'slug' => 'kitchen.manage', 'module' => 'kitchen'],

            // Reports
            ['name' => 'View Sales Reports', 'slug' => 'reports.sales', 'module' => 'reports'],
            ['name' => 'View Inventory Reports', 'slug' => 'reports.inventory', 'module' => 'reports'],
            ['name' => 'View Financial Reports', 'slug' => 'reports.financial', 'module' => 'reports'],
            ['name' => 'Export Reports', 'slug' => 'reports.export', 'module' => 'reports'],

            // Outlets
            ['name' => 'View Outlets', 'slug' => 'outlets.view', 'module' => 'outlets'],
            ['name' => 'Create Outlets', 'slug' => 'outlets.create', 'module' => 'outlets'],
            ['name' => 'Update Outlets', 'slug' => 'outlets.update', 'module' => 'outlets'],
            ['name' => 'Delete Outlets', 'slug' => 'outlets.delete', 'module' => 'outlets'],

            // Users
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'users'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'module' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'users'],

            // Roles
            ['name' => 'View Roles', 'slug' => 'roles.view', 'module' => 'roles'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'module' => 'roles'],
            ['name' => 'Update Roles', 'slug' => 'roles.update', 'module' => 'roles'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'module' => 'roles'],

            // Settings
            ['name' => 'View Settings', 'slug' => 'settings.view', 'module' => 'settings'],
            ['name' => 'Update Settings', 'slug' => 'settings.update', 'module' => 'settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
