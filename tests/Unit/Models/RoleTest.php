<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
    }

    // ==================== CREATION TESTS ====================

    public function test_can_create_role(): void
    {
        $role = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Manager',
            'slug' => 'manager',
        ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Manager',
            'slug' => 'manager',
        ]);
    }

    public function test_role_has_required_attributes(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNotNull($role->id);
        $this->assertNotNull($role->name);
        $this->assertNotNull($role->slug);
    }

    public function test_role_can_be_system_role(): void
    {
        $role = Role::factory()->system()->create(['tenant_id' => null]);

        $this->assertTrue($role->is_system);
        $this->assertNull($role->tenant_id);
    }

    public function test_role_can_be_tenant_specific(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($role->is_system);
        $this->assertEquals($this->tenant->id, $role->tenant_id);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_role_belongs_to_tenant(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(Tenant::class, $role->tenant);
        $this->assertEquals($this->tenant->id, $role->tenant->id);
    }

    public function test_role_has_many_users(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $user1 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user2 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $role->users()->attach([$user1->id, $user2->id]);

        $this->assertCount(2, $role->users);
    }

    public function test_role_has_many_permissions(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();

        $role->permissions()->attach([$permission1->id, $permission2->id]);

        $this->assertCount(2, $role->permissions);
    }

    // ==================== PERMISSION MANAGEMENT TESTS ====================

    public function test_give_permission_adds_permission_to_role(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $permission = Permission::factory()->create();

        $role->givePermission($permission);

        $this->assertTrue($role->permissions->contains($permission));
    }

    public function test_give_permission_does_not_duplicate(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $permission = Permission::factory()->create();

        $role->givePermission($permission);
        $role->givePermission($permission);

        $this->assertCount(1, $role->permissions);
    }

    public function test_revoke_permission_removes_permission_from_role(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $permission = Permission::factory()->create();

        $role->givePermission($permission);
        $this->assertTrue($role->permissions->contains($permission));

        $role->revokePermission($permission);
        $role->refresh();

        $this->assertFalse($role->permissions->contains($permission));
    }

    public function test_sync_permissions_replaces_all_permissions(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();
        $permission3 = Permission::factory()->create();

        $role->givePermission($permission1);
        $this->assertCount(1, $role->permissions);

        $role->syncPermissions([$permission2->id, $permission3->id]);
        $role->refresh();

        $this->assertCount(2, $role->permissions);
        $this->assertFalse($role->permissions->contains($permission1));
        $this->assertTrue($role->permissions->contains($permission2));
        $this->assertTrue($role->permissions->contains($permission3));
    }

    public function test_sync_permissions_can_clear_all_permissions(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();

        $role->permissions()->attach([$permission1->id, $permission2->id]);
        $this->assertCount(2, $role->permissions);

        $role->syncPermissions([]);
        $role->refresh();

        $this->assertCount(0, $role->permissions);
    }

    // ==================== HAS PERMISSION TESTS ====================

    public function test_has_permission_returns_true_when_role_has_permission(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $permission = Permission::factory()->create(['slug' => 'view-products']);

        $role->givePermission($permission);

        $this->assertTrue($role->hasPermission('view-products'));
    }

    public function test_has_permission_returns_false_when_role_does_not_have_permission(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($role->hasPermission('view-products'));
    }

    public function test_has_permission_is_case_sensitive(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $permission = Permission::factory()->create(['slug' => 'view-products']);

        $role->givePermission($permission);

        $this->assertTrue($role->hasPermission('view-products'));
        $this->assertFalse($role->hasPermission('View-Products'));
    }

    // ==================== CASTING TESTS ====================

    public function test_is_system_is_cast_to_boolean(): void
    {
        $role = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_system' => 1,
        ]);

        $this->assertIsBool($role->is_system);
        $this->assertTrue($role->is_system);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_system_state(): void
    {
        $role = Role::factory()->system()->create();

        $this->assertTrue($role->is_system);
    }

    public function test_factory_for_tenant_state(): void
    {
        $role = Role::factory()->forTenant($this->tenant)->create();

        $this->assertEquals($this->tenant->id, $role->tenant_id);
    }

    public function test_factory_super_admin_state(): void
    {
        $role = Role::factory()->superAdmin()->create();

        $this->assertEquals('Super Admin', $role->name);
        $this->assertEquals('super-admin', $role->slug);
        $this->assertTrue($role->is_system);
    }

    public function test_factory_tenant_owner_state(): void
    {
        $role = Role::factory()->tenantOwner()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals('Tenant Owner', $role->name);
        $this->assertEquals('tenant-owner', $role->slug);
        $this->assertTrue($role->is_system);
    }

    public function test_factory_manager_state(): void
    {
        $role = Role::factory()->manager()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals('Manager', $role->name);
        $this->assertEquals('manager', $role->slug);
        $this->assertFalse($role->is_system);
    }

    public function test_factory_outlet_manager_state(): void
    {
        $role = Role::factory()->outletManager()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals('Outlet Manager', $role->name);
        $this->assertEquals('outlet-manager', $role->slug);
    }

    public function test_factory_supervisor_state(): void
    {
        $role = Role::factory()->supervisor()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals('Supervisor', $role->name);
        $this->assertEquals('supervisor', $role->slug);
    }

    public function test_factory_cashier_state(): void
    {
        $role = Role::factory()->cashier()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals('Cashier', $role->name);
        $this->assertEquals('cashier', $role->slug);
    }

    public function test_factory_waiter_state(): void
    {
        $role = Role::factory()->waiter()->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals('Waiter', $role->name);
        $this->assertEquals('waiter', $role->slug);
    }

    public function test_factory_with_slug_state(): void
    {
        $role = Role::factory()->withSlug('custom-role')->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals('custom-role', $role->slug);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_role_uses_uuid(): void
    {
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNotNull($role->id);
        $this->assertIsString($role->id);
        // UUID format check
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $role->id
        );
    }

    // ==================== TENANT ISOLATION TESTS ====================

    public function test_tenant_roles_are_isolated(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Role::factory()->count(3)->create(['tenant_id' => $tenant1->id]);
        Role::factory()->count(2)->create(['tenant_id' => $tenant2->id]);

        $tenant1Roles = Role::where('tenant_id', $tenant1->id)->get();
        $tenant2Roles = Role::where('tenant_id', $tenant2->id)->get();

        $this->assertCount(3, $tenant1Roles);
        $this->assertCount(2, $tenant2Roles);
    }

    public function test_system_roles_have_no_tenant(): void
    {
        $systemRole = Role::factory()->system()->create(['tenant_id' => null]);

        $this->assertNull($systemRole->tenant_id);
        $this->assertNull($systemRole->tenant);
    }

    // ==================== MULTIPLE ROLES TESTS ====================

    public function test_user_can_have_multiple_roles(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role1 = Role::factory()->manager()->create(['tenant_id' => $this->tenant->id]);
        $role2 = Role::factory()->cashier()->create(['tenant_id' => $this->tenant->id]);

        $user->roles()->attach([$role1->id, $role2->id]);

        $this->assertCount(2, $user->roles);
        $this->assertTrue($user->hasRole('manager'));
        $this->assertTrue($user->hasRole('cashier'));
    }

    public function test_role_can_have_multiple_users(): void
    {
        $role = Role::factory()->cashier()->create(['tenant_id' => $this->tenant->id]);
        $user1 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user2 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user3 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $role->users()->attach([$user1->id, $user2->id, $user3->id]);

        $this->assertCount(3, $role->users);
    }
}
