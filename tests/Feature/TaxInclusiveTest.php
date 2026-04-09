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

/**
 * Test cases for Tax Inclusive feature
 *
 * Tax Mode:
 * - 'exclusive': Tax is added on top of price (default)
 *   Example: Price Rp100,000 + Tax 10% = Rp110,000
 *
 * - 'inclusive': Tax is already included in price
 *   Example: Price Rp100,000 (includes Tax 10%)
 *   - Tax Amount: Rp100,000 × (10 / 110) = Rp9,090.91
 *   - Base Price: Rp100,000 - Rp9,090.91 = Rp90,909.09
 *
 * Business Rules:
 * - Discount applied to gross price (before tax extraction)
 * - Service charge calculated from gross price
 */
class TaxInclusiveTest extends TestCase
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
            'tax_percentage' => 10.00,
            'tax_enabled' => true,
            'tax_mode' => 'exclusive', // Default mode
            'service_charge_percentage' => 5.00,
            'service_charge_enabled' => false,
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
            'tax_mode' => null, // Inherit from tenant
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

        // Product with price Rp100,000
        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'base_price' => 100000,
        ]);
    }

    // ==================== TAX MODE FIELD TESTS ====================

    public function test_tenant_has_tax_mode_field(): void
    {
        $tenant = Tenant::factory()->create(['tax_mode' => 'exclusive']);
        $this->assertEquals('exclusive', $tenant->tax_mode);

        $tenant2 = Tenant::factory()->create(['tax_mode' => 'inclusive']);
        $this->assertEquals('inclusive', $tenant2->tax_mode);
    }

    public function test_tenant_tax_mode_defaults_to_exclusive(): void
    {
        $tenant = Tenant::factory()->create();
        $this->assertEquals('exclusive', $tenant->tax_mode);
    }

    public function test_outlet_can_override_tenant_tax_mode(): void
    {
        $this->tenant->update(['tax_mode' => 'exclusive']);
        $this->outlet->update(['tax_mode' => 'inclusive']);

        $this->assertEquals('inclusive', $this->outlet->getTaxMode());
    }

    public function test_outlet_inherits_tenant_tax_mode_when_null(): void
    {
        $this->tenant->update(['tax_mode' => 'inclusive']);
        $this->outlet->update(['tax_mode' => null]);

        $this->assertEquals('inclusive', $this->outlet->getTaxMode());
    }

    public function test_outlet_is_tax_inclusive_helper(): void
    {
        $this->tenant->update(['tax_mode' => 'exclusive']);
        $this->outlet->update(['tax_mode' => null]);
        $this->assertFalse($this->outlet->isTaxInclusive());

        $this->outlet->update(['tax_mode' => 'inclusive']);
        $this->assertTrue($this->outlet->isTaxInclusive());
    }

    // ==================== EXCLUSIVE TAX CALCULATION TESTS ====================

    public function test_exclusive_tax_calculation(): void
    {
        // Setup: Exclusive mode with 10% tax
        $this->tenant->update(['tax_mode' => 'exclusive', 'tax_percentage' => 10.00]);
        $this->outlet->update(['tax_mode' => null]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        // Exclusive: Price Rp100,000 + Tax 10% = Rp110,000
        $this->assertEquals(100000, $result['subtotal']);
        $this->assertEquals(10000, $result['tax_amount']); // 10% of 100,000
        $this->assertEquals(10.00, $result['tax_percentage']);
        $this->assertEquals('exclusive', $result['tax_mode']);
        $this->assertEquals(110000, $result['grand_total']);
    }

    public function test_exclusive_tax_with_multiple_items(): void
    {
        $this->tenant->update(['tax_mode' => 'exclusive', 'tax_percentage' => 10.00]);

        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'base_price' => 50000,
        ]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [
                ['product_id' => $this->product->id, 'quantity' => 2], // 200,000
                ['product_id' => $product2->id, 'quantity' => 1],      // 50,000
            ],
        );

        // Subtotal: 250,000 + Tax 10% = 275,000
        $this->assertEquals(250000, $result['subtotal']);
        $this->assertEquals(25000, $result['tax_amount']);
        $this->assertEquals(275000, $result['grand_total']);
    }

    // ==================== INCLUSIVE TAX CALCULATION TESTS ====================

    public function test_inclusive_tax_calculation(): void
    {
        // Setup: Inclusive mode with 10% tax
        $this->tenant->update(['tax_mode' => 'inclusive', 'tax_percentage' => 10.00]);
        $this->outlet->update(['tax_mode' => null]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        // Inclusive: Price Rp100,000 already includes 10% tax
        // Tax = 100,000 × (10 / 110) = 9,090.91 (rounded to 9091)
        // Subtotal (before tax) = 100,000 - 9,091 = 90,909
        $this->assertEquals(100000, $result['subtotal']); // Gross amount shown as subtotal
        $this->assertEqualsWithDelta(9090.91, $result['tax_amount'], 1); // Allow small rounding difference
        $this->assertEquals(10.00, $result['tax_percentage']);
        $this->assertEquals('inclusive', $result['tax_mode']);
        $this->assertEquals(100000, $result['grand_total']); // Same as subtotal in inclusive
    }

    public function test_inclusive_tax_with_11_percent(): void
    {
        // Common Indonesian PPN rate
        $this->tenant->update(['tax_mode' => 'inclusive', 'tax_percentage' => 11.00]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        // Inclusive: Price Rp100,000 already includes 11% tax
        // Tax = 100,000 × (11 / 111) = 9,909.91
        $this->assertEquals(100000, $result['subtotal']);
        $this->assertEqualsWithDelta(9909.91, $result['tax_amount'], 1);
        $this->assertEquals(100000, $result['grand_total']);
    }

    public function test_inclusive_tax_with_multiple_items(): void
    {
        $this->tenant->update(['tax_mode' => 'inclusive', 'tax_percentage' => 10.00]);

        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'base_price' => 50000,
        ]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [
                ['product_id' => $this->product->id, 'quantity' => 2], // 200,000
                ['product_id' => $product2->id, 'quantity' => 1],      // 50,000
            ],
        );

        // Gross: 250,000 (includes 10% tax)
        // Tax = 250,000 × (10 / 110) = 22,727.27
        $this->assertEquals(250000, $result['subtotal']);
        $this->assertEqualsWithDelta(22727.27, $result['tax_amount'], 1);
        $this->assertEquals(250000, $result['grand_total']);
    }

    // ==================== DISCOUNT WITH TAX MODE TESTS ====================

    public function test_exclusive_tax_with_discount(): void
    {
        $this->tenant->update(['tax_mode' => 'exclusive', 'tax_percentage' => 10.00]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
            null,
            [['type' => 'percentage', 'value' => 10]], // 10% discount
        );

        // Subtotal: 100,000
        // Discount: 10% = 10,000
        // After discount: 90,000
        // Tax: 10% of 90,000 = 9,000
        // Grand Total: 99,000
        $this->assertEquals(100000, $result['subtotal']);
        $this->assertEquals(10000, $result['discount_amount']);
        $this->assertEquals(9000, $result['tax_amount']);
        $this->assertEquals(99000, $result['grand_total']);
    }

    public function test_inclusive_tax_with_discount(): void
    {
        // Business rule: Discount applied to gross price (before tax extraction)
        $this->tenant->update(['tax_mode' => 'inclusive', 'tax_percentage' => 10.00]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
            null,
            [['type' => 'percentage', 'value' => 10]], // 10% discount
        );

        // Gross: 100,000 (includes tax)
        // Discount: 10% of gross = 10,000
        // After discount (gross): 90,000
        // Tax in 90,000 = 90,000 × (10 / 110) = 8,181.82
        // Grand Total: 90,000 (customer pays this)
        $this->assertEquals(100000, $result['subtotal']);
        $this->assertEquals(10000, $result['discount_amount']);
        $this->assertEqualsWithDelta(8181.82, $result['tax_amount'], 1);
        $this->assertEquals(90000, $result['grand_total']);
    }

    public function test_inclusive_tax_with_fixed_discount(): void
    {
        $this->tenant->update(['tax_mode' => 'inclusive', 'tax_percentage' => 10.00]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
            null,
            [['type' => 'fixed', 'value' => 20000]], // Rp20,000 discount
        );

        // Gross: 100,000
        // Discount: 20,000
        // After discount: 80,000
        // Tax in 80,000 = 80,000 × (10 / 110) = 7,272.73
        // Grand Total: 80,000
        $this->assertEquals(100000, $result['subtotal']);
        $this->assertEquals(20000, $result['discount_amount']);
        $this->assertEqualsWithDelta(7272.73, $result['tax_amount'], 1);
        $this->assertEquals(80000, $result['grand_total']);
    }

    // ==================== SERVICE CHARGE WITH TAX MODE TESTS ====================

    public function test_exclusive_tax_with_service_charge(): void
    {
        $this->tenant->update([
            'tax_mode' => 'exclusive',
            'tax_percentage' => 10.00,
            'service_charge_enabled' => true,
            'service_charge_percentage' => 5.00,
        ]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        // Subtotal: 100,000
        // Tax: 10% = 10,000
        // Service Charge: 5% of 100,000 = 5,000
        // Grand Total: 115,000
        $this->assertEquals(100000, $result['subtotal']);
        $this->assertEquals(10000, $result['tax_amount']);
        $this->assertEquals(5000, $result['service_charge_amount']);
        $this->assertEquals(115000, $result['grand_total']);
    }

    public function test_inclusive_tax_with_service_charge(): void
    {
        // Business rule: Service charge calculated from gross price
        $this->tenant->update([
            'tax_mode' => 'inclusive',
            'tax_percentage' => 10.00,
            'service_charge_enabled' => true,
            'service_charge_percentage' => 5.00,
        ]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        // Gross: 100,000 (includes tax)
        // Tax in gross: 100,000 × (10 / 110) = 9,090.91
        // Service Charge: 5% of gross (100,000) = 5,000
        // Grand Total: 100,000 + 5,000 = 105,000
        $this->assertEquals(100000, $result['subtotal']);
        $this->assertEqualsWithDelta(9090.91, $result['tax_amount'], 1);
        $this->assertEquals(5000, $result['service_charge_amount']);
        $this->assertEquals(105000, $result['grand_total']);
    }

    public function test_inclusive_tax_with_discount_and_service_charge(): void
    {
        $this->tenant->update([
            'tax_mode' => 'inclusive',
            'tax_percentage' => 10.00,
            'service_charge_enabled' => true,
            'service_charge_percentage' => 5.00,
        ]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
            null,
            [['type' => 'percentage', 'value' => 10]], // 10% discount
        );

        // Gross: 100,000
        // Discount: 10% = 10,000
        // After discount: 90,000
        // Tax in 90,000: 90,000 × (10 / 110) = 8,181.82
        // Service Charge: 5% of 90,000 = 4,500
        // Grand Total: 90,000 + 4,500 = 94,500
        $this->assertEquals(100000, $result['subtotal']);
        $this->assertEquals(10000, $result['discount_amount']);
        $this->assertEqualsWithDelta(8181.82, $result['tax_amount'], 1);
        $this->assertEquals(4500, $result['service_charge_amount']);
        $this->assertEquals(94500, $result['grand_total']);
    }

    // ==================== TAX DISABLED TESTS ====================

    public function test_inclusive_mode_with_tax_disabled(): void
    {
        $this->tenant->update([
            'tax_mode' => 'inclusive',
            'tax_enabled' => false,
            'tax_percentage' => 10.00,
        ]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        // Tax disabled = no tax calculation regardless of mode
        $this->assertEquals(100000, $result['subtotal']);
        $this->assertEquals(0, $result['tax_amount']);
        $this->assertEquals(100000, $result['grand_total']);
    }

    // ==================== OUTLET OVERRIDE TESTS ====================

    public function test_outlet_can_use_inclusive_while_tenant_uses_exclusive(): void
    {
        $this->tenant->update(['tax_mode' => 'exclusive', 'tax_percentage' => 10.00]);
        $this->outlet->update(['tax_mode' => 'inclusive']);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        // Outlet uses inclusive, so tax is extracted from price
        $this->assertEquals('inclusive', $result['tax_mode']);
        $this->assertEquals(100000, $result['grand_total']); // Price stays same in inclusive
        $this->assertEqualsWithDelta(9090.91, $result['tax_amount'], 1);
    }

    public function test_outlet_can_use_exclusive_while_tenant_uses_inclusive(): void
    {
        $this->tenant->update(['tax_mode' => 'inclusive', 'tax_percentage' => 10.00]);
        $this->outlet->update(['tax_mode' => 'exclusive']);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        // Outlet uses exclusive, so tax is added on top
        $this->assertEquals('exclusive', $result['tax_mode']);
        $this->assertEquals(110000, $result['grand_total']); // 100,000 + 10% tax
        $this->assertEquals(10000, $result['tax_amount']);
    }

    // ==================== API RESPONSE TESTS ====================

    public function test_api_calculation_returns_tax_mode(): void
    {
        $this->tenant->update(['tax_mode' => 'inclusive', 'tax_percentage' => 10.00]);

        $service = app(TransactionService::class);
        $result = $service->calculateTransaction(
            $this->outlet->id,
            [['product_id' => $this->product->id, 'quantity' => 1]],
        );

        $this->assertArrayHasKey('tax_mode', $result);
        $this->assertEquals('inclusive', $result['tax_mode']);
    }

    // ==================== FORM UPDATE TESTS ====================

    public function test_admin_can_update_outlet_tax_mode(): void
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

        $response = $this->actingAs($owner)->put(route('admin.outlets.update', $this->outlet), [
            'name' => $this->outlet->name,
            'code' => $this->outlet->code,
            'tax_mode' => 'inclusive',
        ]);

        $response->assertRedirect();

        $this->outlet->refresh();
        $this->assertEquals('inclusive', $this->outlet->tax_mode);
    }

    public function test_outlet_tax_mode_can_be_set_to_null_for_inheritance(): void
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

        // First set to inclusive
        $this->outlet->update(['tax_mode' => 'inclusive']);

        // Then clear to inherit from tenant
        $response = $this->actingAs($owner)->put(route('admin.outlets.update', $this->outlet), [
            'name' => $this->outlet->name,
            'code' => $this->outlet->code,
            'tax_mode' => '', // Empty = inherit
        ]);

        $response->assertRedirect();

        $this->outlet->refresh();
        $this->assertNull($this->outlet->tax_mode);
    }

    // ==================== ROUNDING TESTS ====================

    public function test_inclusive_tax_rounding_consistency(): void
    {
        $this->tenant->update(['tax_mode' => 'inclusive', 'tax_percentage' => 11.00]);

        // Test with various amounts to ensure consistent rounding
        $amounts = [100000, 75000, 123456, 999999];

        foreach ($amounts as $amount) {
            $product = Product::factory()->create([
                'tenant_id' => $this->tenant->id,
                'base_price' => $amount,
            ]);

            $service = app(TransactionService::class);
            $result = $service->calculateTransaction(
                $this->outlet->id,
                [['product_id' => $product->id, 'quantity' => 1]],
            );

            // In inclusive mode, grand_total should equal subtotal (no additional charges)
            // Tax is extracted, not added
            $this->assertEquals($result['subtotal'], $result['grand_total']);

            // Tax should be calculated as: amount × (rate / (100 + rate))
            $expectedTax = $amount * (11 / 111);
            $this->assertEqualsWithDelta($expectedTax, $result['tax_amount'], 1);
        }
    }
}
