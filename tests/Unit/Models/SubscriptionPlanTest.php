<?php

namespace Tests\Unit\Models;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    // ==================== CREATION TESTS ====================

    public function test_can_create_subscription_plan(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price_monthly' => 199000,
        ]);

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price_monthly' => 199000,
        ]);
    }

    public function test_subscription_plan_has_required_attributes(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->assertNotNull($plan->id);
        $this->assertNotNull($plan->name);
        $this->assertNotNull($plan->slug);
        $this->assertNotNull($plan->price_monthly);
    }

    public function test_subscription_plan_can_have_optional_attributes(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'description' => 'A great plan for small businesses',
            'price_yearly' => 1900000,
        ]);

        $this->assertEquals('A great plan for small businesses', $plan->description);
        $this->assertEquals(1900000, $plan->price_yearly);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_subscription_plan_has_many_subscriptions(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();

        Subscription::factory()->count(3)->create([
            'subscription_plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertCount(3, $plan->subscriptions);
    }

    public function test_subscription_plan_has_many_invoices(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->assertInstanceOf(HasMany::class, $plan->invoices());
    }

    // ==================== SCOPE TESTS ====================

    public function test_scope_active(): void
    {
        SubscriptionPlan::factory()->count(2)->create(['is_active' => true]);
        SubscriptionPlan::factory()->inactive()->create();

        $activePlans = SubscriptionPlan::active()->get();

        $this->assertCount(2, $activePlans);
    }

    public function test_scope_ordered(): void
    {
        SubscriptionPlan::factory()->withSortOrder(3)->create(['name' => 'Plan C']);
        SubscriptionPlan::factory()->withSortOrder(1)->create(['name' => 'Plan A']);
        SubscriptionPlan::factory()->withSortOrder(2)->create(['name' => 'Plan B']);

        $orderedPlans = SubscriptionPlan::ordered()->get();

        $this->assertEquals('Plan A', $orderedPlans[0]->name);
        $this->assertEquals('Plan B', $orderedPlans[1]->name);
        $this->assertEquals('Plan C', $orderedPlans[2]->name);
    }

    // ==================== GET PRICE METHOD TESTS ====================

    public function test_get_price_returns_monthly_price(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 299000,
            'price_yearly' => 2870400,
        ]);

        $this->assertEquals(299000, $plan->getPrice('monthly'));
    }

    public function test_get_price_returns_yearly_price(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 299000,
            'price_yearly' => 2870400,
        ]);

        $this->assertEquals(2870400, $plan->getPrice('yearly'));
    }

    public function test_get_price_defaults_to_monthly(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 99000,
            'price_yearly' => 950400,
        ]);

        $this->assertEquals(99000, $plan->getPrice('invalid'));
    }

    // ==================== IS UNLIMITED METHOD TESTS ====================

    public function test_is_unlimited_returns_true_when_outlets_unlimited(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'max_outlets' => -1,
            'max_users' => 10,
        ]);

        $this->assertTrue($plan->isUnlimited());
    }

    public function test_is_unlimited_returns_true_when_users_unlimited(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'max_outlets' => 5,
            'max_users' => -1,
        ]);

        $this->assertTrue($plan->isUnlimited());
    }

    public function test_is_unlimited_returns_true_when_both_unlimited(): void
    {
        $plan = SubscriptionPlan::factory()->unlimited()->create();

        $this->assertTrue($plan->isUnlimited());
    }

    public function test_is_unlimited_returns_false_when_limited(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'max_outlets' => 2,
            'max_users' => 10,
        ]);

        $this->assertFalse($plan->isUnlimited());
    }

    // ==================== FORMATTED PRICE TESTS ====================

    public function test_get_formatted_price_monthly(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 299000,
        ]);

        $this->assertEquals('Rp 299.000', $plan->getFormattedPriceMonthly());
    }

    public function test_get_formatted_price_yearly(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_yearly' => 2870400,
        ]);

        $this->assertEquals('Rp 2.870.400', $plan->getFormattedPriceYearly());
    }

    public function test_formatted_price_with_large_number(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 1499000,
        ]);

        $this->assertEquals('Rp 1.499.000', $plan->getFormattedPriceMonthly());
    }

    // ==================== CASTING TESTS ====================

    public function test_price_monthly_is_cast_to_decimal(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 299000.50,
        ]);

        $this->assertEquals(299000.50, $plan->price_monthly);
    }

    public function test_price_yearly_is_cast_to_decimal(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_yearly' => 2870400.50,
        ]);

        $this->assertEquals(2870400.50, $plan->price_yearly);
    }

    public function test_max_outlets_is_cast_to_integer(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'max_outlets' => 5,
        ]);

        $this->assertIsInt($plan->max_outlets);
        $this->assertEquals(5, $plan->max_outlets);
    }

    public function test_max_users_is_cast_to_integer(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'max_users' => 25,
        ]);

        $this->assertIsInt($plan->max_users);
        $this->assertEquals(25, $plan->max_users);
    }

    public function test_max_products_is_cast_to_integer(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'max_products' => 500,
        ]);

        $this->assertIsInt($plan->max_products);
        $this->assertEquals(500, $plan->max_products);
    }

    public function test_features_is_cast_to_array(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'features' => ['pos_core' => true, 'inventory' => false],
        ]);

        $this->assertIsArray($plan->features);
        $this->assertTrue($plan->features['pos_core']);
        $this->assertFalse($plan->features['inventory']);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'is_active' => 1,
        ]);

        $this->assertIsBool($plan->is_active);
        $this->assertTrue($plan->is_active);
    }

    public function test_sort_order_is_cast_to_integer(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'sort_order' => 3,
        ]);

        $this->assertIsInt($plan->sort_order);
        $this->assertEquals(3, $plan->sort_order);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_starter_state(): void
    {
        $plan = SubscriptionPlan::factory()->starter()->create();

        $this->assertEquals('Starter', $plan->name);
        $this->assertEquals('starter', $plan->slug);
        $this->assertEquals(99000, $plan->price_monthly);
        $this->assertEquals(1, $plan->max_outlets);
        $this->assertEquals(3, $plan->max_users);
        $this->assertEquals(100, $plan->max_products);
    }

    public function test_factory_growth_state(): void
    {
        $plan = SubscriptionPlan::factory()->growth()->create();

        $this->assertEquals('Growth', $plan->name);
        $this->assertEquals('growth', $plan->slug);
        $this->assertEquals(299000, $plan->price_monthly);
        $this->assertEquals(2, $plan->max_outlets);
        $this->assertEquals(10, $plan->max_users);
        $this->assertEquals(500, $plan->max_products);
    }

    public function test_factory_professional_state(): void
    {
        $plan = SubscriptionPlan::factory()->professional()->create();

        $this->assertEquals('Professional', $plan->name);
        $this->assertEquals('professional', $plan->slug);
        $this->assertEquals(599000, $plan->price_monthly);
        $this->assertEquals(5, $plan->max_outlets);
        $this->assertEquals(25, $plan->max_users);
        $this->assertEquals(-1, $plan->max_products); // Unlimited products
    }

    public function test_factory_enterprise_state(): void
    {
        $plan = SubscriptionPlan::factory()->enterprise()->create();

        $this->assertEquals('Enterprise', $plan->name);
        $this->assertEquals('enterprise', $plan->slug);
        $this->assertEquals(1499000, $plan->price_monthly);
        $this->assertEquals(-1, $plan->max_outlets); // Unlimited
        $this->assertEquals(-1, $plan->max_users); // Unlimited
        $this->assertEquals(-1, $plan->max_products); // Unlimited
    }

    public function test_factory_inactive_state(): void
    {
        $plan = SubscriptionPlan::factory()->inactive()->create();

        $this->assertFalse($plan->is_active);
    }

    public function test_factory_with_sort_order_state(): void
    {
        $plan = SubscriptionPlan::factory()->withSortOrder(5)->create();

        $this->assertEquals(5, $plan->sort_order);
    }

    public function test_factory_unlimited_state(): void
    {
        $plan = SubscriptionPlan::factory()->unlimited()->create();

        $this->assertEquals(-1, $plan->max_outlets);
        $this->assertEquals(-1, $plan->max_users);
        $this->assertEquals(-1, $plan->max_products);
    }

    public function test_factory_with_limits_state(): void
    {
        $plan = SubscriptionPlan::factory()->withLimits(3, 15, 250)->create();

        $this->assertEquals(3, $plan->max_outlets);
        $this->assertEquals(15, $plan->max_users);
        $this->assertEquals(250, $plan->max_products);
    }

    public function test_factory_with_pricing_state(): void
    {
        $plan = SubscriptionPlan::factory()->withPricing(150000, 1500000)->create();

        $this->assertEquals(150000, $plan->price_monthly);
        $this->assertEquals(1500000, $plan->price_yearly);
    }

    // ==================== FEATURE FLAGS TESTS ====================

    public function test_starter_plan_features(): void
    {
        $plan = SubscriptionPlan::factory()->starter()->create();

        $this->assertTrue($plan->features['pos_core']);
        $this->assertTrue($plan->features['product_management']);
        $this->assertTrue($plan->features['basic_reports']);
        $this->assertFalse($plan->features['inventory_basic']);
        $this->assertFalse($plan->features['product_variant']);
        $this->assertFalse($plan->features['api_access']);
    }

    public function test_growth_plan_features(): void
    {
        $plan = SubscriptionPlan::factory()->growth()->create();

        $this->assertTrue($plan->features['pos_core']);
        $this->assertTrue($plan->features['product_variant']);
        $this->assertTrue($plan->features['discount_promo']);
        $this->assertTrue($plan->features['inventory_basic']);
        $this->assertFalse($plan->features['inventory_advanced']);
        $this->assertFalse($plan->features['recipe_bom']);
    }

    public function test_professional_plan_features(): void
    {
        $plan = SubscriptionPlan::factory()->professional()->create();

        $this->assertTrue($plan->features['inventory_advanced']);
        $this->assertTrue($plan->features['recipe_bom']);
        $this->assertTrue($plan->features['stock_transfer']);
        $this->assertTrue($plan->features['manager_authorization']);
        $this->assertFalse($plan->features['kds']);
        $this->assertFalse($plan->features['api_access']);
    }

    public function test_enterprise_plan_features(): void
    {
        $plan = SubscriptionPlan::factory()->enterprise()->create();

        $this->assertTrue($plan->features['kds']);
        $this->assertTrue($plan->features['api_access']);
        $this->assertTrue($plan->features['custom_branding']);
    }

    // ==================== COMBINED SCOPE TESTS ====================

    public function test_combined_active_and_ordered_scopes(): void
    {
        SubscriptionPlan::factory()->withSortOrder(2)->create(['name' => 'Active B', 'is_active' => true]);
        SubscriptionPlan::factory()->withSortOrder(1)->create(['name' => 'Active A', 'is_active' => true]);
        SubscriptionPlan::factory()->inactive()->withSortOrder(0)->create(['name' => 'Inactive']);

        $plans = SubscriptionPlan::active()->ordered()->get();

        $this->assertCount(2, $plans);
        $this->assertEquals('Active A', $plans[0]->name);
        $this->assertEquals('Active B', $plans[1]->name);
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_plan_with_zero_price(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 0,
            'price_yearly' => 0,
        ]);

        $this->assertEquals(0, $plan->price_monthly);
        $this->assertEquals('Rp 0', $plan->getFormattedPriceMonthly());
    }

    public function test_plan_with_null_description(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'description' => null,
        ]);

        $this->assertNull($plan->description);
    }

    public function test_plan_with_empty_features(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'features' => [],
        ]);

        $this->assertIsArray($plan->features);
        $this->assertEmpty($plan->features);
    }

    public function test_yearly_discount_calculation(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 299000,
            'price_yearly' => 2870400, // 20% discount
        ]);

        $monthlyAnnualized = $plan->price_monthly * 12;
        $discount = ($monthlyAnnualized - $plan->price_yearly) / $monthlyAnnualized * 100;

        $this->assertEquals(20, round($discount));
    }
}
