<?php

namespace Tests\Unit\Models;

use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComboTest extends TestCase
{
    use RefreshDatabase;

    // ==================== CREATION TESTS ====================

    public function test_can_create_combo(): void
    {
        $product = Product::factory()->combo()->create();
        $combo = Combo::factory()->create(['product_id' => $product->id]);

        $this->assertDatabaseHas('combos', [
            'id' => $combo->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_combo_has_required_attributes(): void
    {
        $combo = Combo::factory()->create();

        $this->assertNotNull($combo->id);
        $this->assertNotNull($combo->product_id);
        $this->assertNotNull($combo->pricing_type);
    }

    // ==================== CONSTANTS TESTS ====================

    public function test_pricing_type_constants_exist(): void
    {
        $this->assertEquals('fixed', Combo::PRICING_FIXED);
        $this->assertEquals('sum', Combo::PRICING_SUM);
        $this->assertEquals('discount_percent', Combo::PRICING_DISCOUNT_PERCENT);
        $this->assertEquals('discount_amount', Combo::PRICING_DISCOUNT_AMOUNT);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_combo_belongs_to_product(): void
    {
        $product = Product::factory()->combo()->create();
        $combo = Combo::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $combo->product);
        $this->assertEquals($product->id, $combo->product->id);
    }

    public function test_combo_has_many_items(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create(['tenant_id' => $tenant->id]);
        $combo = Combo::factory()->create(['product_id' => $comboProduct->id]);

        $item1Product = Product::factory()->create(['tenant_id' => $tenant->id]);
        $item2Product = Product::factory()->create(['tenant_id' => $tenant->id]);

        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $item1Product->id,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $item2Product->id,
        ]);

        $this->assertCount(2, $combo->items);
    }

    public function test_combo_items_are_ordered_by_sort_order(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create(['tenant_id' => $tenant->id]);
        $combo = Combo::factory()->create(['product_id' => $comboProduct->id]);

        $product1 = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Third']);
        $product2 = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'First']);
        $product3 = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Second']);

        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product1->id,
            'sort_order' => 3,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product2->id,
            'sort_order' => 1,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product3->id,
            'sort_order' => 2,
        ]);

        $items = $combo->items;
        $this->assertEquals('First', $items[0]->product->name);
        $this->assertEquals('Second', $items[1]->product->name);
        $this->assertEquals('Third', $items[2]->product->name);
    }

    // ==================== CALCULATE PRICE TESTS ====================

    public function test_calculate_price_fixed_returns_product_base_price(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create([
            'tenant_id' => $tenant->id,
            'base_price' => 50000,
        ]);
        $combo = Combo::factory()->fixed()->create(['product_id' => $comboProduct->id]);

        // Add items (their prices don't matter for fixed pricing)
        $item1 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 20000]);
        $item2 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 25000]);

        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item1->id]);
        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item2->id]);

        $combo->refresh();
        $this->assertEquals(50000.0, $combo->calculatePrice());
    }

    public function test_calculate_price_sum_returns_sum_of_item_prices(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create([
            'tenant_id' => $tenant->id,
            'base_price' => 100000, // This shouldn't matter for SUM
        ]);
        $combo = Combo::factory()->sumPricing()->create(['product_id' => $comboProduct->id]);

        $item1 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 20000]);
        $item2 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 25000]);

        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item1->id, 'quantity' => 1]);
        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item2->id, 'quantity' => 1]);

        $combo->refresh();
        $this->assertEquals(45000.0, $combo->calculatePrice());
    }

    public function test_calculate_price_sum_respects_quantity(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create(['tenant_id' => $tenant->id]);
        $combo = Combo::factory()->sumPricing()->create(['product_id' => $comboProduct->id]);

        $item1 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 10000]);
        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item1->id, 'quantity' => 3]);

        $combo->refresh();
        $this->assertEquals(30000.0, $combo->calculatePrice());
    }

    public function test_calculate_price_discount_percent(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create(['tenant_id' => $tenant->id]);
        $combo = Combo::factory()->discountPercent(10)->create(['product_id' => $comboProduct->id]);

        $item1 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 50000]);
        $item2 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 50000]);

        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item1->id, 'quantity' => 1]);
        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item2->id, 'quantity' => 1]);

        $combo->refresh();
        // Total = 100000, 10% off = 90000
        $this->assertEquals(90000.0, $combo->calculatePrice());
    }

    public function test_calculate_price_discount_amount(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create(['tenant_id' => $tenant->id]);
        $combo = Combo::factory()->discountAmount(15000)->create(['product_id' => $comboProduct->id]);

        $item1 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 50000]);
        $item2 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 50000]);

        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item1->id, 'quantity' => 1]);
        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item2->id, 'quantity' => 1]);

        $combo->refresh();
        // Total = 100000, minus 15000 = 85000
        $this->assertEquals(85000.0, $combo->calculatePrice());
    }

    public function test_calculate_price_discount_amount_never_negative(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create(['tenant_id' => $tenant->id]);
        $combo = Combo::factory()->discountAmount(100000)->create(['product_id' => $comboProduct->id]);

        $item1 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 10000]);
        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item1->id, 'quantity' => 1]);

        $combo->refresh();
        // Total = 10000, minus 100000 = -90000, but should be 0
        $this->assertEquals(0.0, $combo->calculatePrice());
    }

    public function test_calculate_price_returns_0_for_items_without_product(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create(['tenant_id' => $tenant->id]);
        $combo = Combo::factory()->sumPricing()->create(['product_id' => $comboProduct->id]);

        // Category-based combo item (no product_id)
        ComboItem::factory()->withCategory()->create(['combo_id' => $combo->id]);

        $combo->refresh();
        $this->assertEquals(0.0, $combo->calculatePrice());
    }

    // ==================== SAVINGS ATTRIBUTE TESTS ====================

    public function test_savings_returns_difference_between_items_total_and_combo_price(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create([
            'tenant_id' => $tenant->id,
            'base_price' => 40000,
        ]);
        $combo = Combo::factory()->fixed()->create(['product_id' => $comboProduct->id]);

        $item1 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 25000]);
        $item2 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 25000]);

        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item1->id, 'quantity' => 1]);
        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item2->id, 'quantity' => 1]);

        $combo->refresh();
        // Items total = 50000, Combo price = 40000, Savings = 10000
        $this->assertEquals(10000.0, $combo->savings);
    }

    public function test_savings_is_zero_when_combo_price_exceeds_items_total(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create([
            'tenant_id' => $tenant->id,
            'base_price' => 60000,
        ]);
        $combo = Combo::factory()->fixed()->create(['product_id' => $comboProduct->id]);

        $item1 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 20000]);
        $item2 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 20000]);

        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item1->id, 'quantity' => 1]);
        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item2->id, 'quantity' => 1]);

        $combo->refresh();
        // Items total = 40000, Combo price = 60000, Savings = 0 (not negative)
        $this->assertEquals(0.0, $combo->savings);
    }

    public function test_savings_respects_quantity(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create([
            'tenant_id' => $tenant->id,
            'base_price' => 50000,
        ]);
        $combo = Combo::factory()->fixed()->create(['product_id' => $comboProduct->id]);

        $item1 = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 20000]);
        ComboItem::factory()->create(['combo_id' => $combo->id, 'product_id' => $item1->id, 'quantity' => 3]);

        $combo->refresh();
        // Items total = 60000, Combo price = 50000, Savings = 10000
        $this->assertEquals(10000.0, $combo->savings);
    }

    // ==================== CASTING TESTS ====================

    public function test_discount_value_is_cast_to_decimal(): void
    {
        $combo = Combo::factory()->create(['discount_value' => 15.50]);
        $this->assertEquals('15.50', $combo->discount_value);
    }

    public function test_allow_substitutions_is_cast_to_boolean(): void
    {
        $combo = Combo::factory()->create(['allow_substitutions' => 1]);
        $this->assertIsBool($combo->allow_substitutions);
        $this->assertTrue($combo->allow_substitutions);
    }

    public function test_min_items_is_cast_to_integer(): void
    {
        $combo = Combo::factory()->create(['min_items' => '3']);
        $this->assertIsInt($combo->min_items);
        $this->assertEquals(3, $combo->min_items);
    }

    public function test_max_items_is_cast_to_integer(): void
    {
        $combo = Combo::factory()->create(['max_items' => '5']);
        $this->assertIsInt($combo->max_items);
        $this->assertEquals(5, $combo->max_items);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_fixed_state(): void
    {
        $combo = Combo::factory()->fixed()->create();
        $this->assertEquals(Combo::PRICING_FIXED, $combo->pricing_type);
        $this->assertEquals('0.00', $combo->discount_value);
    }

    public function test_factory_sum_pricing_state(): void
    {
        $combo = Combo::factory()->sumPricing()->create();
        $this->assertEquals(Combo::PRICING_SUM, $combo->pricing_type);
        $this->assertEquals('0.00', $combo->discount_value);
    }

    public function test_factory_discount_percent_state(): void
    {
        $combo = Combo::factory()->discountPercent(20)->create();
        $this->assertEquals(Combo::PRICING_DISCOUNT_PERCENT, $combo->pricing_type);
        $this->assertEquals('20.00', $combo->discount_value);
    }

    public function test_factory_discount_amount_state(): void
    {
        $combo = Combo::factory()->discountAmount(25000)->create();
        $this->assertEquals(Combo::PRICING_DISCOUNT_AMOUNT, $combo->pricing_type);
        $this->assertEquals('25000.00', $combo->discount_value);
    }

    public function test_factory_allow_substitutions_state(): void
    {
        $combo = Combo::factory()->allowSubstitutions()->create();
        $this->assertTrue($combo->allow_substitutions);
    }

    public function test_factory_with_item_limits_state(): void
    {
        $combo = Combo::factory()->withItemLimits(2, 5)->create();
        $this->assertEquals(2, $combo->min_items);
        $this->assertEquals(5, $combo->max_items);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_combo_uses_uuid(): void
    {
        $combo = Combo::factory()->create();
        $this->assertNotNull($combo->id);
        $this->assertIsString($combo->id);
        // UUID format check
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $combo->id
        );
    }

    // ==================== ITEM LIMITS TESTS ====================

    public function test_combo_has_default_min_items(): void
    {
        $combo = Combo::factory()->create();

        // min_items defaults to 1 per migration
        $this->assertEquals(1, $combo->min_items);
        $this->assertNull($combo->max_items);
    }

    public function test_combo_can_have_min_and_max_items(): void
    {
        $combo = Combo::factory()->create([
            'min_items' => 1,
            'max_items' => 3,
        ]);

        $this->assertEquals(1, $combo->min_items);
        $this->assertEquals(3, $combo->max_items);
    }
}
