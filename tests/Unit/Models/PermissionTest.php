<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    // ==================== CREATION TESTS ====================

    public function test_can_create_permission(): void
    {
        $permission = Permission::factory()->create([
            'name' => 'View Products',
            'slug' => 'view-products',
            'module' => 'products',
        ]);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'View Products',
            'slug' => 'view-products',
            'module' => 'products',
        ]);
    }

    public function test_permission_has_required_attributes(): void
    {
        $permission = Permission::factory()->create();

        $this->assertNotNull($permission->id);
        $this->assertNotNull($permission->name);
        $this->assertNotNull($permission->slug);
        $this->assertNotNull($permission->module);
    }

    public function test_permission_can_have_description(): void
    {
        $permission = Permission::factory()->create([
            'description' => 'Permission to view all products',
        ]);

        $this->assertEquals('Permission to view all products', $permission->description);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_permission_belongs_to_many_roles(): void
    {
        $permission = Permission::factory()->create();
        $tenant = Tenant::factory()->create();
        $role1 = Role::factory()->create(['tenant_id' => $tenant->id]);
        $role2 = Role::factory()->create(['tenant_id' => $tenant->id]);

        $permission->roles()->attach([$role1->id, $role2->id]);

        $this->assertCount(2, $permission->roles);
    }

    public function test_permission_can_be_attached_to_role(): void
    {
        $tenant = Tenant::factory()->create();
        $role = Role::factory()->create(['tenant_id' => $tenant->id]);
        $permission = Permission::factory()->create(['slug' => 'manage-products']);

        $role->permissions()->attach($permission->id);

        $this->assertTrue($role->hasPermission('manage-products'));
    }

    // ==================== GET MODULES TESTS ====================

    public function test_get_modules_returns_array(): void
    {
        $modules = Permission::getModules();

        $this->assertIsArray($modules);
    }

    public function test_get_modules_contains_expected_modules(): void
    {
        $modules = Permission::getModules();

        $this->assertArrayHasKey('dashboard', $modules);
        $this->assertArrayHasKey('pos', $modules);
        $this->assertArrayHasKey('orders', $modules);
        $this->assertArrayHasKey('products', $modules);
        $this->assertArrayHasKey('categories', $modules);
        $this->assertArrayHasKey('inventory', $modules);
        $this->assertArrayHasKey('tables', $modules);
        $this->assertArrayHasKey('kitchen', $modules);
        $this->assertArrayHasKey('reports', $modules);
        $this->assertArrayHasKey('outlets', $modules);
        $this->assertArrayHasKey('users', $modules);
        $this->assertArrayHasKey('roles', $modules);
        $this->assertArrayHasKey('settings', $modules);
    }

    public function test_get_modules_returns_display_names(): void
    {
        $modules = Permission::getModules();

        $this->assertEquals('Dashboard', $modules['dashboard']);
        $this->assertEquals('POS', $modules['pos']);
        $this->assertEquals('Products', $modules['products']);
        $this->assertEquals('Roles & Permissions', $modules['roles']);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_for_module_state(): void
    {
        $permission = Permission::factory()->forModule('inventory')->create();

        $this->assertEquals('inventory', $permission->module);
    }

    public function test_factory_with_slug_state(): void
    {
        $permission = Permission::factory()->withSlug('custom-permission')->create();

        $this->assertEquals('custom-permission', $permission->slug);
    }

    public function test_factory_with_name_state(): void
    {
        $permission = Permission::factory()->withName('Custom Permission')->create();

        $this->assertEquals('Custom Permission', $permission->name);
        $this->assertEquals('custom-permission', $permission->slug);
    }

    public function test_factory_view_products_state(): void
    {
        $permission = Permission::factory()->viewProducts()->create();

        $this->assertEquals('View Products', $permission->name);
        $this->assertEquals('view-products', $permission->slug);
        $this->assertEquals('products', $permission->module);
    }

    public function test_factory_manage_products_state(): void
    {
        $permission = Permission::factory()->manageProducts()->create();

        $this->assertEquals('Manage Products', $permission->name);
        $this->assertEquals('manage-products', $permission->slug);
        $this->assertEquals('products', $permission->module);
    }

    public function test_factory_access_pos_state(): void
    {
        $permission = Permission::factory()->accessPos()->create();

        $this->assertEquals('Access POS', $permission->name);
        $this->assertEquals('access-pos', $permission->slug);
        $this->assertEquals('pos', $permission->module);
    }

    public function test_factory_view_reports_state(): void
    {
        $permission = Permission::factory()->viewReports()->create();

        $this->assertEquals('View Reports', $permission->name);
        $this->assertEquals('view-reports', $permission->slug);
        $this->assertEquals('reports', $permission->module);
    }

    public function test_factory_manage_users_state(): void
    {
        $permission = Permission::factory()->manageUsers()->create();

        $this->assertEquals('Manage Users', $permission->name);
        $this->assertEquals('manage-users', $permission->slug);
        $this->assertEquals('users', $permission->module);
    }

    public function test_factory_manage_settings_state(): void
    {
        $permission = Permission::factory()->manageSettings()->create();

        $this->assertEquals('Manage Settings', $permission->name);
        $this->assertEquals('manage-settings', $permission->slug);
        $this->assertEquals('settings', $permission->module);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_permission_uses_uuid(): void
    {
        $permission = Permission::factory()->create();

        $this->assertNotNull($permission->id);
        $this->assertIsString($permission->id);
        // UUID format check
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $permission->id
        );
    }

    // ==================== PERMISSION GROUPING TESTS ====================

    public function test_permissions_can_be_grouped_by_module(): void
    {
        Permission::factory()->forModule('products')->count(3)->create();
        Permission::factory()->forModule('orders')->count(2)->create();
        Permission::factory()->forModule('reports')->count(4)->create();

        $productPermissions = Permission::where('module', 'products')->get();
        $orderPermissions = Permission::where('module', 'orders')->get();
        $reportPermissions = Permission::where('module', 'reports')->get();

        $this->assertCount(3, $productPermissions);
        $this->assertCount(2, $orderPermissions);
        $this->assertCount(4, $reportPermissions);
    }

    // ==================== UNIQUE SLUG TESTS ====================

    public function test_permissions_have_unique_slugs(): void
    {
        $permission1 = Permission::factory()->create(['slug' => 'view-products']);
        $permission2 = Permission::factory()->create(['slug' => 'manage-products']);

        $this->assertNotEquals($permission1->slug, $permission2->slug);
    }

    // ==================== ROLE PERMISSION CHAIN TESTS ====================

    public function test_role_with_multiple_permissions(): void
    {
        $tenant = Tenant::factory()->create();
        $role = Role::factory()->create(['tenant_id' => $tenant->id]);

        $viewProducts = Permission::factory()->viewProducts()->create();
        $manageProducts = Permission::factory()->manageProducts()->create();
        $accessPos = Permission::factory()->accessPos()->create();

        $role->syncPermissions([
            $viewProducts->id,
            $manageProducts->id,
            $accessPos->id,
        ]);

        $this->assertTrue($role->hasPermission('view-products'));
        $this->assertTrue($role->hasPermission('manage-products'));
        $this->assertTrue($role->hasPermission('access-pos'));
        $this->assertFalse($role->hasPermission('view-reports'));
    }

    public function test_permission_shared_across_roles(): void
    {
        $tenant = Tenant::factory()->create();
        $managerRole = Role::factory()->manager()->create(['tenant_id' => $tenant->id]);
        $cashierRole = Role::factory()->cashier()->create(['tenant_id' => $tenant->id]);

        $accessPos = Permission::factory()->accessPos()->create();

        $managerRole->givePermission($accessPos);
        $cashierRole->givePermission($accessPos);

        $this->assertCount(2, $accessPos->roles);
        $this->assertTrue($managerRole->hasPermission('access-pos'));
        $this->assertTrue($cashierRole->hasPermission('access-pos'));
    }
}
