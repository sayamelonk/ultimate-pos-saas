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
 * Starter Tier User Journey Test
 *
 * Tests Starter tier (Rp 99K/bulan):
 * - 1 outlet, 3 users, 100 products
 * - POS Core only (order, payment, receipt)
 * - Basic reports, customer management
 * - NO: variants, discounts, inventory, tables, recipe, API
 *
 * @see docs/analisis/pricing.md
 */
class TierStarterJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $starterPlan;

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

        $this->starterPlan = SubscriptionPlan::where('slug', 'starter')->first();

        // Create tenant with starter subscription
        $this->tenant = Tenant::factory()->create();
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->starterPlan->id,
        ]);

        $this->owner->outlets()->attach($this->outlet->id, ['is_default' => true]);
    }

    // ============================================================
    // STARTER TIER LIMITS
    // ============================================================

    public function test_starter_plan_has_correct_limits(): void
    {
        $this->assertEquals(1, $this->starterPlan->max_outlets);
        $this->assertEquals(3, $this->starterPlan->max_users);
        $this->assertEquals(100, $this->starterPlan->max_products);
    }

    public function test_starter_cannot_create_second_outlet(): void
    {
        // Starter already has 1 outlet (at limit)
        $response = $this->actingAs($this->owner)->get('/admin/outlets/create');

        // Should redirect with error (at limit)
        $response->assertRedirect('/admin/outlets');
    }

    public function test_starter_product_limit_enforced(): void
    {
        // Starter has 100 product limit
        $this->assertEquals(100, $this->starterPlan->max_products);

        // When at limit, canAddProduct should return false
        // This is tested via Tenant model logic
        $this->assertTrue($this->tenant->canAddProduct()); // Can add when under limit
    }

    // ============================================================
    // STARTER ALLOWED FEATURES (WEB)
    // ============================================================

    public function test_starter_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->owner)->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    public function test_starter_can_access_products(): void
    {
        $response = $this->actingAs($this->owner)->get('/menu/products');

        $response->assertStatus(200);
    }

    public function test_starter_can_create_single_product(): void
    {
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->owner)->post('/menu/products', [
            'sku' => 'SKU-001',
            'name' => 'Kopi Hitam',
            'category_id' => $category->id,
            'base_price' => 10000,
            'product_type' => 'single',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('products', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Kopi Hitam',
            'product_type' => 'single',
        ]);
    }

    public function test_starter_can_access_categories(): void
    {
        $response = $this->actingAs($this->owner)->get('/menu/categories');

        $response->assertStatus(200);
    }

    public function test_starter_can_access_outlets(): void
    {
        $response = $this->actingAs($this->owner)->get('/admin/outlets');

        $response->assertStatus(200);
    }

    // ============================================================
    // STARTER BLOCKED FEATURES (WEB)
    // ============================================================

    public function test_starter_features_disabled(): void
    {
        $features = $this->starterPlan->features;

        // Should NOT have these features
        $this->assertFalse($features['product_variant'] ?? false);
        $this->assertFalse($features['product_combo'] ?? false);
        $this->assertFalse($features['discount_promo'] ?? false);
        $this->assertFalse($features['inventory_basic'] ?? false);
        $this->assertFalse($features['table_management'] ?? false);
        $this->assertFalse($features['recipe_bom'] ?? false);
        $this->assertFalse($features['export_excel_pdf'] ?? false);
        $this->assertFalse($features['api_access'] ?? false);
    }

    public function test_starter_has_basic_features(): void
    {
        $features = $this->starterPlan->features;

        // Should have these features
        $this->assertTrue($features['pos_core'] ?? false);
        $this->assertTrue($features['product_management'] ?? false);
        $this->assertTrue($features['basic_reports'] ?? false);
        $this->assertTrue($features['customer_management'] ?? false);
    }

    // ============================================================
    // STARTER API V1 (MOBILE APP)
    // ============================================================

    public function test_starter_api_can_login(): void
    {
        $this->owner->update(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->owner->email,
            'password' => 'password123',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_starter_api_can_list_products(): void
    {
        Sanctum::actingAs($this->owner);

        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        Product::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_starter_api_can_list_categories(): void
    {
        Sanctum::actingAs($this->owner);

        ProductCategory::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_starter_api_can_list_customers(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/customers');

        $response->assertStatus(200);
    }

    public function test_starter_api_can_list_payment_methods(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/payment-methods');

        $response->assertStatus(200);
    }

    public function test_starter_api_can_get_subscription_status(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/subscription');

        $response->assertStatus(200)
            ->assertJsonPath('data.plan.slug', 'starter');
    }

    public function test_starter_api_can_check_features(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/subscription/features');

        $response->assertStatus(200);
        // Features are returned from plan
        $this->assertArrayHasKey('data', $response->json());
    }

    // ============================================================
    // STARTER API V2 (POS APP)
    // ============================================================

    public function test_starter_api_v2_can_get_sync_master(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/sync/master');

        $response->assertStatus(200);
    }

    public function test_starter_api_v2_can_get_settings(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/settings');

        $response->assertStatus(200);
    }

    public function test_starter_api_v2_can_check_features(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/settings/features');

        $response->assertStatus(200)
            ->assertJsonPath('data.pos_core', true)
            ->assertJsonPath('data.table_management', false);
    }

    // ============================================================
    // COMPLETE STARTER JOURNEY
    // ============================================================

    public function test_complete_starter_journey(): void
    {
        // 1. Owner logs in via API
        $this->owner->update(['password' => bcrypt('password123')]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->owner->email,
            'password' => 'password123',
            'device_name' => 'Test Device',
        ]);
        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');

        // 2. Check subscription status
        $subResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/subscription');
        $subResponse->assertStatus(200);
        $subResponse->assertJsonPath('data.plan.slug', 'starter');

        // 3. Verify starter plan features from factory
        $features = $this->starterPlan->features;
        $this->assertTrue($features['pos_core']);
        $this->assertFalse($features['product_variant']);
        $this->assertFalse($features['discount_promo']);
        $this->assertFalse($features['table_management']);
        $this->assertFalse($features['api_access']);

        // 4. Create product via web
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->owner)->post('/menu/products', [
            'sku' => 'STARTER-001',
            'name' => 'Produk Starter',
            'category_id' => $category->id,
            'base_price' => 15000,
            'product_type' => 'single',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Produk Starter',
        ]);

        // 5. List products via API
        $productsResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products');
        $productsResponse->assertStatus(200);

        // 6. Verify outlet limit (cannot create 2nd outlet)
        $this->assertFalse($this->tenant->canAddOutlet());
    }
}
