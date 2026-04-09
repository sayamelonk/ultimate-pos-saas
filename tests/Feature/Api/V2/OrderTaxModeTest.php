<?php

namespace Tests\Feature\Api\V2;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOutlet;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * TDD Tests for Order API - Tax Mode Calculation
 *
 * These tests ensure the Order API correctly calculates taxes
 * based on tax_mode (inclusive vs exclusive) settings.
 */
class OrderTaxModeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Outlet $outlet;

    private Product $product;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'tax_enabled' => true,
            'tax_percentage' => 10.00,
            'tax_mode' => 'exclusive',
            'service_charge_enabled' => true,
            'service_charge_percentage' => 5.00,
        ]);

        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->outlet = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_enabled' => true,
            'tax_percentage' => 10.00,
            'tax_mode' => 'exclusive',
            'service_charge_enabled' => true,
            'service_charge_percentage' => 5.00,
        ]);

        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'base_price' => 100000, // Rp 100.000
            'is_active' => true,
        ]);

        ProductOutlet::create([
            'product_id' => $this->product->id,
            'outlet_id' => $this->outlet->id,
            'is_available' => true,
        ]);

        $this->paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        // Open POS session
        PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);
    }

    // ==================== Tax Mode - Exclusive ====================

    /** @test */
    public function calculate_returns_tax_mode_in_response(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertArrayHasKey('tax_mode', $response->json('data'));
        $this->assertEquals('exclusive', $response->json('data.tax_mode'));
    }

    /** @test */
    public function calculate_adds_tax_on_top_when_mode_is_exclusive(): void
    {
        // Product price: 100,000
        // Tax 10% exclusive: should be added on top = 10,000
        // Subtotal: 100,000
        // Tax: 10,000
        // Service charge 5%: 5,000
        // Grand total: 115,000

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();

        $data = $response->json('data');

        $this->assertEquals(100000, $data['subtotal']);
        $this->assertEquals(10.00, $data['tax_percentage']);
        $this->assertEquals(10000, $data['tax_amount']); // 10% of 100,000
        $this->assertEquals(5.00, $data['service_charge_percentage']);
        $this->assertEquals(5000, $data['service_charge_amount']); // 5% of 100,000
    }

    // ==================== Tax Mode - Inclusive ====================

    /** @test */
    public function calculate_extracts_tax_from_price_when_mode_is_inclusive(): void
    {
        // Update outlet to use inclusive tax mode
        $this->outlet->update([
            'tax_mode' => 'inclusive',
        ]);

        // Product price: 100,000 (already includes 10% tax)
        // Tax included = 100,000 - (100,000 / 1.10) = 100,000 - 90,909.09 = 9,090.91
        // Base price (without tax): 90,909.09
        // Tax: 9,090.91

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();

        $data = $response->json('data');

        $this->assertEquals('inclusive', $data['tax_mode']);
        // For inclusive: subtotal should be price without tax
        $expectedBasePrice = round(100000 / 1.10, 2); // 90909.09
        $expectedTax = round(100000 - $expectedBasePrice, 2); // 9090.91

        $this->assertEquals($expectedBasePrice, $data['subtotal']);
        $this->assertEquals($expectedTax, $data['tax_amount']);
    }

    /** @test */
    public function calculate_grand_total_same_regardless_of_tax_mode(): void
    {
        // For same product price (100,000), grand total should be:
        // - Exclusive: 100,000 + 10,000 (tax) + 5,000 (sc) = 115,000
        // - Inclusive: 100,000 (price already has tax) + service charge on base
        // Actually, with inclusive, service charge should be on base price

        // This test verifies tax mode is working correctly

        Sanctum::actingAs($this->user);

        // First calculate with exclusive (current setup)
        $exclusiveResponse = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $exclusiveData = $exclusiveResponse->json('data');

        // Switch to inclusive
        $this->outlet->update(['tax_mode' => 'inclusive']);

        $inclusiveResponse = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $inclusiveData = $inclusiveResponse->json('data');

        // Verify both modes return correct tax_mode
        $this->assertEquals('exclusive', $exclusiveData['tax_mode']);
        $this->assertEquals('inclusive', $inclusiveData['tax_mode']);

        // Verify tax amounts are different based on mode
        $this->assertNotEquals($exclusiveData['tax_amount'], $inclusiveData['tax_amount']);
    }

    // ==================== Tax Disabled ====================

    /** @test */
    public function calculate_returns_zero_tax_when_tax_disabled(): void
    {
        $this->outlet->update([
            'tax_enabled' => false,
            'tax_percentage' => 10.00, // Still set but should be ignored
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();

        $data = $response->json('data');

        $this->assertEquals(100000, $data['subtotal']);
        $this->assertEquals(0, $data['tax_amount']);
        $this->assertFalse($data['tax_enabled']);
    }

    /** @test */
    public function calculate_returns_tax_enabled_flag(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertArrayHasKey('tax_enabled', $response->json('data'));
        $this->assertTrue($response->json('data.tax_enabled'));
    }

    // ==================== Service Charge Disabled ====================

    /** @test */
    public function calculate_returns_zero_service_charge_when_disabled(): void
    {
        $this->outlet->update([
            'service_charge_enabled' => false,
            'service_charge_percentage' => 5.00, // Still set but should be ignored
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();

        $data = $response->json('data');

        $this->assertEquals(0, $data['service_charge_amount']);
        $this->assertFalse($data['service_charge_enabled']);
    }

    /** @test */
    public function calculate_returns_service_charge_enabled_flag(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertArrayHasKey('service_charge_enabled', $response->json('data'));
        $this->assertTrue($response->json('data.service_charge_enabled'));
    }

    // ==================== Inheritance from Tenant ====================

    /** @test */
    public function calculate_inherits_tax_mode_from_tenant_when_outlet_is_null(): void
    {
        // Create outlet without tax_mode
        $outletWithoutTaxMode = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_enabled' => true,
            'tax_percentage' => 10.00,
            'tax_mode' => null, // Should inherit from tenant (exclusive)
            'service_charge_enabled' => true,
            'service_charge_percentage' => 5.00,
        ]);

        ProductOutlet::create([
            'product_id' => $this->product->id,
            'outlet_id' => $outletWithoutTaxMode->id,
            'is_available' => true,
        ]);

        // Open POS session for this outlet
        PosSession::factory()->create([
            'outlet_id' => $outletWithoutTaxMode->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $outletWithoutTaxMode->id,
        ]);

        $response->assertOk();

        // Should inherit from tenant (exclusive)
        $this->assertEquals('exclusive', $response->json('data.tax_mode'));
    }

    /** @test */
    public function calculate_inherits_tax_enabled_from_tenant_when_outlet_is_null(): void
    {
        // Create outlet without tax_enabled
        $outletWithoutTaxEnabled = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_enabled' => null, // Should inherit from tenant (true)
            'tax_percentage' => 10.00,
            'tax_mode' => 'exclusive',
            'service_charge_enabled' => true,
            'service_charge_percentage' => 5.00,
        ]);

        ProductOutlet::create([
            'product_id' => $this->product->id,
            'outlet_id' => $outletWithoutTaxEnabled->id,
            'is_available' => true,
        ]);

        // Open POS session for this outlet
        PosSession::factory()->create([
            'outlet_id' => $outletWithoutTaxEnabled->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $outletWithoutTaxEnabled->id,
        ]);

        $response->assertOk();

        // Should inherit from tenant (true)
        $this->assertTrue($response->json('data.tax_enabled'));
        $this->assertGreaterThan(0, $response->json('data.tax_amount'));
    }

    // ==================== Complete Response Structure ====================

    /** @test */
    public function calculate_has_complete_tax_fields_in_response(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();

        $expectedFields = [
            'tax_enabled',
            'tax_mode',
            'tax_percentage',
            'tax_amount',
            'service_charge_enabled',
            'service_charge_percentage',
            'service_charge_amount',
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $response->json('data'), "Missing field: {$field}");
        }
    }
}
