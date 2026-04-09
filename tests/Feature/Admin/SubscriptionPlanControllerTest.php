<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanControllerTest extends TestCase
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

    public function test_super_admin_can_access_subscription_plans_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscription-plans.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.subscription-plans.index');
    }

    public function test_subscription_plans_index_displays_all_plans(): void
    {
        $plan1 = SubscriptionPlan::factory()->starter()->create();
        $plan2 = SubscriptionPlan::factory()->growth()->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscription-plans.index'));

        $response->assertStatus(200);
        $response->assertSee($plan1->name);
        $response->assertSee($plan2->name);
    }

    public function test_subscription_plans_are_ordered_by_sort_order(): void
    {
        $plan3 = SubscriptionPlan::factory()->create(['sort_order' => 3, 'name' => 'Third Plan']);
        $plan1 = SubscriptionPlan::factory()->create(['sort_order' => 1, 'name' => 'First Plan']);
        $plan2 = SubscriptionPlan::factory()->create(['sort_order' => 2, 'name' => 'Second Plan']);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscription-plans.index'));

        $response->assertStatus(200);
        $response->assertViewHas('plans', function ($plans) {
            return $plans->first()->sort_order === 1 && $plans->last()->sort_order === 3;
        });
    }

    public function test_non_super_admin_cannot_access_subscription_plans_index(): void
    {
        $tenant = Tenant::factory()->create();
        $regularUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($regularUser)->get(route('admin.subscription-plans.index'));

        // Should be forbidden or redirect based on middleware
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_subscription_plans_index(): void
    {
        $response = $this->get(route('admin.subscription-plans.index'));

        $response->assertRedirect(route('login'));
    }

    // ==================== CREATE TESTS ====================

    public function test_super_admin_can_access_create_subscription_plan_form(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscription-plans.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.subscription-plans.create');
    }

    // ==================== STORE TESTS ====================

    public function test_super_admin_can_create_subscription_plan(): void
    {
        $planData = [
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'description' => 'A test subscription plan',
            'price_monthly' => 150000,
            'price_yearly' => 1500000,
            'max_outlets' => 3,
            'max_users' => 10,
            'max_products' => 500,
            'is_active' => true,
            'sort_order' => 5,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('admin.subscription-plans.store'), $planData);

        $response->assertRedirect(route('admin.subscription-plans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscription_plans', [
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price_monthly' => 150000,
        ]);
    }

    public function test_create_subscription_plan_requires_name(): void
    {
        $planData = [
            'slug' => 'test-plan',
            'price_monthly' => 150000,
            'price_yearly' => 1500000,
            'max_outlets' => 3,
            'max_users' => 10,
            'max_products' => 500,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('admin.subscription-plans.store'), $planData);

        $response->assertSessionHasErrors('name');
    }

    public function test_create_subscription_plan_requires_unique_slug(): void
    {
        SubscriptionPlan::factory()->create(['slug' => 'existing-slug']);

        $planData = [
            'name' => 'Test Plan',
            'slug' => 'existing-slug',
            'price_monthly' => 150000,
            'price_yearly' => 1500000,
            'max_outlets' => 3,
            'max_users' => 10,
            'max_products' => 500,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('admin.subscription-plans.store'), $planData);

        $response->assertSessionHasErrors('slug');
    }

    public function test_create_subscription_plan_validates_price_is_positive(): void
    {
        $planData = [
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price_monthly' => -1000,
            'price_yearly' => 1500000,
            'max_outlets' => 3,
            'max_users' => 10,
            'max_products' => 500,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('admin.subscription-plans.store'), $planData);

        $response->assertSessionHasErrors('price_monthly');
    }

    // ==================== SHOW TESTS ====================

    public function test_super_admin_can_view_subscription_plan_details(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscription-plans.show', $plan));

        $response->assertStatus(200);
        $response->assertViewIs('admin.subscription-plans.show');
        $response->assertViewHas('subscriptionPlan');
    }

    public function test_show_page_displays_plan_details(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Premium Plan',
            'price_monthly' => 299000,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscription-plans.show', $plan));

        $response->assertStatus(200);
        $response->assertSee('Premium Plan');
        $response->assertSee('299.000');
    }

    public function test_show_page_includes_subscription_statistics(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();

        Subscription::factory()
            ->active()
            ->forTenant($tenant)
            ->withPlan($plan)
            ->create(['billing_cycle' => 'monthly']);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscription-plans.show', $plan));

        $response->assertStatus(200);
        $response->assertViewHas('totalSubscriptionsCount', 1);
        $response->assertViewHas('activeSubscriptionsCount', 1);
    }

    public function test_show_page_calculates_revenue_correctly(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 100000,
            'price_yearly' => 1000000,
        ]);

        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Subscription::factory()
            ->active()
            ->forTenant($tenant1)
            ->withPlan($plan)
            ->create(['billing_cycle' => 'monthly']);

        Subscription::factory()
            ->active()
            ->forTenant($tenant2)
            ->withPlan($plan)
            ->create(['billing_cycle' => 'yearly']);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscription-plans.show', $plan));

        $response->assertStatus(200);
        $response->assertViewHas('monthlyRevenue', 100000);
        $response->assertViewHas('yearlyRevenue', 1000000);
        $response->assertViewHas('totalRevenue', 1100000);
    }

    // ==================== EDIT TESTS ====================

    public function test_super_admin_can_access_edit_subscription_plan_form(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscription-plans.edit', $plan));

        $response->assertStatus(200);
        $response->assertViewIs('admin.subscription-plans.edit');
        $response->assertViewHas('subscriptionPlan');
    }

    public function test_edit_form_displays_current_values(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Existing Plan',
            'price_monthly' => 199000,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.subscription-plans.edit', $plan));

        $response->assertStatus(200);
        $response->assertSee('Existing Plan');
        $response->assertSee('199000');
    }

    // ==================== UPDATE TESTS ====================

    public function test_super_admin_can_update_subscription_plan(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Old Name',
            'price_monthly' => 99000,
        ]);

        $updateData = [
            'name' => 'New Name',
            'slug' => $plan->slug,
            'price_monthly' => 149000,
            'price_yearly' => 1490000,
            'max_outlets' => 5,
            'max_users' => 20,
            'max_products' => 1000,
            'sort_order' => 2,
        ];

        $response = $this->actingAs($this->superAdmin)->put(route('admin.subscription-plans.update', $plan), $updateData);

        $response->assertRedirect(route('admin.subscription-plans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'name' => 'New Name',
            'price_monthly' => 149000,
        ]);
    }

    public function test_update_allows_same_slug_for_same_plan(): void
    {
        $plan = SubscriptionPlan::factory()->create(['slug' => 'my-plan']);

        $updateData = [
            'name' => 'Updated Name',
            'slug' => 'my-plan',
            'price_monthly' => 149000,
            'price_yearly' => 1490000,
            'max_outlets' => 5,
            'max_users' => 20,
            'max_products' => 1000,
            'sort_order' => 2,
        ];

        $response = $this->actingAs($this->superAdmin)->put(route('admin.subscription-plans.update', $plan), $updateData);

        $response->assertRedirect(route('admin.subscription-plans.index'));
        $response->assertSessionDoesntHaveErrors('slug');
    }

    public function test_update_prevents_duplicate_slug_from_other_plan(): void
    {
        $plan1 = SubscriptionPlan::factory()->create(['slug' => 'plan-one']);
        $plan2 = SubscriptionPlan::factory()->create(['slug' => 'plan-two']);

        $updateData = [
            'name' => 'Updated Name',
            'slug' => 'plan-one',
            'price_monthly' => 149000,
            'price_yearly' => 1490000,
            'max_outlets' => 5,
            'max_users' => 20,
            'max_products' => 1000,
            'sort_order' => 2,
        ];

        $response = $this->actingAs($this->superAdmin)->put(route('admin.subscription-plans.update', $plan2), $updateData);

        $response->assertSessionHasErrors('slug');
    }

    // ==================== DELETE TESTS ====================

    public function test_super_admin_can_delete_subscription_plan_without_subscriptions(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.subscription-plans.destroy', $plan));

        $response->assertRedirect(route('admin.subscription-plans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('subscription_plans', ['id' => $plan->id]);
    }

    public function test_super_admin_cannot_delete_subscription_plan_with_active_subscriptions(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();

        Subscription::factory()
            ->forTenant($tenant)
            ->withPlan($plan)
            ->create();

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.subscription-plans.destroy', $plan));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
    }

    // ==================== FEATURE TESTS ====================

    public function test_subscription_plan_can_have_features(): void
    {
        $features = [
            'pos_core' => true,
            'inventory_basic' => true,
            'inventory_advanced' => false,
        ];

        $planData = [
            'name' => 'Feature Test Plan',
            'slug' => 'feature-test-plan',
            'price_monthly' => 150000,
            'price_yearly' => 1500000,
            'max_outlets' => 3,
            'max_users' => 10,
            'max_products' => 500,
            'features' => $features,
            'sort_order' => 1,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('admin.subscription-plans.store'), $planData);

        $response->assertRedirect(route('admin.subscription-plans.index'));

        $plan = SubscriptionPlan::where('slug', 'feature-test-plan')->first();
        $this->assertEquals($features, $plan->features);
    }

    public function test_inactive_plan_can_be_created(): void
    {
        $planData = [
            'name' => 'Inactive Plan',
            'slug' => 'inactive-plan',
            'price_monthly' => 150000,
            'price_yearly' => 1500000,
            'max_outlets' => 3,
            'max_users' => 10,
            'max_products' => 500,
            'is_active' => false,
            'sort_order' => 1,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('admin.subscription-plans.store'), $planData);

        $response->assertRedirect(route('admin.subscription-plans.index'));

        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'inactive-plan',
            'is_active' => false,
        ]);
    }

    public function test_unlimited_values_can_be_set(): void
    {
        $planData = [
            'name' => 'Unlimited Plan',
            'slug' => 'unlimited-plan',
            'price_monthly' => 1500000,
            'price_yearly' => 15000000,
            'max_outlets' => -1,
            'max_users' => -1,
            'max_products' => -1,
            'sort_order' => 1,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('admin.subscription-plans.store'), $planData);

        $response->assertRedirect(route('admin.subscription-plans.index'));

        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'unlimited-plan',
            'max_outlets' => -1,
            'max_users' => -1,
            'max_products' => -1,
        ]);
    }
}
