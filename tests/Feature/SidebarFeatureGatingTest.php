<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarFeatureGatingTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithPlan(string $planType): array
    {
        $plan = SubscriptionPlan::factory()->{$planType}()->create();
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()
            ->active()
            ->forTenant($tenant)
            ->withPlan($plan)
            ->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        return compact('tenant', 'plan', 'subscription', 'user');
    }

    /**
     * Test Starter plan should NOT see inventory section in sidebar.
     */
    public function test_starter_plan_does_not_see_inventory_section(): void
    {
        $data = $this->createTenantWithPlan('starter');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // Starter has inventory_basic = false, should not see inventory section
        $response->assertDontSee('href="'.route('inventory.items.index').'"', false);
        $response->assertDontSee('href="'.route('inventory.stocks.index').'"', false);
    }

    /**
     * Test Growth plan SHOULD see basic inventory menu items.
     */
    public function test_growth_plan_sees_basic_inventory_menu(): void
    {
        $data = $this->createTenantWithPlan('growth');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // Growth has inventory_basic = true, should see inventory routes
        $response->assertSee('href="'.route('inventory.items.index').'"', false);
        $response->assertSee('href="'.route('inventory.stocks.index').'"', false);
    }

    /**
     * Test Growth plan should NOT see advanced inventory menu items.
     */
    public function test_growth_plan_does_not_see_advanced_inventory_menu(): void
    {
        $data = $this->createTenantWithPlan('growth');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // Growth has inventory_advanced = false
        // Should NOT see PO, Goods Receive, Stock Batches
        $response->assertDontSee('href="'.route('inventory.purchase-orders.index').'"', false);
        $response->assertDontSee('href="'.route('inventory.goods-receives.index').'"', false);
        $response->assertDontSee('href="'.route('inventory.batches.index').'"', false);
    }

    /**
     * Test Growth plan should NOT see recipe menu.
     */
    public function test_growth_plan_does_not_see_recipe_menu(): void
    {
        $data = $this->createTenantWithPlan('growth');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // Growth has recipe_bom = false
        $response->assertDontSee('href="'.route('inventory.recipes.index').'"', false);
    }

    /**
     * Test Growth plan should NOT see stock transfer menu.
     */
    public function test_growth_plan_does_not_see_stock_transfer_menu(): void
    {
        $data = $this->createTenantWithPlan('growth');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // Growth has stock_transfer = false
        $response->assertDontSee('href="'.route('inventory.stock-transfers.index').'"', false);
    }

    /**
     * Test Professional plan SHOULD see all inventory menu items.
     */
    public function test_professional_plan_sees_all_inventory_menu(): void
    {
        $data = $this->createTenantWithPlan('professional');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // Professional has all inventory features - check routes are present
        $response->assertSee('href="'.route('inventory.items.index').'"', false);
        $response->assertSee('href="'.route('inventory.purchase-orders.index').'"', false);
        $response->assertSee('href="'.route('inventory.recipes.index').'"', false);
        $response->assertSee('href="'.route('inventory.stock-transfers.index').'"', false);
    }

    /**
     * Test Professional plan SHOULD see recipe menu.
     */
    public function test_professional_plan_sees_recipe_menu(): void
    {
        $data = $this->createTenantWithPlan('professional');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('href="'.route('inventory.recipes.index').'"', false);
    }

    /**
     * Test Enterprise plan SHOULD see all menu items.
     */
    public function test_enterprise_plan_sees_all_menu(): void
    {
        $data = $this->createTenantWithPlan('enterprise');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('href="'.route('inventory.items.index').'"', false);
        $response->assertSee('href="'.route('inventory.purchase-orders.index').'"', false);
        $response->assertSee('href="'.route('inventory.recipes.index').'"', false);
        $response->assertSee('href="'.route('inventory.stock-transfers.index').'"', false);
    }

    /**
     * Test Starter plan should NOT see Reports section (needs inventory).
     */
    public function test_starter_plan_does_not_see_inventory_reports(): void
    {
        $data = $this->createTenantWithPlan('starter');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // Reports section should be hidden for starter
        $response->assertDontSee('href="'.route('inventory.reports.stock-valuation').'"', false);
        $response->assertDontSee('href="'.route('inventory.reports.stock-movement').'"', false);
    }

    /**
     * Test Growth plan SHOULD see basic Reports.
     */
    public function test_growth_plan_sees_basic_reports(): void
    {
        $data = $this->createTenantWithPlan('growth');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('href="'.route('inventory.reports.stock-valuation').'"', false);
        $response->assertSee('href="'.route('inventory.reports.stock-movement').'"', false);
    }
}
