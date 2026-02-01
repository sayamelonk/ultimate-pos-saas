<?php

namespace Tests\Feature\Admin;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-OUTLET-001: Akses halaman outlet list sebagai Super Admin
     *
     * Preconditions: Login sebagai Super Admin
     * Expected: Semua outlet dari semua tenant ditampilkan
     */
    public function test_super_admin_can_access_outlet_list(): void
    {
        // Arrange: Create super admin
        $superAdminRole = Role::factory()->superAdmin()->create();
        $superAdmin = User::factory()->create();
        $superAdmin->roles()->attach($superAdminRole->id);

        // Act: Access outlet list
        $response = $this->actingAs($superAdmin)
            ->get(route('admin.outlets.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.outlets.index');
        $response->assertViewHas('outlets');
    }

    /**
     * TC-OUTLET-002: Akses halaman outlet list sebagai Tenant Owner
     *
     * Preconditions: Login sebagai Tenant Owner
     * Expected: Hanya outlet milik tenant sendiri yang ditampilkan
     */
    public function test_tenant_owner_can_only_see_their_tenant_outlets(): void
    {
        // Arrange: Create two tenants with outlets
        $ownerRole = Role::factory()->tenantOwner()->create();

        $tenant1 = Tenant::factory()->create();
        $owner1 = User::factory()->forTenant($tenant1)->create();
        $owner1->roles()->attach($ownerRole->id);

        $outlet1 = Outlet::factory()->forTenant($tenant1)->create(['name' => 'Tenant 1 Outlet']);

        $tenant2 = Tenant::factory()->create();
        $outlet2 = Outlet::factory()->forTenant($tenant2)->create(['name' => 'Tenant 2 Outlet']);

        // Act: Access outlet list as tenant 1 owner
        $response = $this->actingAs($owner1)
            ->get(route('admin.outlets.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('outlets');

        // Verify only tenant 1 outlets are visible
        $outlets = $response->viewData('outlets');
        $this->assertTrue($outlets->every(fn ($outlet) => $outlet->tenant_id === $tenant1->id));
        $this->assertTrue($outlets->contains('name', 'Tenant 1 Outlet'));
        $this->assertFalse($outlets->contains('name', 'Tenant 2 Outlet'));
    }

    /**
     * TC-OUTLET-003: Search outlet
     *
     * Preconditions: Login, beberapa outlet sudah ada
     * Expected: Outlet dengan nama/code/address mengandung "main" ditampilkan
     */
    public function test_can_search_outlets_by_name_code_or_address(): void
    {
        // Arrange: Create outlets
        $ownerRole = Role::factory()->tenantOwner()->create();
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->forTenant($tenant)->create();
        $admin->roles()->attach($ownerRole->id);

        Outlet::factory()->forTenant($tenant)->create(['name' => 'Main Branch']);
        Outlet::factory()->forTenant($tenant)->create(['name' => 'Secondary Branch']);
        Outlet::factory()->forTenant($tenant)->create(['code' => 'MAIN']);

        // Act: Search for "main"
        $response = $this->actingAs($admin)
            ->get(route('admin.outlets.index', ['search' => 'main']));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('outlets');
    }

    /**
     * TC-OUTLET-004: Filter outlet by status
     *
     * Preconditions: Login
     * Expected: Hanya outlet aktif/nonaktif yang ditampilkan sesuai filter
     */
    public function test_can_filter_outlets_by_status(): void
    {
        // Arrange: Create outlets with different status
        $ownerRole = Role::factory()->tenantOwner()->create();
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->forTenant($tenant)->create();
        $admin->roles()->attach($ownerRole->id);

        Outlet::factory()->forTenant($tenant)->create(['is_active' => true]);
        Outlet::factory()->forTenant($tenant)->create(['is_active' => false]);
        Outlet::factory()->forTenant($tenant)->create(['is_active' => true]);

        // Act: Filter by active status
        $response = $this->actingAs($admin)
            ->get(route('admin.outlets.index', ['status' => 'active']));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('outlets');

        // Act: Filter by inactive status
        $response = $this->actingAs($admin)
            ->get(route('admin.outlets.index', ['status' => 'inactive']));

        // Assert
        $response->assertStatus(200);
    }

    /**
     * TC-OUTLET-005: Buat outlet baru
     *
     * Preconditions: Login sebagai Tenant Owner
     * Expected: Outlet ter-create dengan tenant_id dari user login, Redirect dengan success message
     */
    public function test_tenant_owner_can_create_outlet(): void
    {
        // Arrange: Create tenant owner
        $ownerRole = Role::factory()->tenantOwner()->create();
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->forTenant($tenant)->create();
        $owner->roles()->attach($ownerRole->id);

        // Act: Create new outlet
        $response = $this->actingAs($owner)
            ->post(route('admin.outlets.store'), [
                'name' => 'Branch 1',
                'code' => 'BR1',
                'address' => 'Jl. Test No. 1',
                'phone' => '08123456789',
                'email' => 'branch1@test.com',
                'is_active' => true,
            ]);

        // Assert: Outlet created
        $outlet = Outlet::where('code', 'BR1')->first();
        $this->assertDatabaseHas('outlets', [
            'name' => 'Branch 1',
            'code' => 'BR1',
            'tenant_id' => $tenant->id,
            'address' => 'Jl. Test No. 1',
            'phone' => '08123456789',
            'email' => 'branch1@test.com',
            'is_active' => true,
        ]);

        // Assert: User is automatically assigned to the outlet
        $this->assertDatabaseHas('user_outlets', [
            'user_id' => $owner->id,
            'outlet_id' => $outlet->id,
            'is_default' => false,
        ]);

        $response->assertRedirect(route('admin.outlets.index'));
        $response->assertSessionHas('success');
    }

    /**
     * TC-OUTLET-005b: Super Admin creates outlet for specific tenant
     *
     * Expected: Outlet ter-create dengan tenant_id yang dipilih
     */
    public function test_super_admin_can_create_outlet_for_specific_tenant(): void
    {
        // Arrange: Create super admin and tenant
        $superAdminRole = Role::factory()->superAdmin()->create();
        $superAdmin = User::factory()->create();
        $superAdmin->roles()->attach($superAdminRole->id);

        $tenant = Tenant::factory()->create();

        // Act: Create outlet for specific tenant
        $response = $this->actingAs($superAdmin)
            ->post(route('admin.outlets.store'), [
                'name' => 'New Outlet',
                'code' => 'NEW',
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]);

        // Assert: Outlet created for the specified tenant
        $this->assertDatabaseHas('outlets', [
            'name' => 'New Outlet',
            'code' => 'NEW',
            'tenant_id' => $tenant->id,
        ]);

        // Assert: Super Admin is NOT automatically assigned to the outlet
        $outlet = Outlet::where('code', 'NEW')->first();
        $this->assertDatabaseMissing('user_outlets', [
            'user_id' => $superAdmin->id,
            'outlet_id' => $outlet->id,
        ]);

        $response->assertRedirect(route('admin.outlets.index'));
        $response->assertSessionHas('success');
    }

    /**
     * TC-OUTLET-006: Validasi form outlet
     *
     * Preconditions: Login
     * Expected: Error validation: name required, code required
     */
    public function test_cannot_create_outlet_without_required_fields(): void
    {
        // Arrange: Create admin
        $ownerRole = Role::factory()->tenantOwner()->create();
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->forTenant($tenant)->create();
        $admin->roles()->attach($ownerRole->id);

        // Act: Try to create outlet without required fields
        $response = $this->actingAs($admin)
            ->post(route('admin.outlets.store'), []);

        // Assert: Validation errors
        $response->assertSessionHasErrors(['name', 'code']);
    }

    /**
     * TC-OUTLET-006b: Validasi code unik per tenant
     *
     * Expected: Code harus unik dalam satu tenant
     */
    public function test_outlet_code_must_be_unique_per_tenant(): void
    {
        // Arrange: Create admin and existing outlet
        $ownerRole = Role::factory()->tenantOwner()->create();
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->forTenant($tenant)->create();
        $admin->roles()->attach($ownerRole->id);

        Outlet::factory()->forTenant($tenant)->create(['code' => 'BR1']);

        // Act: Try to create outlet with same code
        $response = $this->actingAs($admin)
            ->post(route('admin.outlets.store'), [
                'name' => 'Another Branch',
                'code' => 'BR1',
                'is_active' => true,
            ]);

        // Assert: Validation error
        $response->assertSessionHasErrors('code');
    }

    /**
     * TC-OUTLET-006c: Code unik cross-tenant (boleh sama)
     *
     * Expected: Code bisa sama antara tenant berbeda
     */
    public function test_outlet_code_can_be_same_across_different_tenants(): void
    {
        // Arrange: Create two tenants
        $ownerRole = Role::factory()->tenantOwner()->create();

        $tenant1 = Tenant::factory()->create();
        $owner1 = User::factory()->forTenant($tenant1)->create();
        $owner1->roles()->attach($ownerRole->id);

        $tenant2 = Tenant::factory()->create();
        Outlet::factory()->forTenant($tenant2)->create(['code' => 'MAIN']);

        // Act: Create outlet with same code for different tenant
        $response = $this->actingAs($owner1)
            ->post(route('admin.outlets.store'), [
                'name' => 'Main Branch',
                'code' => 'MAIN',
                'is_active' => true,
            ]);

        // Assert: Outlet created successfully
        $this->assertDatabaseHas('outlets', [
            'tenant_id' => $tenant1->id,
            'code' => 'MAIN',
        ]);

        $response->assertRedirect(route('admin.outlets.index'));
        $response->assertSessionHasNoErrors();
    }

    /**
     * TC-OUTLET-007: Edit outlet sendiri
     *
     * Preconditions: Login sebagai Tenant Owner
     * Expected: Data outlet terupdate, redirect dengan success message
     */
    public function test_tenant_owner_can_edit_their_tenant_outlet(): void
    {
        // Arrange: Create tenant owner and outlet
        $ownerRole = Role::factory()->tenantOwner()->create();
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->forTenant($tenant)->create();
        $owner->roles()->attach($ownerRole->id);

        $outlet = Outlet::factory()->forTenant($tenant)->create(['name' => 'Old Name']);

        // Act: Update outlet
        $response = $this->actingAs($owner)
            ->put(route('admin.outlets.update', $outlet), [
                'name' => 'Branch 1 Updated',
                'code' => $outlet->code,
                'address' => 'Updated Address',
                'phone' => '08987654321',
                'email' => 'updated@test.com',
                'is_active' => true,
            ]);

        // Assert: Outlet updated
        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'name' => 'Branch 1 Updated',
            'address' => 'Updated Address',
            'phone' => '08987654321',
            'email' => 'updated@test.com',
        ]);

        $response->assertRedirect(route('admin.outlets.index'));
        $response->assertSessionHas('success');
    }

    /**
     * TC-OUTLET-008: Edit outlet tenant lain (unauthorized)
     *
     * Preconditions: Login sebagai Tenant Owner, mencoba edit outlet dari tenant lain
     * Expected: Error 403
     */
    public function test_tenant_owner_cannot_edit_outlet_from_other_tenant(): void
    {
        // Arrange: Create two tenants
        $ownerRole = Role::factory()->tenantOwner()->create();

        $tenant1 = Tenant::factory()->create();
        $owner1 = User::factory()->forTenant($tenant1)->create();
        $owner1->roles()->attach($ownerRole->id);

        $tenant2 = Tenant::factory()->create();
        $outlet2 = Outlet::factory()->forTenant($tenant2)->create();

        // Act: Try to edit outlet from other tenant
        $response = $this->actingAs($owner1)
            ->put(route('admin.outlets.update', $outlet2), [
                'name' => 'Updated Name',
                'code' => $outlet2->code,
            ]);

        // Assert: 403 Forbidden
        $response->assertStatus(403);
    }

    /**
     * TC-OUTLET-009: Hapus outlet tanpa assigned users
     *
     * Preconditions: Outlet tanpa users terkait
     * Expected: Outlet terhapus, success message
     */
    public function test_can_delete_outlet_without_assigned_users(): void
    {
        // Arrange: Create admin and outlet without users
        $ownerRole = Role::factory()->tenantOwner()->create();
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->forTenant($tenant)->create();
        $admin->roles()->attach($ownerRole->id);

        $outlet = Outlet::factory()->forTenant($tenant)->create();

        // Act: Delete outlet
        $response = $this->actingAs($admin)
            ->delete(route('admin.outlets.destroy', $outlet));

        // Assert: Outlet deleted
        $this->assertDatabaseMissing('outlets', [
            'id' => $outlet->id,
        ]);

        $response->assertRedirect(route('admin.outlets.index'));
        $response->assertSessionHas('success');
    }

    /**
     * TC-OUTLET-010: Hapus outlet dengan assigned users
     *
     * Preconditions: Outlet memiliki users terkait
     * Expected: Error: "Cannot delete outlet with assigned users."
     */
    public function test_cannot_delete_outlet_with_assigned_users(): void
    {
        // Arrange: Create admin and outlet with users
        $ownerRole = Role::factory()->tenantOwner()->create();
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->forTenant($tenant)->create();
        $admin->roles()->attach($ownerRole->id);

        $outlet = Outlet::factory()->forTenant($tenant)->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->outlets()->attach($outlet->id, ['is_default' => true]);

        // Act: Try to delete outlet
        $response = $this->actingAs($admin)
            ->delete(route('admin.outlets.destroy', $outlet));

        // Assert: Outlet still exists and error message shown
        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * TC-OUTLET-011: Super Admin dapat mengakses create outlet
     *
     * Expected: Form create dengan daftar tenant ditampilkan
     */
    public function test_super_admin_can_access_create_outlet_with_tenant_list(): void
    {
        // Arrange: Create super admin and active tenants
        $superAdminRole = Role::factory()->superAdmin()->create();
        $superAdmin = User::factory()->create();
        $superAdmin->roles()->attach($superAdminRole->id);

        Tenant::factory()->count(3)->create(['is_active' => true]);
        Tenant::factory()->create(['is_active' => false]);

        // Act: Access create page
        $response = $this->actingAs($superAdmin)
            ->get(route('admin.outlets.create'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.outlets.create');
        $response->assertViewHas('tenants');

        // Verify only active tenants are shown
        $tenants = $response->viewData('tenants');
        $this->assertTrue($tenants->every(fn ($tenant) => $tenant->is_active));
    }

    /**
     * TC-OUTLET-012: Tenant Owner tidak melihat tenant selection saat create
     *
     * Expected: Form create tanpa daftar tenant
     */
    public function test_tenant_owner_create_outlet_without_tenant_selection(): void
    {
        // Arrange: Create tenant owner
        $ownerRole = Role::factory()->tenantOwner()->create();
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->forTenant($tenant)->create();
        $owner->roles()->attach($ownerRole->id);

        // Act: Access create page
        $response = $this->actingAs($owner)
            ->get(route('admin.outlets.create'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.outlets.create');

        // Verify tenants list is empty
        $tenants = $response->viewData('tenants');
        $this->assertIsArray($tenants);
        $this->assertEmpty($tenants);
    }

    /**
     * TC-OUTLET-013: Deactivate outlet
     *
     * Expected: Outlet menjadi tidak aktif
     */
    public function test_can_deactivate_outlet(): void
    {
        // Arrange: Create admin and active outlet
        $ownerRole = Role::factory()->tenantOwner()->create();
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->forTenant($tenant)->create();
        $admin->roles()->attach($ownerRole->id);

        $outlet = Outlet::factory()->forTenant($tenant)->create(['is_active' => true]);

        // Act: Deactivate outlet
        $response = $this->actingAs($admin)
            ->put(route('admin.outlets.update', $outlet), [
                'name' => $outlet->name,
                'code' => $outlet->code,
                'is_active' => false,
            ]);

        // Assert: Outlet is now inactive
        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'is_active' => false,
        ]);

        $response->assertSessionHas('success');
    }
}
