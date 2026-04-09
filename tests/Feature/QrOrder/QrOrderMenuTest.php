<?php

namespace Tests\Feature\QrOrder;

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
use Tests\TestCase;

class QrOrderMenuTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Outlet $outlet;

    private Table $table;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        SubscriptionPlan::factory()->professional()->create();
        $proPlan = SubscriptionPlan::where('slug', 'professional')->first();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $proPlan->id,
        ]);

        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);
        $this->table->generateQrToken();

        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'base_price' => 25000,
            'is_active' => true,
            'show_in_menu' => true,
        ]);
    }

    public function test_can_access_qr_menu_with_valid_token(): void
    {
        $response = $this->get("/qr/{$this->table->qr_token}");

        $response->assertOk();
        $response->assertViewIs('qr-menu.show');
        $response->assertViewHas('outlet');
        $response->assertViewHas('table');
        $response->assertViewHas('products');
        $response->assertViewHas('categories');
    }

    public function test_qr_menu_returns_404_for_invalid_token(): void
    {
        $response = $this->get('/qr/invalid-token-here');

        $response->assertNotFound();
    }

    public function test_qr_menu_returns_404_for_inactive_table(): void
    {
        $this->table->update(['is_active' => false]);

        $response = $this->get("/qr/{$this->table->qr_token}");

        $response->assertNotFound();
    }

    public function test_qr_menu_shows_products_with_show_in_menu(): void
    {
        // Create a product that is NOT shown in menu
        $hiddenProduct = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->product->category_id,
            'name' => 'Hidden Item',
            'base_price' => 10000,
            'is_active' => true,
            'show_in_menu' => false,
        ]);

        $response = $this->get("/qr/{$this->table->qr_token}");

        $response->assertOk();
        $products = $response->viewData('products');
        $this->assertTrue($products->contains('id', $this->product->id));
        $this->assertFalse($products->contains('id', $hiddenProduct->id));
    }

    public function test_qr_menu_blocked_when_feature_not_available(): void
    {
        // Switch to starter plan (no qr_order)
        SubscriptionPlan::factory()->starter()->create();
        $starterPlan = SubscriptionPlan::where('slug', 'starter')->first();

        Subscription::where('tenant_id', $this->tenant->id)->delete();
        Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $starterPlan->id,
        ]);

        $response = $this->get("/qr/{$this->table->qr_token}");

        $response->assertForbidden();
    }

    public function test_table_can_generate_and_revoke_qr_token(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);

        $this->assertFalse($table->hasQrCode());
        $this->assertNull($table->getQrMenuUrl());

        $token = $table->generateQrToken();
        $table->refresh();

        $this->assertTrue($table->hasQrCode());
        $this->assertNotNull($table->getQrMenuUrl());
        $this->assertStringContains("/qr/{$token}", $table->getQrMenuUrl());

        $table->revokeQrToken();
        $table->refresh();

        $this->assertFalse($table->hasQrCode());
        $this->assertNull($table->getQrMenuUrl());
    }

    /**
     * Assert that a string contains a substring.
     */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
