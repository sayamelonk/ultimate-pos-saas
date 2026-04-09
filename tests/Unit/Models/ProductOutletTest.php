<?php

namespace Tests\Unit\Models;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductOutlet;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOutletTest extends TestCase
{
    use RefreshDatabase;

    // ==================== CREATION TESTS ====================

    public function test_can_create_product_outlet(): void
    {
        $tenant = Tenant::factory()->create();
        $product = Product::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $productOutlet = ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
        ]);

        $this->assertDatabaseHas('product_outlets', [
            'id' => $productOutlet->id,
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
        ]);
    }

    public function test_product_outlet_has_required_attributes(): void
    {
        $productOutlet = ProductOutlet::factory()->create();

        $this->assertNotNull($productOutlet->id);
        $this->assertNotNull($productOutlet->product_id);
        $this->assertNotNull($productOutlet->outlet_id);
        $this->assertNotNull($productOutlet->is_available);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_product_outlet_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $productOutlet = ProductOutlet::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $productOutlet->product);
        $this->assertEquals($product->id, $productOutlet->product->id);
    }

    public function test_product_outlet_belongs_to_outlet(): void
    {
        $outlet = Outlet::factory()->create();
        $productOutlet = ProductOutlet::factory()->create(['outlet_id' => $outlet->id]);

        $this->assertInstanceOf(Outlet::class, $productOutlet->outlet);
        $this->assertEquals($outlet->id, $productOutlet->outlet->id);
    }

    // ==================== EFFECTIVE PRICE TESTS ====================

    public function test_effective_price_returns_custom_price_when_set(): void
    {
        $product = Product::factory()->create(['base_price' => 25000]);
        $productOutlet = ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'custom_price' => 30000,
        ]);

        $this->assertEquals(30000, $productOutlet->effectivePrice);
    }

    public function test_effective_price_returns_base_price_when_custom_price_is_null(): void
    {
        $product = Product::factory()->create(['base_price' => 25000]);
        $productOutlet = ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'custom_price' => null,
        ]);

        $this->assertEquals(25000, $productOutlet->effectivePrice);
    }

    public function test_effective_price_returns_custom_price_even_when_lower_than_base(): void
    {
        $product = Product::factory()->create(['base_price' => 25000]);
        $productOutlet = ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'custom_price' => 20000,
        ]);

        $this->assertEquals(20000, $productOutlet->effectivePrice);
    }

    public function test_effective_price_returns_custom_price_when_zero(): void
    {
        $product = Product::factory()->create(['base_price' => 25000]);
        $productOutlet = ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'custom_price' => 0,
        ]);

        // 0 is falsy but not null, so it should NOT be returned
        // Actually in PHP, 0 ?? 25000 returns 0 because ?? checks for null only
        $this->assertEquals(0, $productOutlet->effectivePrice);
    }

    // ==================== AVAILABILITY TESTS ====================

    public function test_product_outlet_is_available_by_default(): void
    {
        $productOutlet = ProductOutlet::factory()->create();
        $this->assertTrue($productOutlet->is_available);
    }

    public function test_product_outlet_can_be_unavailable(): void
    {
        $productOutlet = ProductOutlet::factory()->unavailable()->create();
        $this->assertFalse($productOutlet->is_available);
    }

    // ==================== FEATURED TESTS ====================

    public function test_product_outlet_is_not_featured_by_default(): void
    {
        $productOutlet = ProductOutlet::factory()->create();
        $this->assertFalse($productOutlet->is_featured);
    }

    public function test_product_outlet_can_be_featured(): void
    {
        $productOutlet = ProductOutlet::factory()->featured()->create();
        $this->assertTrue($productOutlet->is_featured);
    }

    // ==================== CASTING TESTS ====================

    public function test_is_available_is_cast_to_boolean(): void
    {
        $productOutlet = ProductOutlet::factory()->create(['is_available' => 1]);
        $this->assertIsBool($productOutlet->is_available);
        $this->assertTrue($productOutlet->is_available);
    }

    public function test_custom_price_is_cast_to_decimal(): void
    {
        $productOutlet = ProductOutlet::factory()->create(['custom_price' => 25000.50]);
        $this->assertEquals('25000.50', $productOutlet->custom_price);
    }

    public function test_is_featured_is_cast_to_boolean(): void
    {
        $productOutlet = ProductOutlet::factory()->create(['is_featured' => 1]);
        $this->assertIsBool($productOutlet->is_featured);
        $this->assertTrue($productOutlet->is_featured);
    }

    public function test_sort_order_is_cast_to_integer(): void
    {
        $productOutlet = ProductOutlet::factory()->create(['sort_order' => '5']);
        $this->assertIsInt($productOutlet->sort_order);
        $this->assertEquals(5, $productOutlet->sort_order);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_unavailable_state(): void
    {
        $productOutlet = ProductOutlet::factory()->unavailable()->create();
        $this->assertFalse($productOutlet->is_available);
    }

    public function test_factory_featured_state(): void
    {
        $productOutlet = ProductOutlet::factory()->featured()->create();
        $this->assertTrue($productOutlet->is_featured);
    }

    public function test_factory_with_custom_price_state(): void
    {
        $productOutlet = ProductOutlet::factory()->withCustomPrice(35000)->create();
        $this->assertEquals('35000.00', $productOutlet->custom_price);
    }

    public function test_factory_with_sort_order_state(): void
    {
        $productOutlet = ProductOutlet::factory()->withSortOrder(10)->create();
        $this->assertEquals(10, $productOutlet->sort_order);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_product_outlet_uses_uuid(): void
    {
        $productOutlet = ProductOutlet::factory()->create();
        $this->assertNotNull($productOutlet->id);
        $this->assertIsString($productOutlet->id);
        // UUID format check
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $productOutlet->id
        );
    }

    // ==================== UNIQUE CONSTRAINT TESTS ====================

    public function test_same_product_can_be_in_multiple_outlets(): void
    {
        $tenant = Tenant::factory()->create();
        $product = Product::factory()->create(['tenant_id' => $tenant->id]);
        $outlet1 = Outlet::factory()->create(['tenant_id' => $tenant->id]);
        $outlet2 = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet1->id,
        ]);
        ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet2->id,
        ]);

        $this->assertCount(2, ProductOutlet::where('product_id', $product->id)->get());
    }

    public function test_same_outlet_can_have_multiple_products(): void
    {
        $tenant = Tenant::factory()->create();
        $product1 = Product::factory()->create(['tenant_id' => $tenant->id]);
        $product2 = Product::factory()->create(['tenant_id' => $tenant->id]);
        $outlet = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        ProductOutlet::factory()->create([
            'product_id' => $product1->id,
            'outlet_id' => $outlet->id,
        ]);
        ProductOutlet::factory()->create([
            'product_id' => $product2->id,
            'outlet_id' => $outlet->id,
        ]);

        $this->assertCount(2, ProductOutlet::where('outlet_id', $outlet->id)->get());
    }

    // ==================== CUSTOM PRICE SCENARIOS ====================

    public function test_different_outlets_can_have_different_custom_prices(): void
    {
        $tenant = Tenant::factory()->create();
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'base_price' => 25000,
        ]);
        $outlet1 = Outlet::factory()->create(['tenant_id' => $tenant->id]);
        $outlet2 = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $productOutlet1 = ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet1->id,
            'custom_price' => 27000,
        ]);
        $productOutlet2 = ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet2->id,
            'custom_price' => 23000,
        ]);

        $this->assertEquals(27000, $productOutlet1->effectivePrice);
        $this->assertEquals(23000, $productOutlet2->effectivePrice);
    }

    public function test_one_outlet_with_custom_price_other_without(): void
    {
        $tenant = Tenant::factory()->create();
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'base_price' => 25000,
        ]);
        $outlet1 = Outlet::factory()->create(['tenant_id' => $tenant->id]);
        $outlet2 = Outlet::factory()->create(['tenant_id' => $tenant->id]);

        $productOutlet1 = ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet1->id,
            'custom_price' => 30000,
        ]);
        $productOutlet2 = ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet2->id,
            'custom_price' => null,
        ]);

        $this->assertEquals(30000, $productOutlet1->effectivePrice);
        $this->assertEquals(25000, $productOutlet2->effectivePrice);
    }
}
