<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Outlet $outlet;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = SubscriptionPlan::factory()->professional()->create();
        $this->tenant = Tenant::factory()->create([
            'tax_percentage' => 11.00,
            'tax_enabled' => true,
            'service_charge_percentage' => 5.00,
            'service_charge_enabled' => true,
        ]);

        Subscription::factory()
            ->active()
            ->forTenant($this->tenant)
            ->withPlan($plan)
            ->create();

        $this->outlet = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_percentage' => null,
            'tax_enabled' => null,
            'service_charge_percentage' => null,
            'service_charge_enabled' => null,
        ]);

        $role = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'slug' => 'cashier',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        $this->user->roles()->attach($role);
        $this->user->outlets()->attach($this->outlet, ['is_default' => true]);

        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'base_price' => 100000,
        ]);
    }

    // ==================== TENANT TAX ENABLED TESTS ====================

    public function test_tenant_tax_enabled_field_exists(): void
    {
        $tenant = Tenant::factory()->create(['tax_enabled' => true]);

        $this->assertTrue($tenant->tax_enabled);
    }

    public function test_tenant_tax_disabled_returns_zero_tax(): void
    {
        $this->tenant->update(['tax_enabled' => false]);
        $this->outlet->update(['tax_enabled' => null]); // Inherit from tenant

        $effectiveTax = $this->outlet->getEffectiveTaxPercentage();

        $this->assertEquals(0, $effectiveTax);
    }

    public function test_tenant_tax_enabled_returns_tax_percentage(): void
    {
        $this->tenant->update(['tax_enabled' => true, 'tax_percentage' => 11.00]);

        $effectiveTax = $this->outlet->getEffectiveTaxPercentage();

        $this->assertEquals(11.00, $effectiveTax);
    }

    // ==================== OUTLET TAX OVERRIDE TESTS ====================

    public function test_outlet_can_override_tenant_tax_enabled(): void
    {
        $this->tenant->update(['tax_enabled' => true, 'tax_percentage' => 11.00]);
        $this->outlet->update(['tax_enabled' => false]); // Outlet disables tax

        $effectiveTax = $this->outlet->getEffectiveTaxPercentage();

        $this->assertEquals(0, $effectiveTax);
    }

    public function test_outlet_can_enable_tax_when_tenant_disabled(): void
    {
        $this->tenant->update(['tax_enabled' => false]);
        $this->outlet->update(['tax_enabled' => true, 'tax_percentage' => 10.00]);

        $effectiveTax = $this->outlet->getEffectiveTaxPercentage();

        $this->assertEquals(10.00, $effectiveTax);
    }

    public function test_outlet_inherits_tenant_tax_settings_when_null(): void
    {
        $this->tenant->update(['tax_enabled' => true, 'tax_percentage' => 11.00]);
        $this->outlet->update(['tax_enabled' => null, 'tax_percentage' => null]);

        $effectiveTax = $this->outlet->getEffectiveTaxPercentage();

        $this->assertEquals(11.00, $effectiveTax);
    }

    public function test_outlet_can_have_different_tax_percentage(): void
    {
        $this->tenant->update(['tax_enabled' => true, 'tax_percentage' => 11.00]);
        $this->outlet->update(['tax_enabled' => true, 'tax_percentage' => 5.00]);

        $effectiveTax = $this->outlet->getEffectiveTaxPercentage();

        $this->assertEquals(5.00, $effectiveTax);
    }

    // ==================== SERVICE CHARGE TESTS ====================

    public function test_tenant_service_charge_enabled_field_exists(): void
    {
        $tenant = Tenant::factory()->create(['service_charge_enabled' => true]);

        $this->assertTrue($tenant->service_charge_enabled);
    }

    public function test_service_charge_disabled_returns_zero(): void
    {
        $this->tenant->update(['service_charge_enabled' => false]);
        $this->outlet->update(['service_charge_enabled' => null]);

        $effectiveServiceCharge = $this->outlet->getEffectiveServiceChargePercentage();

        $this->assertEquals(0, $effectiveServiceCharge);
    }

    public function test_outlet_can_override_service_charge_enabled(): void
    {
        $this->tenant->update(['service_charge_enabled' => true, 'service_charge_percentage' => 5.00]);
        $this->outlet->update(['service_charge_enabled' => false]);

        $effectiveServiceCharge = $this->outlet->getEffectiveServiceChargePercentage();

        $this->assertEquals(0, $effectiveServiceCharge);
    }

    // ==================== TRANSACTION CALCULATION TESTS ====================

    public function test_transaction_calculation_applies_tax_when_enabled(): void
    {
        $this->tenant->update(['tax_enabled' => true, 'tax_percentage' => 10.00]);
        $this->outlet->update(['tax_enabled' => null, 'tax_percentage' => null]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        $this->assertEquals(10.00, $result['tax_percentage']);
        $this->assertEquals(10000, $result['tax_amount']); // 10% of 100000
    }

    public function test_transaction_calculation_zero_tax_when_disabled(): void
    {
        $this->tenant->update(['tax_enabled' => false, 'tax_percentage' => 10.00]);
        $this->outlet->update(['tax_enabled' => null]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        $this->assertEquals(0, $result['tax_percentage']);
        $this->assertEquals(0, $result['tax_amount']);
    }

    public function test_transaction_calculation_uses_outlet_tax_override(): void
    {
        $this->tenant->update(['tax_enabled' => true, 'tax_percentage' => 11.00]);
        $this->outlet->update(['tax_enabled' => true, 'tax_percentage' => 5.00]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        $this->assertEquals(5.00, $result['tax_percentage']);
        $this->assertEquals(5000, $result['tax_amount']); // 5% of 100000
    }

    public function test_transaction_grand_total_correct_with_tax_and_service_charge(): void
    {
        $this->tenant->update([
            'tax_enabled' => true,
            'tax_percentage' => 10.00,
            'service_charge_enabled' => true,
            'service_charge_percentage' => 5.00,
        ]);
        $this->outlet->update([
            'tax_enabled' => null,
            'tax_percentage' => null,
            'service_charge_enabled' => null,
            'service_charge_percentage' => null,
        ]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        // Subtotal: 100000
        // Tax: 10% = 10000
        // Service Charge: 5% = 5000
        // Grand Total: 115000
        $this->assertEquals(100000, $result['subtotal']);
        $this->assertEquals(10000, $result['tax_amount']);
        $this->assertEquals(5000, $result['service_charge_amount']);
        $this->assertEquals(115000, $result['grand_total']);
    }

    public function test_transaction_grand_total_without_tax_and_service_charge(): void
    {
        $this->tenant->update([
            'tax_enabled' => false,
            'service_charge_enabled' => false,
        ]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        // Subtotal: 100000, no tax, no service charge
        $this->assertEquals(100000, $result['subtotal']);
        $this->assertEquals(0, $result['tax_amount']);
        $this->assertEquals(0, $result['service_charge_amount']);
        $this->assertEquals(100000, $result['grand_total']);
    }

    // ==================== UI/FORM TESTS ====================

    public function test_tenant_owner_can_update_tax_settings(): void
    {
        $ownerRole = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'slug' => 'tenant-owner',
        ]);
        $owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        $owner->roles()->attach($ownerRole);

        // This test assumes there's a settings endpoint - adjust route as needed
        $response = $this->actingAs($owner)->patch(route('admin.outlets.update', $this->outlet), [
            'name' => $this->outlet->name,
            'code' => $this->outlet->code,
            'tax_enabled' => false,
            'tax_percentage' => 10.00,
            'service_charge_enabled' => true,
            'service_charge_percentage' => 5.00,
        ]);

        $response->assertRedirect();

        $this->outlet->refresh();
        $this->assertFalse($this->outlet->tax_enabled);
        $this->assertEquals(10.00, $this->outlet->getRawOriginal('tax_percentage'));
    }

    // ==================== CHECKBOX UNCHECKED STATE TESTS ====================

    public function test_unchecked_tax_checkbox_saves_as_false(): void
    {
        $ownerRole = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'slug' => 'tenant-owner',
        ]);
        $owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        $owner->roles()->attach($ownerRole);

        // First, enable tax
        $this->outlet->update(['tax_enabled' => true]);
        $this->assertTrue($this->outlet->fresh()->tax_enabled);

        // Now submit form WITHOUT tax_enabled (simulating unchecked checkbox)
        // HTML checkboxes don't send any value when unchecked
        $response = $this->actingAs($owner)->put(route('admin.outlets.update', $this->outlet), [
            'name' => $this->outlet->name,
            'code' => $this->outlet->code,
            // tax_enabled is NOT included (unchecked checkbox)
            // service_charge_enabled is NOT included (unchecked checkbox)
        ]);

        $response->assertRedirect();

        $this->outlet->refresh();
        $this->assertFalse($this->outlet->tax_enabled);
        $this->assertFalse($this->outlet->service_charge_enabled);
    }

    public function test_unchecked_service_charge_checkbox_saves_as_false(): void
    {
        $ownerRole = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'slug' => 'tenant-owner',
        ]);
        $owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        $owner->roles()->attach($ownerRole);

        // First, enable service charge
        $this->outlet->update(['service_charge_enabled' => true]);
        $this->assertTrue($this->outlet->fresh()->service_charge_enabled);

        // Now submit form WITHOUT service_charge_enabled (simulating unchecked checkbox)
        $response = $this->actingAs($owner)->put(route('admin.outlets.update', $this->outlet), [
            'name' => $this->outlet->name,
            'code' => $this->outlet->code,
            // service_charge_enabled is NOT included (unchecked checkbox)
        ]);

        $response->assertRedirect();

        $this->outlet->refresh();
        $this->assertFalse($this->outlet->service_charge_enabled);
    }

    public function test_checked_tax_checkbox_saves_as_true(): void
    {
        $ownerRole = Role::factory()->create([
            'tenant_id' => $this->tenant->id,
            'slug' => 'tenant-owner',
        ]);
        $owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        $owner->roles()->attach($ownerRole);

        // First, disable tax
        $this->outlet->update(['tax_enabled' => false]);
        $this->assertFalse($this->outlet->fresh()->tax_enabled);

        // Now submit form WITH tax_enabled=1 (simulating checked checkbox)
        $response = $this->actingAs($owner)->put(route('admin.outlets.update', $this->outlet), [
            'name' => $this->outlet->name,
            'code' => $this->outlet->code,
            'tax_enabled' => '1', // Checked checkbox sends "1" or "on"
            'service_charge_enabled' => '1',
        ]);

        $response->assertRedirect();

        $this->outlet->refresh();
        $this->assertTrue($this->outlet->tax_enabled);
        $this->assertTrue($this->outlet->service_charge_enabled);
    }

    // ==================== HELPER METHOD TESTS ====================

    public function test_outlet_is_tax_enabled_helper(): void
    {
        $this->tenant->update(['tax_enabled' => true]);
        $this->outlet->update(['tax_enabled' => null]);

        $this->assertTrue($this->outlet->isTaxEnabled());

        $this->outlet->update(['tax_enabled' => false]);
        $this->assertFalse($this->outlet->isTaxEnabled());
    }

    public function test_outlet_is_service_charge_enabled_helper(): void
    {
        $this->tenant->update(['service_charge_enabled' => true]);
        $this->outlet->update(['service_charge_enabled' => null]);

        $this->assertTrue($this->outlet->isServiceChargeEnabled());

        $this->outlet->update(['service_charge_enabled' => false]);
        $this->assertFalse($this->outlet->isServiceChargeEnabled());
    }
}
