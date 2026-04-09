<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Enterprise Tier User Journey Test
 *
 * Tests Enterprise tier (Rp 1.499K/bulan):
 * - Unlimited outlets, Unlimited users, Unlimited products
 * - All Professional features +
 * - API Access (REST API)
 * - Custom branding (logo, warna, receipt)
 * - Waiter App unlimited devices
 * - KDS (Kitchen Display System)
 * - Custom reports
 * - Data export scheduled
 * - SLA 99.9% uptime
 * - Dedicated Account Manager
 * - Training & Onboarding session
 *
 * @see docs/analisis/pricing.md
 */
class TierEnterpriseJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $enterprisePlan;

    protected Tenant $tenant;

    protected User $owner;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        // Create all plans
        SubscriptionPlan::factory()->starter()->create();
        SubscriptionPlan::factory()->growth()->create();
        SubscriptionPlan::factory()->professional()->create();
        SubscriptionPlan::factory()->enterprise()->create();

        $this->enterprisePlan = SubscriptionPlan::where('slug', 'enterprise')->first();

        // Create tenant with enterprise subscription
        $this->tenant = Tenant::factory()->create();
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->enterprisePlan->id,
        ]);

        $this->owner->outlets()->attach($this->outlet->id, ['is_default' => true]);
    }

    // ============================================================
    // ENTERPRISE TIER LIMITS (UNLIMITED)
    // ============================================================

    public function test_enterprise_plan_has_unlimited_limits(): void
    {
        $this->assertEquals(-1, $this->enterprisePlan->max_outlets); // Unlimited
        $this->assertEquals(-1, $this->enterprisePlan->max_users); // Unlimited
        $this->assertEquals(-1, $this->enterprisePlan->max_products); // Unlimited
    }

    public function test_enterprise_can_create_unlimited_outlets(): void
    {
        // Create 20 outlets
        Outlet::factory()->count(20)->create(['tenant_id' => $this->tenant->id]);

        // Should still be able to add more
        $this->assertTrue($this->tenant->canAddOutlet());
    }

    public function test_enterprise_can_have_unlimited_users(): void
    {
        // Create 50 users
        User::factory()->count(50)->create(['tenant_id' => $this->tenant->id]);

        // Should still be able to add more
        $this->assertTrue($this->tenant->canAddUser());
    }

    public function test_enterprise_can_have_unlimited_products(): void
    {
        // -1 means unlimited
        $this->assertEquals(-1, $this->enterprisePlan->max_products);

        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        Product::factory()->count(500)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
        ]);

        // Should still be able to add more
        $this->assertTrue($this->tenant->canAddProduct());
    }

    // ============================================================
    // ENTERPRISE ALL FEATURES ENABLED
    // ============================================================

    public function test_enterprise_has_all_features(): void
    {
        $features = $this->enterprisePlan->features;

        // Core features
        $this->assertTrue($features['pos_core'] ?? false);
        $this->assertTrue($features['product_management'] ?? false);
        $this->assertTrue($features['basic_reports'] ?? false);
        $this->assertTrue($features['customer_management'] ?? false);

        // Growth features
        $this->assertTrue($features['multi_payment'] ?? false);
        $this->assertTrue($features['product_variant'] ?? false);
        $this->assertTrue($features['product_combo'] ?? false);
        $this->assertTrue($features['discount_promo'] ?? false);
        $this->assertTrue($features['inventory_basic'] ?? false);
        $this->assertTrue($features['table_management'] ?? false);
        $this->assertTrue($features['export_excel_pdf'] ?? false);
        $this->assertTrue($features['loyalty_points'] ?? false);

        // Professional features
        $this->assertTrue($features['inventory_advanced'] ?? false);
        $this->assertTrue($features['recipe_bom'] ?? false);
        $this->assertTrue($features['stock_transfer'] ?? false);
        $this->assertTrue($features['waiter_app'] ?? false);
        $this->assertTrue($features['qr_order'] ?? false);
        $this->assertTrue($features['manager_authorization'] ?? false);

        // Enterprise-only features
        $this->assertTrue($features['api_access'] ?? false);
        $this->assertTrue($features['custom_branding'] ?? false);
        $this->assertTrue($features['kds'] ?? false);
    }

    public function test_enterprise_has_api_access(): void
    {
        $this->assertTrue($this->enterprisePlan->features['api_access'] ?? false);
    }

    public function test_enterprise_has_custom_branding(): void
    {
        $this->assertTrue($this->enterprisePlan->features['custom_branding'] ?? false);
    }

    public function test_enterprise_has_kds(): void
    {
        $this->assertTrue($this->enterprisePlan->features['kds'] ?? false);
    }

    // ============================================================
    // ENTERPRISE WEB FEATURES
    // ============================================================

    public function test_enterprise_can_access_all_features(): void
    {
        // Dashboard
        $this->actingAs($this->owner)->get('/admin/dashboard')->assertStatus(200);

        // Products
        $this->actingAs($this->owner)->get('/menu/products')->assertStatus(200);

        // Categories
        $this->actingAs($this->owner)->get('/menu/categories')->assertStatus(200);

        // Authorization Settings
        $this->actingAs($this->owner)->get('/admin/authorization/settings')->assertStatus(200);

        // Outlets
        $this->actingAs($this->owner)->get('/admin/outlets')->assertStatus(200);
    }

    public function test_enterprise_has_api_access_feature(): void
    {
        $this->assertTrue($this->enterprisePlan->features['api_access'] ?? false);
    }

    public function test_enterprise_has_custom_branding_feature(): void
    {
        $this->assertTrue($this->enterprisePlan->features['custom_branding'] ?? false);
    }

    // ============================================================
    // ENTERPRISE API V1 (MOBILE APP) - FULL ACCESS
    // ============================================================

    public function test_enterprise_api_full_access(): void
    {
        Sanctum::actingAs($this->owner);

        // All endpoints should work
        $this->getJson('/api/v1/products')->assertStatus(200);
        $this->getJson('/api/v1/categories')->assertStatus(200);
        $this->getJson('/api/v1/customers')->assertStatus(200);
        $this->getJson('/api/v1/payment-methods')->assertStatus(200);
        $this->getJson('/api/v1/discounts')->assertStatus(200);
        $this->getJson('/api/v1/floors')->assertStatus(200);
        $this->getJson('/api/v1/tables')->assertStatus(200);
        $this->getJson('/api/v1/authorize/settings')->assertStatus(200);
        $this->getJson('/api/v1/subscription')->assertStatus(200);
    }

    public function test_enterprise_api_subscription_shows_all_features(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/subscription/features');

        $response->assertStatus(200);
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_enterprise_api_can_check_any_feature(): void
    {
        Sanctum::actingAs($this->owner);

        // Check API access feature specifically
        $response = $this->getJson('/api/v1/subscription/features/api_access');

        $response->assertStatus(200);
    }

    // ============================================================
    // ENTERPRISE API V2 (POS APP) - FULL ACCESS
    // ============================================================

    public function test_enterprise_api_v2_full_access(): void
    {
        Sanctum::actingAs($this->owner);

        // All v2 endpoints should work
        $this->getJson('/api/v2/sync/master')->assertStatus(200);
        $this->getJson('/api/v2/settings')->assertStatus(200);
        $this->getJson('/api/v2/settings/features')->assertStatus(200);
        $this->getJson('/api/v2/cash-drawer/status')->assertStatus(200);
        $this->getJson('/api/v2/inventory/items')->assertStatus(200);
        $this->getJson('/api/v2/inventory/alerts/low-stock')->assertStatus(200);
    }

    public function test_enterprise_api_v2_reports_full_access(): void
    {
        Sanctum::actingAs($this->owner);

        $date = now()->format('Y-m-d');

        $this->getJson("/api/v2/reports/sales/summary?date={$date}")->assertStatus(200);
        $this->getJson("/api/v2/reports/sales/by-payment-method?date={$date}")->assertStatus(200);
        $this->getJson("/api/v2/reports/sales/by-category?date={$date}")->assertStatus(200);
        $this->getJson("/api/v2/reports/sales/by-product?date={$date}")->assertStatus(200);
    }

    public function test_enterprise_api_v2_settings_shows_all_features(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/settings/features');

        $response->assertStatus(200);
    }

    // ============================================================
    // ENTERPRISE THIRD-PARTY API ACCESS
    // ============================================================

    public function test_enterprise_can_generate_api_token(): void
    {
        $this->owner->update(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->owner->email,
            'password' => 'password123',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
    }

    public function test_enterprise_api_token_can_access_all_endpoints(): void
    {
        $this->owner->update(['password' => bcrypt('password123')]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->owner->email,
            'password' => 'password123',
            'device_name' => 'Test Device',
        ]);
        $token = $loginResponse->json('data.token');

        // Access various endpoints with token
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products')
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/subscription/features')
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v2/sync/master')
            ->assertStatus(200);
    }

    // ============================================================
    // COMPLETE ENTERPRISE JOURNEY
    // ============================================================

    public function test_complete_enterprise_journey(): void
    {
        // 1. Login via API
        $this->owner->update(['password' => bcrypt('password123')]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->owner->email,
            'password' => 'password123',
            'device_name' => 'Test Device',
        ]);
        $token = $loginResponse->json('data.token');

        // 2. Check subscription shows Enterprise plan
        $subResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/subscription');
        $subResponse->assertJsonPath('data.plan.slug', 'enterprise');

        // 3. Check ALL features are enabled via plan
        $features = $this->enterprisePlan->features;
        $this->assertTrue($features['pos_core']);
        $this->assertTrue($features['product_variant']);
        $this->assertTrue($features['recipe_bom']);
        $this->assertTrue($features['inventory_advanced']);
        $this->assertTrue($features['stock_transfer']);
        $this->assertTrue($features['manager_authorization']);
        $this->assertTrue($features['waiter_app']);
        $this->assertTrue($features['qr_order']);
        $this->assertTrue($features['api_access']);
        $this->assertTrue($features['custom_branding']);
        $this->assertTrue($features['kds']);

        // 4. Create multiple outlets (unlimited)
        for ($i = 2; $i <= 10; $i++) {
            $this->actingAs($this->owner)->post('/admin/outlets', [
                'name' => "Enterprise Outlet {$i}",
                'code' => "ENT{$i}",
                'is_active' => true,
            ]);
        }

        $this->assertEquals(10, Outlet::where('tenant_id', $this->tenant->id)->count());

        // 5. Can still add more outlets (unlimited)
        $this->assertTrue($this->tenant->fresh()->canAddOutlet());

        // 6. Verify products are unlimited
        $this->assertTrue($this->tenant->canAddProduct());

        // 7. Use API v2 for advanced features
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v2/inventory/alerts/low-stock')
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v2/reports/sales/summary?date='.now()->format('Y-m-d'))
            ->assertStatus(200);

        // 8. Verify full API access for third-party integrations
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products')
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/customers')
            ->assertStatus(200);
    }

    // ============================================================
    // ENTERPRISE UPGRADE PATH TEST
    // ============================================================

    public function test_enterprise_shows_upgrade_not_available(): void
    {
        Sanctum::actingAs($this->owner);

        // Enterprise is the top tier, no upgrade available
        $response = $this->getJson('/api/v1/subscription');

        $response->assertStatus(200)
            ->assertJsonPath('data.plan.slug', 'enterprise');

        // Verify it's the highest tier
        $this->assertEquals(4, $this->enterprisePlan->sort_order);
    }
}
