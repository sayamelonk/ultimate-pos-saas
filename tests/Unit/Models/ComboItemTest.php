<?php

namespace Tests\Unit\Models;

use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComboItemTest extends TestCase
{
    use RefreshDatabase;

    // ==================== CREATION TESTS ====================

    public function test_can_create_combo_item(): void
    {
        $tenant = Tenant::factory()->create();
        $comboProduct = Product::factory()->combo()->create(['tenant_id' => $tenant->id]);
        $combo = Combo::factory()->create(['product_id' => $comboProduct->id]);
        $product = Product::factory()->create(['tenant_id' => $tenant->id]);

        $comboItem = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseHas('combo_items', [
            'id' => $comboItem->id,
            'combo_id' => $combo->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_combo_item_has_required_attributes(): void
    {
        $comboItem = ComboItem::factory()->create();

        $this->assertNotNull($comboItem->id);
        $this->assertNotNull($comboItem->combo_id);
        $this->assertNotNull($comboItem->group_name);
        $this->assertNotNull($comboItem->quantity);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_combo_item_belongs_to_combo(): void
    {
        $combo = Combo::factory()->create();
        $comboItem = ComboItem::factory()->create(['combo_id' => $combo->id]);

        $this->assertInstanceOf(Combo::class, $comboItem->combo);
        $this->assertEquals($combo->id, $comboItem->combo->id);
    }

    public function test_combo_item_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $comboItem = ComboItem::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $comboItem->product);
        $this->assertEquals($product->id, $comboItem->product->id);
    }

    public function test_combo_item_belongs_to_category(): void
    {
        $category = ProductCategory::factory()->create();
        $comboItem = ComboItem::factory()->create([
            'product_id' => null,
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(ProductCategory::class, $comboItem->category);
        $this->assertEquals($category->id, $comboItem->category->id);
    }

    public function test_combo_item_can_have_product_without_category(): void
    {
        $product = Product::factory()->create();
        $comboItem = ComboItem::factory()->create([
            'product_id' => $product->id,
            'category_id' => null,
        ]);

        $this->assertNotNull($comboItem->product);
        $this->assertNull($comboItem->category);
    }

    public function test_combo_item_can_have_category_without_product(): void
    {
        $category = ProductCategory::factory()->create();
        $comboItem = ComboItem::factory()->withCategory()->create([
            'category_id' => $category->id,
        ]);

        $this->assertNull($comboItem->product);
        $this->assertNotNull($comboItem->category);
    }

    // ==================== GET AVAILABLE PRODUCTS TESTS ====================

    public function test_get_available_products_returns_single_product_when_product_id_set(): void
    {
        $product = Product::factory()->create();
        $comboItem = ComboItem::factory()->create(['product_id' => $product->id]);

        $availableProducts = $comboItem->getAvailableProducts();

        $this->assertCount(1, $availableProducts);
        $this->assertEquals($product->id, $availableProducts->first()->id);
    }

    public function test_get_available_products_returns_category_products_when_category_id_set(): void
    {
        $tenant = Tenant::factory()->create();
        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);

        // Create active products in category
        Product::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
            'product_type' => Product::TYPE_SINGLE,
        ]);

        // Create inactive product (should be excluded)
        Product::factory()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $comboItem = ComboItem::factory()->create([
            'product_id' => null,
            'category_id' => $category->id,
        ]);

        $availableProducts = $comboItem->getAvailableProducts();

        $this->assertCount(3, $availableProducts);
    }

    public function test_get_available_products_excludes_combo_products(): void
    {
        $tenant = Tenant::factory()->create();
        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);

        // Create regular products
        Product::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
            'product_type' => Product::TYPE_SINGLE,
        ]);

        // Create combo product (should be excluded)
        Product::factory()->combo()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $comboItem = ComboItem::factory()->create([
            'product_id' => null,
            'category_id' => $category->id,
        ]);

        $availableProducts = $comboItem->getAvailableProducts();

        $this->assertCount(2, $availableProducts);
    }

    public function test_get_available_products_returns_empty_when_no_product_or_category(): void
    {
        $comboItem = ComboItem::factory()->create([
            'product_id' => null,
            'category_id' => null,
        ]);

        $availableProducts = $comboItem->getAvailableProducts();

        $this->assertCount(0, $availableProducts);
    }

    // ==================== REQUIRED ITEM TESTS ====================

    public function test_combo_item_is_required_by_default(): void
    {
        $comboItem = ComboItem::factory()->create();
        $this->assertTrue($comboItem->is_required);
    }

    public function test_combo_item_can_be_optional(): void
    {
        $comboItem = ComboItem::factory()->optional()->create();
        $this->assertFalse($comboItem->is_required);
    }

    // ==================== VARIANT SELECTION TESTS ====================

    public function test_variant_selection_is_disabled_by_default(): void
    {
        $comboItem = ComboItem::factory()->create();
        $this->assertFalse($comboItem->allow_variant_selection);
    }

    public function test_variant_selection_can_be_enabled(): void
    {
        $comboItem = ComboItem::factory()->allowVariantSelection()->create();
        $this->assertTrue($comboItem->allow_variant_selection);
    }

    // ==================== CASTING TESTS ====================

    public function test_quantity_is_cast_to_integer(): void
    {
        $comboItem = ComboItem::factory()->create(['quantity' => '3']);
        $this->assertIsInt($comboItem->quantity);
        $this->assertEquals(3, $comboItem->quantity);
    }

    public function test_is_required_is_cast_to_boolean(): void
    {
        $comboItem = ComboItem::factory()->create(['is_required' => 1]);
        $this->assertIsBool($comboItem->is_required);
        $this->assertTrue($comboItem->is_required);
    }

    public function test_allow_variant_selection_is_cast_to_boolean(): void
    {
        $comboItem = ComboItem::factory()->create(['allow_variant_selection' => 1]);
        $this->assertIsBool($comboItem->allow_variant_selection);
        $this->assertTrue($comboItem->allow_variant_selection);
    }

    public function test_price_adjustment_is_cast_to_decimal(): void
    {
        $comboItem = ComboItem::factory()->create(['price_adjustment' => 5000.50]);
        $this->assertEquals('5000.50', $comboItem->price_adjustment);
    }

    public function test_sort_order_is_cast_to_integer(): void
    {
        $comboItem = ComboItem::factory()->create(['sort_order' => '5']);
        $this->assertIsInt($comboItem->sort_order);
        $this->assertEquals(5, $comboItem->sort_order);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_optional_state(): void
    {
        $comboItem = ComboItem::factory()->optional()->create();
        $this->assertFalse($comboItem->is_required);
    }

    public function test_factory_required_state(): void
    {
        $comboItem = ComboItem::factory()->required()->create();
        $this->assertTrue($comboItem->is_required);
    }

    public function test_factory_with_category_state(): void
    {
        $comboItem = ComboItem::factory()->withCategory()->create();
        $this->assertNull($comboItem->product_id);
        $this->assertNotNull($comboItem->category_id);
    }

    public function test_factory_allow_variant_selection_state(): void
    {
        $comboItem = ComboItem::factory()->allowVariantSelection()->create();
        $this->assertTrue($comboItem->allow_variant_selection);
    }

    public function test_factory_with_quantity_state(): void
    {
        $comboItem = ComboItem::factory()->withQuantity(5)->create();
        $this->assertEquals(5, $comboItem->quantity);
    }

    public function test_factory_with_price_adjustment_state(): void
    {
        $comboItem = ComboItem::factory()->withPriceAdjustment(2500)->create();
        $this->assertEquals('2500.00', $comboItem->price_adjustment);
    }

    public function test_factory_with_group_name_state(): void
    {
        $comboItem = ComboItem::factory()->withGroupName('Beverage')->create();
        $this->assertEquals('Beverage', $comboItem->group_name);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_combo_item_uses_uuid(): void
    {
        $comboItem = ComboItem::factory()->create();
        $this->assertNotNull($comboItem->id);
        $this->assertIsString($comboItem->id);
        // UUID format check
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $comboItem->id
        );
    }

    // ==================== GROUP NAME TESTS ====================

    public function test_combo_item_can_have_group_name(): void
    {
        $comboItem = ComboItem::factory()->create(['group_name' => 'Main Dish']);
        $this->assertEquals('Main Dish', $comboItem->group_name);
    }

    public function test_combo_items_can_be_grouped(): void
    {
        $combo = Combo::factory()->create();

        ComboItem::factory()->withGroupName('Main')->create(['combo_id' => $combo->id]);
        ComboItem::factory()->withGroupName('Main')->create(['combo_id' => $combo->id]);
        ComboItem::factory()->withGroupName('Side')->create(['combo_id' => $combo->id]);
        ComboItem::factory()->withGroupName('Drink')->create(['combo_id' => $combo->id]);

        $mainItems = $combo->items->where('group_name', 'Main');
        $sideItems = $combo->items->where('group_name', 'Side');
        $drinkItems = $combo->items->where('group_name', 'Drink');

        $this->assertCount(2, $mainItems);
        $this->assertCount(1, $sideItems);
        $this->assertCount(1, $drinkItems);
    }

    // ==================== PRICE ADJUSTMENT TESTS ====================

    public function test_combo_item_can_have_positive_price_adjustment(): void
    {
        $comboItem = ComboItem::factory()->create(['price_adjustment' => 5000]);
        $this->assertEquals('5000.00', $comboItem->price_adjustment);
    }

    public function test_combo_item_can_have_negative_price_adjustment(): void
    {
        $comboItem = ComboItem::factory()->create(['price_adjustment' => -2000]);
        $this->assertEquals('-2000.00', $comboItem->price_adjustment);
    }

    public function test_combo_item_can_have_zero_price_adjustment(): void
    {
        $comboItem = ComboItem::factory()->create(['price_adjustment' => 0]);
        $this->assertEquals('0.00', $comboItem->price_adjustment);
    }
}
