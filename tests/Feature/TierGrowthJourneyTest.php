<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\Floor;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Growth Tier User Journey Test
 *
 * Tests Growth tier (Rp 299K/bulan):
 * - 2 outlets, 10 users, 500 products
 * - All Starter features +
 * - Product variants & combos
 * - Modifiers/Add-ons
 * - Table Management
 * - Basic Inventory (stock tracking)
 * - Multi payment method
 * - Discounts & promos
 * - Export Excel/PDF
 * - Customer loyalty points
 * - NO: Recipe/BOM, Stock transfer, Waiter App, API Access
 *
 * @see docs/analisis/pricing.md
 */
class TierGrowthJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $growthPlan;

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

        $this->growthPlan = SubscriptionPlan::where('slug', 'growth')->first();

        // Create tenant with growth subscription
        $this->tenant = Tenant::factory()->create();
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
        ]);

        $this->owner->outlets()->attach($this->outlet->id, ['is_default' => true]);
    }

    // ============================================================
    // GROWTH TIER LIMITS
    // ============================================================

    public function test_growth_plan_has_correct_limits(): void
    {
        $this->assertEquals(2, $this->growthPlan->max_outlets);
        $this->assertEquals(10, $this->growthPlan->max_users);
        $this->assertEquals(500, $this->growthPlan->max_products);
    }

    public function test_growth_can_create_second_outlet(): void
    {
        // Growth has 2 outlets limit, currently 1
        $this->assertTrue($this->tenant->canAddOutlet());

        $response = $this->actingAs($this->owner)->post('/admin/outlets', [
            'name' => 'Branch 2',
            'code' => 'BRANCH2',
            'address' => 'Jl. Test No. 2',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/outlets');

        $this->assertDatabaseHas('outlets', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Branch 2',
        ]);
    }

    public function test_growth_cannot_create_third_outlet(): void
    {
        // Create 2nd outlet (at limit)
        Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        // Now at 2 outlets (limit for growth)
        $this->assertFalse($this->tenant->canAddOutlet());
    }

    // ============================================================
    // GROWTH ALLOWED FEATURES
    // ============================================================

    public function test_growth_has_variant_feature(): void
    {
        $this->assertTrue($this->growthPlan->features['product_variant'] ?? false);
    }

    public function test_growth_has_combo_feature(): void
    {
        $this->assertTrue($this->growthPlan->features['product_combo'] ?? false);
    }

    public function test_growth_has_discount_promo_feature(): void
    {
        $this->assertTrue($this->growthPlan->features['discount_promo'] ?? false);
    }

    public function test_growth_has_table_management_feature(): void
    {
        $this->assertTrue($this->growthPlan->features['table_management'] ?? false);
    }

    public function test_growth_has_inventory_basic_feature(): void
    {
        $this->assertTrue($this->growthPlan->features['inventory_basic'] ?? false);
    }

    public function test_growth_has_export_feature(): void
    {
        $this->assertTrue($this->growthPlan->features['export_excel_pdf'] ?? false);
    }

    public function test_growth_has_loyalty_feature(): void
    {
        $this->assertTrue($this->growthPlan->features['loyalty_points'] ?? false);
    }

    // ============================================================
    // GROWTH BLOCKED FEATURES
    // ============================================================

    public function test_growth_no_recipe_bom(): void
    {
        $this->assertFalse($this->growthPlan->features['recipe_bom'] ?? false);
    }

    public function test_growth_no_stock_transfer(): void
    {
        $this->assertFalse($this->growthPlan->features['stock_transfer'] ?? false);
    }

    public function test_growth_no_waiter_app(): void
    {
        $this->assertFalse($this->growthPlan->features['waiter_app'] ?? false);
    }

    public function test_growth_no_api_access(): void
    {
        $this->assertFalse($this->growthPlan->features['api_access'] ?? false);
    }

    public function test_growth_no_kds(): void
    {
        $this->assertFalse($this->growthPlan->features['kds'] ?? false);
    }

    // ============================================================
    // GROWTH WEB FEATURES
    // ============================================================

    public function test_growth_can_create_variant_product(): void
    {
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->owner)->post('/menu/products', [
            'sku' => 'VARIANT-001',
            'name' => 'Es Kopi',
            'category_id' => $category->id,
            'base_price' => 20000,
            'product_type' => 'variant',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('products', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Es Kopi',
            'product_type' => 'variant',
        ]);
    }

    public function test_growth_has_table_management_enabled(): void
    {
        // Growth tier has table management feature
        $this->assertTrue($this->growthPlan->features['table_management'] ?? false);
    }

    public function test_growth_has_inventory_basic_enabled(): void
    {
        // Growth tier has basic inventory feature
        $this->assertTrue($this->growthPlan->features['inventory_basic'] ?? false);
    }

    public function test_growth_has_discount_promo_enabled(): void
    {
        // Growth tier has discount/promo feature
        $this->assertTrue($this->growthPlan->features['discount_promo'] ?? false);
    }

    public function test_growth_can_access_categories(): void
    {
        $response = $this->actingAs($this->owner)->get('/menu/categories');

        $response->assertStatus(200);
    }

    // ============================================================
    // GROWTH API V1 (MOBILE APP)
    // ============================================================

    public function test_growth_api_can_list_products_with_variants(): void
    {
        Sanctum::actingAs($this->owner);

        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'product_type' => 'variant',
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
    }

    public function test_growth_api_can_list_floors(): void
    {
        Sanctum::actingAs($this->owner);

        Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->getJson('/api/v1/floors');

        $response->assertStatus(200);
    }

    public function test_growth_api_can_list_tables(): void
    {
        Sanctum::actingAs($this->owner);

        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);

        $response = $this->getJson('/api/v1/tables');

        $response->assertStatus(200);
    }

    public function test_growth_api_can_list_discounts(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/discounts');

        $response->assertStatus(200);
    }

    public function test_growth_api_subscription_shows_correct_features(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/subscription/features');

        $response->assertStatus(200);
        $this->assertArrayHasKey('data', $response->json());
    }

    // ============================================================
    // GROWTH API V2 (POS APP)
    // ============================================================

    public function test_growth_api_v2_can_sync_master(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/sync/master');

        $response->assertStatus(200);
    }

    public function test_growth_api_v2_can_get_inventory_items(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/inventory/items');

        $response->assertStatus(200);
    }

    public function test_growth_api_v2_can_get_cash_drawer_status(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/cash-drawer/status');

        $response->assertStatus(200);
    }

    public function test_growth_api_v2_can_get_sales_reports(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/reports/sales/summary?date='.now()->format('Y-m-d'));

        $response->assertStatus(200);
    }

    // ============================================================
    // COMPLETE GROWTH JOURNEY
    // ============================================================

    public function test_complete_growth_journey(): void
    {
        // 1. Login via API
        $this->owner->update(['password' => bcrypt('password123')]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->owner->email,
            'password' => 'password123',
            'device_name' => 'Test Device',
        ]);
        $token = $loginResponse->json('data.token');

        // 2. Check subscription shows Growth plan
        $subResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/subscription');
        $subResponse->assertJsonPath('data.plan.slug', 'growth');

        // 3. Check plan features from factory
        $features = $this->growthPlan->features;
        $this->assertTrue($features['product_variant']);
        $this->assertTrue($features['table_management']);
        $this->assertTrue($features['discount_promo']);
        $this->assertFalse($features['recipe_bom']);
        $this->assertFalse($features['api_access']);

        // 4. Create variant product via web
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->owner)->post('/menu/products', [
            'sku' => 'GROWTH-001',
            'name' => 'Es Kopi Susu',
            'category_id' => $category->id,
            'base_price' => 25000,
            'product_type' => 'variant',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Es Kopi Susu',
            'product_type' => 'variant',
        ]);

        // 5. Create second outlet (growth allows 2)
        $this->actingAs($this->owner)->post('/admin/outlets', [
            'name' => 'Cabang Kedua',
            'code' => 'CAB2',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('outlets', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Cabang Kedua',
        ]);

        // 6. Verify cannot create 3rd outlet
        $this->assertEquals(2, Outlet::where('tenant_id', $this->tenant->id)->count());
        $this->assertFalse($this->tenant->fresh()->canAddOutlet());
    }
}
