<?php

namespace Tests\Unit\Models;

use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
    }

    // ==================== CREATION TESTS ====================

    public function test_can_create_user(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_has_required_attributes(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNotNull($user->id);
        $this->assertNotNull($user->tenant_id);
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
    }

    public function test_password_is_hidden(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_pin_is_hidden(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'pin' => '1234',
        ]);
        $array = $user->toArray();

        $this->assertArrayNotHasKey('pin', $array);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_user_belongs_to_tenant(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(Tenant::class, $user->tenant);
        $this->assertEquals($this->tenant->id, $user->tenant->id);
    }

    public function test_user_has_many_roles(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role1 = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $role2 = Role::factory()->create(['tenant_id' => $this->tenant->id]);

        $user->roles()->attach([$role1->id, $role2->id]);

        $this->assertCount(2, $user->roles);
    }

    public function test_user_has_many_outlets(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $outlet1 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        $user->outlets()->attach([
            $outlet1->id => ['is_default' => true],
            $outlet2->id => ['is_default' => false],
        ]);

        $this->assertCount(2, $user->outlets);
    }

    public function test_user_has_one_user_pin(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $userPin = UserPin::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(UserPin::class, $user->userPin);
        $this->assertEquals($userPin->id, $user->userPin->id);
    }

    // ==================== DEFAULT OUTLET TESTS ====================

    public function test_default_outlet_returns_outlet_with_is_default_true(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $outlet1 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        $user->outlets()->attach([
            $outlet1->id => ['is_default' => false],
            $outlet2->id => ['is_default' => true],
        ]);

        $defaultOutlet = $user->defaultOutlet();
        $this->assertNotNull($defaultOutlet);
        $this->assertEquals($outlet2->id, $defaultOutlet->id);
    }

    public function test_default_outlet_returns_null_when_no_default(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        $user->outlets()->attach([
            $outlet->id => ['is_default' => false],
        ]);

        $this->assertNull($user->defaultOutlet());
    }

    public function test_default_outlet_returns_null_when_no_outlets(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNull($user->defaultOutlet());
    }

    // ==================== ROLE CHECK TESTS ====================

    public function test_has_role_returns_true_when_user_has_role(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'slug' => 'manager',
        ]);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasRole('manager'));
    }

    public function test_has_role_returns_false_when_user_does_not_have_role(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($user->hasRole('manager'));
    }

    public function test_has_any_role_returns_true_when_user_has_one_of_roles(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'slug' => 'cashier',
        ]);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasAnyRole(['manager', 'cashier', 'waiter']));
    }

    public function test_has_any_role_returns_false_when_user_has_none_of_roles(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'slug' => 'waiter',
        ]);
        $user->roles()->attach($role->id);

        $this->assertFalse($user->hasAnyRole(['manager', 'cashier', 'admin']));
    }

    // ==================== PERMISSION CHECK TESTS ====================

    public function test_has_permission_returns_true_when_role_has_permission(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $permission = Permission::factory()->create(['slug' => 'view-products']);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->hasPermission('view-products'));
    }

    public function test_has_permission_returns_false_when_role_does_not_have_permission(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $this->assertFalse($user->hasPermission('view-products'));
    }

    public function test_has_permission_returns_false_when_user_has_no_roles(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($user->hasPermission('view-products'));
    }

    // ==================== SPECIAL ROLE CHECKS ====================

    public function test_is_super_admin_returns_true_for_super_admin(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->superAdmin()->create();
        $user->roles()->attach($role->id);

        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_is_super_admin_returns_false_for_non_super_admin(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->manager()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $this->assertFalse($user->isSuperAdmin());
    }

    public function test_is_tenant_owner_returns_true_for_tenant_owner(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->tenantOwner()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->isTenantOwner());
    }

    public function test_is_tenant_owner_returns_false_for_non_tenant_owner(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->cashier()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $this->assertFalse($user->isTenantOwner());
    }

    // ==================== OUTLET ACCESS TESTS ====================

    public function test_can_access_outlet_returns_true_for_super_admin(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->superAdmin()->create();
        $user->roles()->attach($role->id);

        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($user->canAccessOutlet($outlet->id));
    }

    public function test_can_access_outlet_returns_true_for_tenant_owner(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->tenantOwner()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($user->canAccessOutlet($outlet->id));
    }

    public function test_can_access_outlet_returns_true_when_user_assigned_to_outlet(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->cashier()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->outlets()->attach($outlet->id);

        $this->assertTrue($user->canAccessOutlet($outlet->id));
    }

    public function test_can_access_outlet_returns_false_when_user_not_assigned(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->cashier()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($user->canAccessOutlet($outlet->id));
    }

    // ==================== INITIALS ATTRIBUTE TESTS ====================

    public function test_initials_returns_first_letters_of_name(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
        ]);

        $this->assertEquals('JD', $user->initials);
    }

    public function test_initials_returns_single_letter_for_single_word_name(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John',
        ]);

        $this->assertEquals('J', $user->initials);
    }

    public function test_initials_returns_max_two_letters(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John David Doe',
        ]);

        $this->assertEquals('JD', $user->initials);
    }

    // ==================== PIN TESTS ====================

    public function test_has_pin_returns_true_when_active_pin_exists(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        UserPin::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->assertTrue($user->hasPin());
    }

    public function test_has_pin_returns_false_when_pin_is_inactive(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        UserPin::factory()->inactive()->create(['user_id' => $user->id]);

        $this->assertFalse($user->hasPin());
    }

    public function test_has_pin_returns_false_when_no_pin(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($user->hasPin());
    }

    public function test_verify_pin_returns_true_for_correct_pin(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        UserPin::factory()->withPin('5678')->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->assertTrue($user->verifyPin('5678'));
    }

    public function test_verify_pin_returns_false_for_incorrect_pin(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        UserPin::factory()->withPin('5678')->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->assertFalse($user->verifyPin('1234'));
    }

    public function test_verify_pin_returns_false_when_pin_inactive(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        UserPin::factory()->withPin('5678')->inactive()->create([
            'user_id' => $user->id,
        ]);

        $this->assertFalse($user->verifyPin('5678'));
    }

    public function test_verify_pin_returns_false_when_no_pin(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($user->verifyPin('1234'));
    }

    // ==================== AUTHORIZATION ROLE CHECKS ====================

    public function test_can_authorize_returns_true_for_manager(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->manager()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->canAuthorize());
    }

    public function test_can_authorize_returns_true_for_supervisor(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->supervisor()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->canAuthorize());
    }

    public function test_can_authorize_returns_true_for_tenant_owner(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->tenantOwner()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->canAuthorize());
    }

    public function test_can_authorize_returns_false_for_cashier(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->cashier()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $this->assertFalse($user->canAuthorize());
    }

    public function test_is_manager_returns_true_for_manager_roles(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->manager()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->isManager());
    }

    public function test_is_manager_returns_false_for_non_manager(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->cashier()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $this->assertFalse($user->isManager());
    }

    public function test_is_supervisor_returns_true_for_supervisor_roles(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $role = Role::factory()->supervisor()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($role->id);

        $this->assertTrue($user->isSupervisor());
    }

    // ==================== CASTING TESTS ====================

    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(Carbon::class, $user->email_verified_at);
    }

    public function test_last_login_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->withLastLogin()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(Carbon::class, $user->last_login_at);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => 1,
        ]);

        $this->assertIsBool($user->is_active);
        $this->assertTrue($user->is_active);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_unverified_state(): void
    {
        $user = User::factory()->unverified()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNull($user->email_verified_at);
    }

    public function test_factory_inactive_state(): void
    {
        $user = User::factory()->inactive()->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($user->is_active);
    }

    public function test_factory_with_last_login_state(): void
    {
        $user = User::factory()->withLastLogin()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNotNull($user->last_login_at);
    }

    public function test_factory_for_tenant_state(): void
    {
        $user = User::factory()->forTenant($this->tenant)->create();

        $this->assertEquals($this->tenant->id, $user->tenant_id);
    }

    public function test_factory_with_locale_state(): void
    {
        $user = User::factory()->withLocale('en')->create(['tenant_id' => $this->tenant->id]);

        $this->assertEquals('en', $user->locale);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_user_uses_uuid(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertNotNull($user->id);
        $this->assertIsString($user->id);
        // UUID format check
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $user->id
        );
    }

    // ==================== TENANT ISOLATION TESTS ====================

    public function test_users_are_isolated_by_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        User::factory()->count(3)->create(['tenant_id' => $tenant1->id]);
        User::factory()->count(2)->create(['tenant_id' => $tenant2->id]);

        $tenant1Users = User::where('tenant_id', $tenant1->id)->get();
        $tenant2Users = User::where('tenant_id', $tenant2->id)->get();

        $this->assertCount(3, $tenant1Users);
        $this->assertCount(2, $tenant2Users);
    }
}
