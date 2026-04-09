<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFeatureGatingTest extends TestCase
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
     * Test Starter plan should NOT see inventory widgets on dashboard.
     */
    public function test_starter_plan_does_not_see_inventory_widgets(): void
    {
        $data = $this->createTenantWithPlan('starter');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('Expiring Soon');
        $response->assertDontSee('Low Stock');
        $response->assertDontSee('Items nearing expiry date');
        $response->assertDontSee('Items below reorder point');
    }

    /**
     * Test Starter plan should NOT see inventory reports link in Quick Actions.
     */
    public function test_starter_plan_does_not_see_inventory_reports_link(): void
    {
        $data = $this->createTenantWithPlan('starter');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('route(\'inventory.reports.stock-valuation\')');
    }

    /**
     * Test Growth plan SHOULD see inventory widgets (basic inventory).
     */
    public function test_growth_plan_sees_inventory_widgets(): void
    {
        $data = $this->createTenantWithPlan('growth');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // Growth has inventory_basic = true, should see low stock
        $response->assertSee('Low Stock');
        $response->assertSee('Items below reorder point');
    }

    /**
     * Test Growth plan should NOT see expiring batches (inventory_advanced feature).
     */
    public function test_growth_plan_does_not_see_expiring_batches(): void
    {
        $data = $this->createTenantWithPlan('growth');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // Growth has inventory_advanced = false, should NOT see expiring batches
        $response->assertDontSee('Expiring Soon');
        $response->assertDontSee('Items nearing expiry date');
    }

    /**
     * Test Professional plan SHOULD see all inventory widgets.
     */
    public function test_professional_plan_sees_all_inventory_widgets(): void
    {
        $data = $this->createTenantWithPlan('professional');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // Professional has both inventory_basic and inventory_advanced = true
        $response->assertSee('Expiring Soon');
        $response->assertSee('Low Stock');
    }

    /**
     * Test Enterprise plan SHOULD see all inventory widgets.
     */
    public function test_enterprise_plan_sees_all_inventory_widgets(): void
    {
        $data = $this->createTenantWithPlan('enterprise');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Expiring Soon');
        $response->assertSee('Low Stock');
    }

    /**
     * Test dashboard controller does not query inventory data for Starter plan.
     */
    public function test_starter_plan_controller_does_not_query_inventory_data(): void
    {
        $data = $this->createTenantWithPlan('starter');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // The view data should have empty collections for inventory
        $response->assertViewHas('expiringBatches', function ($batches) {
            return $batches->isEmpty();
        });
        $response->assertViewHas('lowStockItems', function ($items) {
            return $items->isEmpty();
        });
    }

    /**
     * Test Growth plan controller queries only low stock data (inventory_basic).
     */
    public function test_growth_plan_controller_queries_low_stock_data(): void
    {
        $data = $this->createTenantWithPlan('growth');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        // Growth has inventory_basic, should have lowStockItems in view
        $response->assertViewHas('lowStockItems');
        // But expiring batches should be empty (no inventory_advanced)
        $response->assertViewHas('expiringBatches', function ($batches) {
            return $batches->isEmpty();
        });
    }

    /**
     * Test Professional plan controller queries all inventory data.
     */
    public function test_professional_plan_controller_queries_all_inventory_data(): void
    {
        $data = $this->createTenantWithPlan('professional');

        $response = $this->actingAs($data['user'])->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('expiringBatches');
        $response->assertViewHas('lowStockItems');
    }

    /**
     * Test super admin always sees all data regardless of subscription.
     */
    public function test_super_admin_sees_super_admin_dashboard(): void
    {
        $superAdminRole = Role::factory()->create([
            'slug' => 'super-admin',
            'name' => 'Super Admin',
            'is_system' => true,
        ]);

        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'email_verified_at' => now(),
        ]);

        $superAdmin->roles()->attach($superAdminRole);

        $response = $this->actingAs($superAdmin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Total Tenants');
        $response->assertSee('Total Outlets');
        $response->assertSee('Total Users');
    }
}
