<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletSwitchTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    private User $cashier;

    private Outlet $outlet1;

    private Outlet $outlet2;

    private Outlet $outlet3;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant
        $this->tenant = Tenant::factory()->create();

        // Create outlets
        $this->outlet1 = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Outlet 1',
        ]);

        $this->outlet2 = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Outlet 2',
        ]);

        $this->outlet3 = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Outlet 3',
        ]);

        // Create owner role
        $ownerRole = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'slug' => 'tenant-owner',
            'name' => 'Tenant Owner',
        ]);

        // Create cashier role
        $cashierRole = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'slug' => 'cashier',
            'name' => 'Cashier',
        ]);

        // Create owner user with tenant-owner role
        $this->owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        $this->owner->roles()->attach($ownerRole);
        $this->owner->outlets()->attach($this->outlet1->id, ['is_default' => true]);

        // Create cashier user with cashier role (only assigned to outlet1 and outlet2)
        $this->cashier = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        $this->cashier->roles()->attach($cashierRole);
        $this->cashier->outlets()->attach($this->outlet1->id, ['is_default' => true]);
        $this->cashier->outlets()->attach($this->outlet2->id, ['is_default' => false]);
    }

    public function test_tenant_owner_can_switch_to_any_tenant_outlet(): void
    {
        // Owner is assigned to outlet1, should be able to switch to outlet2 (tenant's outlet)
        $response = $this->actingAs($this->owner)
            ->post(route('admin.outlets.switch', $this->outlet2));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify outlet2 is now default
        $this->owner->refresh();
        $defaultOutlet = $this->owner->outlets()->wherePivot('is_default', true)->first();
        $this->assertEquals($this->outlet2->id, $defaultOutlet->id);

        // Verify outlet2 was auto-assigned to user
        $this->assertTrue($this->owner->outlets()->where('outlets.id', $this->outlet2->id)->exists());
    }

    public function test_tenant_owner_can_switch_to_outlet_not_previously_assigned(): void
    {
        // Owner only has outlet1, should be able to switch to outlet3 (auto-assign)
        $this->assertFalse($this->owner->outlets()->where('outlets.id', $this->outlet3->id)->exists());

        $response = $this->actingAs($this->owner)
            ->post(route('admin.outlets.switch', $this->outlet3));

        $response->assertRedirect();

        // Verify outlet3 is now assigned and default
        $this->owner->refresh();
        $this->assertTrue($this->owner->outlets()->where('outlets.id', $this->outlet3->id)->exists());

        $defaultOutlet = $this->owner->outlets()->wherePivot('is_default', true)->first();
        $this->assertEquals($this->outlet3->id, $defaultOutlet->id);
    }

    public function test_cashier_can_switch_to_assigned_outlet(): void
    {
        // Cashier is assigned to outlet1 (default) and outlet2
        $response = $this->actingAs($this->cashier)
            ->post(route('admin.outlets.switch', $this->outlet2));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify outlet2 is now default
        $this->cashier->refresh();
        $defaultOutlet = $this->cashier->outlets()->wherePivot('is_default', true)->first();
        $this->assertEquals($this->outlet2->id, $defaultOutlet->id);
    }

    public function test_cashier_cannot_switch_to_unassigned_outlet(): void
    {
        // Cashier is NOT assigned to outlet3
        $this->assertFalse($this->cashier->outlets()->where('outlets.id', $this->outlet3->id)->exists());

        $response = $this->actingAs($this->cashier)
            ->post(route('admin.outlets.switch', $this->outlet3));

        $response->assertStatus(403);
    }

    public function test_user_cannot_switch_to_other_tenant_outlet(): void
    {
        // Create another tenant with outlet
        $otherTenant = Tenant::factory()->create();
        $otherOutlet = Outlet::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Tenant Outlet',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('admin.outlets.switch', $otherOutlet));

        $response->assertStatus(403);
    }

    public function test_switching_outlet_updates_is_default_correctly(): void
    {
        // Add outlet2 and outlet3 to owner
        $this->owner->outlets()->attach($this->outlet2->id, ['is_default' => false]);
        $this->owner->outlets()->attach($this->outlet3->id, ['is_default' => false]);

        // Verify outlet1 is currently default
        $this->assertTrue($this->owner->outlets()->wherePivot('is_default', true)->where('outlets.id', $this->outlet1->id)->exists());

        // Switch to outlet2
        $this->actingAs($this->owner)
            ->post(route('admin.outlets.switch', $this->outlet2));

        $this->owner->refresh();

        // Verify only outlet2 is default now
        $defaultOutlets = $this->owner->outlets()->wherePivot('is_default', true)->get();
        $this->assertCount(1, $defaultOutlets);
        $this->assertEquals($this->outlet2->id, $defaultOutlets->first()->id);

        // Verify outlet1 and outlet3 are not default
        $this->assertFalse($this->owner->outlets()->wherePivot('is_default', true)->where('outlets.id', $this->outlet1->id)->exists());
        $this->assertFalse($this->owner->outlets()->wherePivot('is_default', true)->where('outlets.id', $this->outlet3->id)->exists());
    }

    public function test_guest_cannot_switch_outlet(): void
    {
        $response = $this->post(route('admin.outlets.switch', $this->outlet1));

        $response->assertRedirect(route('login'));
    }
}
