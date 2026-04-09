<?php

namespace Tests\Feature\Api\V1;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant and user
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create subscription plans
        $this->createSubscriptionPlans();
    }

    protected function createSubscriptionPlans(): void
    {
        SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Perfect for small businesses',
            'price_monthly' => 99000,
            'price_yearly' => 990000,
            'max_outlets' => 1,
            'max_users' => 3,
            'max_products' => 100,
            'features' => [
                'pos_core' => true,
                'basic_reports' => true,
                'inventory_basic' => false,
                'inventory_advanced' => false,
                'table_management' => false,
                'qr_order' => false,
                'api_access' => false,
            ],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'description' => 'For growing businesses',
            'price_monthly' => 299000,
            'price_yearly' => 2990000,
            'max_outlets' => 3,
            'max_users' => 10,
            'max_products' => 500,
            'features' => [
                'pos_core' => true,
                'basic_reports' => true,
                'inventory_basic' => true,
                'inventory_advanced' => false,
                'table_management' => true,
                'qr_order' => false,
                'api_access' => false,
            ],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        SubscriptionPlan::create([
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'Full-featured for professionals',
            'price_monthly' => 599000,
            'price_yearly' => 5990000,
            'max_outlets' => 10,
            'max_users' => 50,
            'max_products' => -1, // unlimited
            'features' => [
                'pos_core' => true,
                'basic_reports' => true,
                'inventory_basic' => true,
                'inventory_advanced' => true,
                'table_management' => true,
                'qr_order' => true,
                'api_access' => true,
            ],
            'is_active' => true,
            'sort_order' => 3,
        ]);

        SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'For large enterprises',
            'price_monthly' => 1499000,
            'price_yearly' => 14990000,
            'max_outlets' => -1, // unlimited
            'max_users' => -1, // unlimited
            'max_products' => -1, // unlimited
            'features' => [
                'pos_core' => true,
                'basic_reports' => true,
                'inventory_basic' => true,
                'inventory_advanced' => true,
                'table_management' => true,
                'qr_order' => true,
                'api_access' => true,
                'priority_support' => true,
                'custom_integration' => true,
            ],
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Inactive plan (should not be returned)
        SubscriptionPlan::create([
            'name' => 'Legacy',
            'slug' => 'legacy',
            'description' => 'Deprecated plan',
            'price_monthly' => 50000,
            'price_yearly' => 500000,
            'max_outlets' => 1,
            'max_users' => 1,
            'max_products' => 50,
            'features' => [],
            'is_active' => false,
            'sort_order' => 99,
        ]);
    }

    /** @test */
    public function guest_can_list_subscription_plans(): void
    {
        $response = $this->getJson('/api/v1/subscription-plans');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'price_monthly',
                        'price_yearly',
                        'price_monthly_formatted',
                        'price_yearly_formatted',
                        'max_outlets',
                        'max_users',
                        'max_products',
                        'features',
                        'is_popular',
                    ],
                ],
            ]);

        // Should only return active plans
        $this->assertCount(4, $response->json('data'));

        // Should be ordered by sort_order
        $plans = $response->json('data');
        $this->assertEquals('starter', $plans[0]['slug']);
        $this->assertEquals('growth', $plans[1]['slug']);
        $this->assertEquals('professional', $plans[2]['slug']);
        $this->assertEquals('enterprise', $plans[3]['slug']);
    }

    /** @test */
    public function guest_can_view_single_subscription_plan(): void
    {
        $plan = SubscriptionPlan::where('slug', 'growth')->first();

        $response = $this->getJson("/api/v1/subscription-plans/{$plan->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'price_monthly',
                    'price_yearly',
                    'price_monthly_formatted',
                    'price_yearly_formatted',
                    'max_outlets',
                    'max_users',
                    'max_products',
                    'features',
                ],
            ])
            ->assertJsonPath('data.slug', 'growth')
            ->assertJsonPath('data.price_monthly', 299000);
    }

    /** @test */
    public function guest_can_view_plan_by_slug(): void
    {
        $response = $this->getJson('/api/v1/subscription-plans/slug/professional');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'professional')
            ->assertJsonPath('data.price_monthly', 599000);
    }

    /** @test */
    public function returns_404_for_inactive_plan(): void
    {
        $inactivePlan = SubscriptionPlan::where('slug', 'legacy')->first();

        $response = $this->getJson("/api/v1/subscription-plans/{$inactivePlan->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function returns_404_for_non_existent_plan(): void
    {
        $response = $this->getJson('/api/v1/subscription-plans/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function returns_404_for_non_existent_slug(): void
    {
        $response = $this->getJson('/api/v1/subscription-plans/slug/non-existent');

        $response->assertNotFound();
    }

    /** @test */
    public function plan_features_are_properly_formatted(): void
    {
        $response = $this->getJson('/api/v1/subscription-plans');

        $growthPlan = collect($response->json('data'))->firstWhere('slug', 'growth');

        $this->assertTrue($growthPlan['features']['pos_core']);
        $this->assertTrue($growthPlan['features']['inventory_basic']);
        $this->assertFalse($growthPlan['features']['inventory_advanced']);
        $this->assertFalse($growthPlan['features']['api_access']);
    }

    /** @test */
    public function unlimited_values_are_represented_correctly(): void
    {
        $response = $this->getJson('/api/v1/subscription-plans');

        $enterprisePlan = collect($response->json('data'))->firstWhere('slug', 'enterprise');

        $this->assertEquals(-1, $enterprisePlan['max_outlets']);
        $this->assertEquals(-1, $enterprisePlan['max_users']);
        $this->assertEquals(-1, $enterprisePlan['max_products']);
    }

    /** @test */
    public function can_compare_plans(): void
    {
        $response = $this->getJson('/api/v1/subscription-plans/compare?plans=starter,growth,professional');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price_monthly',
                        'features',
                    ],
                ],
                'feature_list',
            ]);

        $this->assertCount(3, $response->json('data'));
    }
}
