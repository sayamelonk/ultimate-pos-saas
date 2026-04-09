<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::factory()->create([
            'slug' => 'super-admin',
            'name' => 'Super Admin',
            'is_system' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'tenant_id' => null,
            'email_verified_at' => now(),
        ]);

        $this->superAdmin->roles()->attach($this->superAdminRole);
    }

    // ==================== INDEX TESTS ====================

    public function test_super_admin_can_access_subscriptions_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscriptions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.subscriptions.index');
    }

    public function test_subscriptions_index_displays_all_subscriptions(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant Alpha']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant Beta']);

        Subscription::factory()->forTenant($tenant1)->withPlan($plan)->create();
        Subscription::factory()->forTenant($tenant2)->withPlan($plan)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscriptions.index'));

        $response->assertStatus(200);
        $response->assertSee('Tenant Alpha');
        $response->assertSee('Tenant Beta');
    }

    public function test_subscriptions_can_be_filtered_by_status(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant1 = Tenant::factory()->create(['name' => 'TenantActiveTest123']);
        $tenant2 = Tenant::factory()->create(['name' => 'TenantCancelledTest456']);

        Subscription::factory()->active()->forTenant($tenant1)->withPlan($plan)->create();
        Subscription::factory()->cancelled()->forTenant($tenant2)->withPlan($plan)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscriptions.index', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertViewHas('subscriptions', function ($subscriptions) {
            return $subscriptions->count() === 1
                && $subscriptions->first()->tenant->name === 'TenantActiveTest123';
        });
    }

    public function test_subscriptions_can_be_filtered_by_plan(): void
    {
        $starterPlan = SubscriptionPlan::factory()->starter()->create();
        $growthPlan = SubscriptionPlan::factory()->growth()->create();
        $tenant1 = Tenant::factory()->create(['name' => 'TenantStarterPlanTest']);
        $tenant2 = Tenant::factory()->create(['name' => 'TenantGrowthPlanTest']);

        Subscription::factory()->forTenant($tenant1)->withPlan($starterPlan)->create();
        Subscription::factory()->forTenant($tenant2)->withPlan($growthPlan)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscriptions.index', ['plan' => $starterPlan->id]));

        $response->assertStatus(200);
        $response->assertViewHas('subscriptions', function ($subscriptions) use ($starterPlan) {
            return $subscriptions->count() === 1
                && $subscriptions->first()->subscription_plan_id === $starterPlan->id;
        });
    }

    public function test_subscriptions_can_be_searched_by_tenant_name(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant1 = Tenant::factory()->create(['name' => 'WarungBudiSearchTest']);
        $tenant2 = Tenant::factory()->create(['name' => 'CafeJakartaSearchTest']);

        Subscription::factory()->forTenant($tenant1)->withPlan($plan)->create();
        Subscription::factory()->forTenant($tenant2)->withPlan($plan)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscriptions.index', ['search' => 'Budi']));

        $response->assertStatus(200);
        $response->assertViewHas('subscriptions', function ($subscriptions) {
            return $subscriptions->count() === 1
                && $subscriptions->first()->tenant->name === 'WarungBudiSearchTest';
        });
    }

    public function test_guest_cannot_access_subscriptions_index(): void
    {
        $response = $this->get(route('admin.subscriptions.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_super_admin_cannot_access_subscriptions_index(): void
    {
        $tenant = Tenant::factory()->create();
        $regularUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($regularUser)->get(route('admin.subscriptions.index'));

        $response->assertStatus(403);
    }

    // ==================== SHOW TESTS ====================

    public function test_super_admin_can_view_subscription_details(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->forTenant($tenant)->withPlan($plan)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscriptions.show', $subscription));

        $response->assertStatus(200);
        $response->assertViewIs('admin.subscriptions.show');
        $response->assertViewHas('subscription');
    }

    public function test_show_page_displays_subscription_details(): void
    {
        $plan = SubscriptionPlan::factory()->create(['name' => 'Professional Plan']);
        $tenant = Tenant::factory()->create(['name' => 'Test Business']);
        $subscription = Subscription::factory()
            ->active()
            ->forTenant($tenant)
            ->withPlan($plan)
            ->create(['billing_cycle' => 'yearly']);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscriptions.show', $subscription));

        $response->assertStatus(200);
        $response->assertSee('Test Business');
        $response->assertSee('Professional Plan');
    }

    public function test_show_page_loads_related_invoices(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->forTenant($tenant)->withPlan($plan)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscriptions.show', $subscription));

        $response->assertStatus(200);
        $response->assertViewHas('subscription', function ($sub) {
            return $sub->relationLoaded('invoices');
        });
    }

    // ==================== UPDATE STATUS TESTS ====================

    public function test_super_admin_can_update_subscription_status_to_active(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()
            ->forTenant($tenant)
            ->withPlan($plan)
            ->create(['status' => 'frozen']);

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.subscriptions.update-status', $subscription),
            ['status' => 'active']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_update_subscription_status_to_cancelled(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()
            ->active()
            ->forTenant($tenant)
            ->withPlan($plan)
            ->create();

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.subscriptions.update-status', $subscription),
            ['status' => 'cancelled']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_super_admin_can_update_subscription_status_to_frozen(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()
            ->active()
            ->forTenant($tenant)
            ->withPlan($plan)
            ->create();

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.subscriptions.update-status', $subscription),
            ['status' => 'frozen']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'frozen',
        ]);
    }

    public function test_update_status_validates_allowed_statuses(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()
            ->forTenant($tenant)
            ->withPlan($plan)
            ->create();

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.subscriptions.update-status', $subscription),
            ['status' => 'invalid_status']
        );

        $response->assertSessionHasErrors('status');
    }

    public function test_update_status_requires_status_field(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()
            ->forTenant($tenant)
            ->withPlan($plan)
            ->create();

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.subscriptions.update-status', $subscription),
            []
        );

        $response->assertSessionHasErrors('status');
    }

    // ==================== PAGINATION TESTS ====================

    public function test_subscriptions_are_paginated(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        for ($i = 0; $i < 25; $i++) {
            $tenant = Tenant::factory()->create();
            Subscription::factory()->forTenant($tenant)->withPlan($plan)->create();
        }

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscriptions.index'));

        $response->assertStatus(200);
        $response->assertViewHas('subscriptions', function ($subscriptions) {
            return $subscriptions->count() === 20 && $subscriptions->total() === 25;
        });
    }

    // ==================== VIEW DATA TESTS ====================

    public function test_index_provides_plans_for_filter_dropdown(): void
    {
        SubscriptionPlan::factory()->starter()->create();
        SubscriptionPlan::factory()->growth()->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscriptions.index'));

        $response->assertStatus(200);
        $response->assertViewHas('plans', function ($plans) {
            return $plans->count() === 2;
        });
    }

    public function test_index_provides_tenants_for_filter_dropdown(): void
    {
        Tenant::factory()->count(5)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscriptions.index'));

        $response->assertStatus(200);
        $response->assertViewHas('tenants', function ($tenants) {
            return $tenants->count() === 5;
        });
    }
}
