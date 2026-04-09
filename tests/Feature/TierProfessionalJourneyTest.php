<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\ProductCategory;
use App\Models\Recipe;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Professional Tier User Journey Test
 *
 * Tests Professional tier (Rp 599K/bulan):
 * - 5 outlets, 25 users, Unlimited products
 * - All Growth features +
 * - Recipe/BOM (auto stock deduction)
 * - Advanced Inventory (PO, receiving, adjustment)
 * - Stock transfer antar outlet
 * - Manager Authorization (void, refund, discount)
 * - Waiter App (1 device included)
 * - QR Order
 * - Multi kitchen station
 * - NO: API Access, Custom branding, KDS
 *
 * @see docs/analisis/pricing.md
 */
class TierProfessionalJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $professionalPlan;

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

        $this->professionalPlan = SubscriptionPlan::where('slug', 'professional')->first();

        // Create tenant with professional subscription
        $this->tenant = Tenant::factory()->create();
        $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->professionalPlan->id,
        ]);

        $this->owner->outlets()->attach($this->outlet->id, ['is_default' => true]);
    }

    // ============================================================
    // PROFESSIONAL TIER LIMITS
    // ============================================================

    public function test_professional_plan_has_correct_limits(): void
    {
        $this->assertEquals(5, $this->professionalPlan->max_outlets);
        $this->assertEquals(25, $this->professionalPlan->max_users);
        $this->assertEquals(-1, $this->professionalPlan->max_products); // Unlimited
    }

    public function test_professional_can_create_up_to_5_outlets(): void
    {
        // Create 4 more outlets (total 5)
        Outlet::factory()->count(4)->create(['tenant_id' => $this->tenant->id]);

        // Now at 5 outlets (limit for professional)
        $this->assertEquals(5, Outlet::where('tenant_id', $this->tenant->id)->count());
        $this->assertFalse($this->tenant->canAddOutlet());
    }

    public function test_professional_has_unlimited_products(): void
    {
        // -1 means unlimited
        $this->assertEquals(-1, $this->professionalPlan->max_products);

        // Can always add products
        $this->assertTrue($this->tenant->canAddProduct());
    }

    // ============================================================
    // PROFESSIONAL ALLOWED FEATURES
    // ============================================================

    public function test_professional_has_all_growth_features(): void
    {
        $features = $this->professionalPlan->features;

        // All growth features
        $this->assertTrue($features['pos_core'] ?? false);
        $this->assertTrue($features['product_variant'] ?? false);
        $this->assertTrue($features['product_combo'] ?? false);
        $this->assertTrue($features['discount_promo'] ?? false);
        $this->assertTrue($features['table_management'] ?? false);
        $this->assertTrue($features['inventory_basic'] ?? false);
        $this->assertTrue($features['export_excel_pdf'] ?? false);
        $this->assertTrue($features['loyalty_points'] ?? false);
    }

    public function test_professional_has_recipe_bom(): void
    {
        $this->assertTrue($this->professionalPlan->features['recipe_bom'] ?? false);
    }

    public function test_professional_has_advanced_inventory(): void
    {
        $this->assertTrue($this->professionalPlan->features['inventory_advanced'] ?? false);
    }

    public function test_professional_has_stock_transfer(): void
    {
        $this->assertTrue($this->professionalPlan->features['stock_transfer'] ?? false);
    }

    public function test_professional_has_manager_authorization(): void
    {
        $this->assertTrue($this->professionalPlan->features['manager_authorization'] ?? false);
    }

    public function test_professional_has_waiter_app(): void
    {
        $this->assertTrue($this->professionalPlan->features['waiter_app'] ?? false);
    }

    public function test_professional_has_qr_order(): void
    {
        $this->assertTrue($this->professionalPlan->features['qr_order'] ?? false);
    }

    // ============================================================
    // PROFESSIONAL BLOCKED FEATURES
    // ============================================================

    public function test_professional_no_api_access(): void
    {
        $this->assertFalse($this->professionalPlan->features['api_access'] ?? false);
    }

    public function test_professional_no_custom_branding(): void
    {
        $this->assertFalse($this->professionalPlan->features['custom_branding'] ?? false);
    }

    public function test_professional_no_kds(): void
    {
        $this->assertFalse($this->professionalPlan->features['kds'] ?? false);
    }

    // ============================================================
    // PROFESSIONAL WEB FEATURES
    // ============================================================

    public function test_professional_can_access_authorization_settings(): void
    {
        $response = $this->actingAs($this->owner)->get('/admin/authorization/settings');

        $response->assertStatus(200);
    }

    public function test_professional_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->owner)->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    public function test_professional_can_access_products(): void
    {
        $response = $this->actingAs($this->owner)->get('/menu/products');

        $response->assertStatus(200);
    }

    public function test_professional_can_create_multiple_outlets(): void
    {
        // Create 3 more outlets
        for ($i = 2; $i <= 4; $i++) {
            $response = $this->actingAs($this->owner)->post('/admin/outlets', [
                'name' => "Branch {$i}",
                'code' => "BRANCH{$i}",
                'is_active' => true,
            ]);

            $response->assertRedirect('/admin/outlets');
        }

        $this->assertEquals(4, Outlet::where('tenant_id', $this->tenant->id)->count());
    }

    // ============================================================
    // PROFESSIONAL API V1 (MOBILE APP)
    // ============================================================

    public function test_professional_api_can_get_authorization_settings(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/authorize/settings');

        $response->assertStatus(200);
    }

    public function test_professional_api_can_get_managers(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/authorize/managers');

        $response->assertStatus(200);
    }

    public function test_professional_api_subscription_shows_correct_features(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/subscription/features');

        $response->assertStatus(200);
        $this->assertArrayHasKey('data', $response->json());
    }

    // ============================================================
    // PROFESSIONAL API V2 (POS APP)
    // ============================================================

    public function test_professional_api_v2_can_get_inventory_with_advanced_features(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/inventory/items');

        $response->assertStatus(200);
    }

    public function test_professional_api_v2_can_get_low_stock_alerts(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/inventory/alerts/low-stock');

        $response->assertStatus(200);
    }

    public function test_professional_api_v2_can_get_session_history(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/sessions/history');

        $response->assertStatus(200);
    }

    public function test_professional_api_v2_can_get_detailed_reports(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v2/reports/sales/by-product?date='.now()->format('Y-m-d'));

        $response->assertStatus(200);
    }

    // ============================================================
    // COMPLETE PROFESSIONAL JOURNEY
    // ============================================================

    public function test_complete_professional_journey(): void
    {
        // 1. Login via API
        $this->owner->update(['password' => bcrypt('password123')]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->owner->email,
            'password' => 'password123',
            'device_name' => 'Test Device',
        ]);
        $token = $loginResponse->json('data.token');

        // 2. Check subscription shows Professional plan
        $subResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/subscription');
        $subResponse->assertJsonPath('data.plan.slug', 'professional');

        // 3. Check features - should have all professional features
        $features = $this->professionalPlan->features;
        $this->assertTrue($features['recipe_bom']);
        $this->assertTrue($features['inventory_advanced']);
        $this->assertTrue($features['stock_transfer']);
        $this->assertTrue($features['manager_authorization']);
        $this->assertTrue($features['waiter_app']);
        $this->assertFalse($features['api_access']);
        $this->assertFalse($features['kds']);

        // 4. Create multiple outlets (professional allows 5)
        for ($i = 2; $i <= 5; $i++) {
            $this->actingAs($this->owner)->post('/admin/outlets', [
                'name' => "Outlet {$i}",
                'code' => "OUT{$i}",
                'is_active' => true,
            ]);
        }

        $this->assertEquals(5, Outlet::where('tenant_id', $this->tenant->id)->count());

        // 5. Verify cannot create 6th outlet
        $this->assertFalse($this->tenant->fresh()->canAddOutlet());

        // 6. Verify unlimited products
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        // Should be able to add products (unlimited)
        $this->assertTrue($this->tenant->canAddProduct());
    }
}
