<?php

namespace Tests\Unit\Models;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\Tenant;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    // ==================== CREATION TESTS ====================

    public function test_can_create_variant(): void
    {
        $product = Product::factory()->variant()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Large',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'product_id' => $product->id,
            'name' => 'Large',
        ]);
    }

    public function test_variant_has_required_attributes(): void
    {
        $variant = ProductVariant::factory()->create();

        $this->assertNotNull($variant->id);
        $this->assertNotNull($variant->product_id);
        $this->assertNotNull($variant->sku);
        $this->assertNotNull($variant->name);
        $this->assertNotNull($variant->price);
    }

    public function test_variant_has_unique_sku(): void
    {
        $product = Product::factory()->variant()->create();
        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-001',
        ]);
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-002',
        ]);

        $this->assertNotEquals($variant1->sku, $variant2->sku);
    }

    public function test_variant_has_unique_barcode(): void
    {
        $product = Product::factory()->variant()->create();
        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'barcode' => '1234567890123',
        ]);
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'barcode' => '1234567890124',
        ]);

        $this->assertNotEquals($variant1->barcode, $variant2->barcode);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_variant_belongs_to_product(): void
    {
        $product = Product::factory()->variant()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $variant->product);
        $this->assertEquals($product->id, $variant->product->id);
    }

    public function test_variant_can_belong_to_inventory_item(): void
    {
        $tenant = Tenant::factory()->create();
        $inventoryItem = InventoryItem::factory()->create(['tenant_id' => $tenant->id]);
        $product = Product::factory()->variant()->create(['tenant_id' => $tenant->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'inventory_item_id' => $inventoryItem->id,
        ]);

        $this->assertInstanceOf(InventoryItem::class, $variant->inventoryItem);
        $this->assertEquals($inventoryItem->id, $variant->inventoryItem->id);
    }

    public function test_variant_can_belong_to_recipe(): void
    {
        $tenant = Tenant::factory()->create();
        $recipe = Recipe::factory()->create(['tenant_id' => $tenant->id]);
        $product = Product::factory()->variant()->create(['tenant_id' => $tenant->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'recipe_id' => $recipe->id,
        ]);

        $this->assertInstanceOf(Recipe::class, $variant->recipe);
        $this->assertEquals($recipe->id, $variant->recipe->id);
    }

    public function test_variant_inventory_item_can_be_null(): void
    {
        $variant = ProductVariant::factory()->create(['inventory_item_id' => null]);
        $this->assertNull($variant->inventoryItem);
    }

    public function test_variant_recipe_can_be_null(): void
    {
        $variant = ProductVariant::factory()->create(['recipe_id' => null]);
        $this->assertNull($variant->recipe);
    }

    // ==================== SCOPE TESTS ====================

    public function test_scope_active_returns_only_active_variants(): void
    {
        $product = Product::factory()->variant()->create();
        ProductVariant::factory()->count(3)->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);
        ProductVariant::factory()->count(2)->inactive()->create([
            'product_id' => $product->id,
        ]);

        $activeVariants = ProductVariant::where('product_id', $product->id)->active()->get();
        $this->assertCount(3, $activeVariants);
    }

    // ==================== MARGIN ATTRIBUTE TESTS ====================

    public function test_margin_is_calculated_correctly(): void
    {
        $variant = ProductVariant::factory()->create([
            'price' => 100,
            'cost_price' => 40,
        ]);

        // Margin = ((100 - 40) / 100) * 100 = 60%
        $this->assertEquals(60.0, $variant->margin);
    }

    public function test_margin_returns_100_when_cost_is_zero(): void
    {
        $variant = ProductVariant::factory()->create([
            'price' => 100,
            'cost_price' => 0,
        ]);

        $this->assertEquals(100.0, $variant->margin);
    }

    public function test_margin_returns_100_when_cost_is_negative(): void
    {
        $variant = ProductVariant::factory()->create([
            'price' => 100,
            'cost_price' => -10,
        ]);

        $this->assertEquals(100.0, $variant->margin);
    }

    public function test_margin_is_negative_when_cost_exceeds_price(): void
    {
        $variant = ProductVariant::factory()->create([
            'price' => 100,
            'cost_price' => 120,
        ]);

        // Margin = ((100 - 120) / 100) * 100 = -20%
        $this->assertEquals(-20.0, $variant->margin);
    }

    public function test_margin_with_decimal_values(): void
    {
        $variant = ProductVariant::factory()->create([
            'price' => 25000,
            'cost_price' => 10000,
        ]);

        // Margin = ((25000 - 10000) / 25000) * 100 = 60%
        $this->assertEquals(60.0, $variant->margin);
    }

    // ==================== OPTION NAMES TESTS ====================

    public function test_get_option_names_returns_empty_array_when_no_options(): void
    {
        $variant = ProductVariant::factory()->create(['option_ids' => []]);
        $this->assertEquals([], $variant->getOptionNames());
    }

    public function test_get_option_names_returns_empty_array_when_option_ids_is_empty_array(): void
    {
        $variant = ProductVariant::factory()->create(['option_ids' => []]);
        $this->assertEquals([], $variant->getOptionNames());
    }

    public function test_get_option_names_returns_option_names_ordered_by_sort_order(): void
    {
        $tenant = Tenant::factory()->create();
        $variantGroup = VariantGroup::factory()->create(['tenant_id' => $tenant->id]);

        $option1 = VariantOption::factory()->create([
            'variant_group_id' => $variantGroup->id,
            'name' => 'Small',
            'sort_order' => 1,
        ]);
        $option2 = VariantOption::factory()->create([
            'variant_group_id' => $variantGroup->id,
            'name' => 'Medium',
            'sort_order' => 2,
        ]);
        $option3 = VariantOption::factory()->create([
            'variant_group_id' => $variantGroup->id,
            'name' => 'Large',
            'sort_order' => 3,
        ]);

        $product = Product::factory()->variant()->create(['tenant_id' => $tenant->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'option_ids' => [$option3->id, $option1->id, $option2->id],
        ]);

        $names = $variant->getOptionNames();
        $this->assertEquals(['Small', 'Medium', 'Large'], $names);
    }

    // ==================== CASTING TESTS ====================

    public function test_option_ids_is_cast_to_array(): void
    {
        $variant = ProductVariant::factory()->create([
            'option_ids' => ['opt-1', 'opt-2'],
        ]);
        $this->assertIsArray($variant->option_ids);
    }

    public function test_price_is_cast_to_decimal(): void
    {
        $variant = ProductVariant::factory()->create(['price' => 25000.50]);
        $this->assertEquals('25000.50', $variant->price);
    }

    public function test_cost_price_is_cast_to_decimal(): void
    {
        $variant = ProductVariant::factory()->create(['cost_price' => 10000.25]);
        $this->assertEquals('10000.25', $variant->cost_price);
    }

    public function test_is_active_is_cast_to_boolean(): void
    {
        $variant = ProductVariant::factory()->create(['is_active' => 1]);
        $this->assertIsBool($variant->is_active);
        $this->assertTrue($variant->is_active);
    }

    public function test_sort_order_is_cast_to_integer(): void
    {
        $variant = ProductVariant::factory()->create(['sort_order' => '5']);
        $this->assertIsInt($variant->sort_order);
        $this->assertEquals(5, $variant->sort_order);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_inactive_state(): void
    {
        $variant = ProductVariant::factory()->inactive()->create();
        $this->assertFalse($variant->is_active);
    }

    public function test_factory_with_barcode_state(): void
    {
        $variant = ProductVariant::factory()->withBarcode('CUSTOM123456789')->create();
        $this->assertEquals('CUSTOM123456789', $variant->barcode);
    }

    public function test_factory_with_price_state(): void
    {
        $variant = ProductVariant::factory()->withPrice(50000)->create();
        $this->assertEquals('50000.00', $variant->price);
        // Cost is 40% of price
        $this->assertEquals('20000.00', $variant->cost_price);
    }

    // ==================== UUID TRAIT TESTS ====================

    public function test_variant_uses_uuid(): void
    {
        $variant = ProductVariant::factory()->create();
        $this->assertNotNull($variant->id);
        $this->assertIsString($variant->id);
        // UUID format check
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $variant->id
        );
    }

    // ==================== PRODUCT ASSOCIATION TESTS ====================

    public function test_variant_is_associated_with_variant_type_product(): void
    {
        $product = Product::factory()->variant()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->assertEquals(Product::TYPE_VARIANT, $variant->product->product_type);
        $this->assertTrue($variant->product->isVariant());
    }

    public function test_multiple_variants_can_belong_to_same_product(): void
    {
        $product = Product::factory()->variant()->create();
        ProductVariant::factory()->count(5)->create(['product_id' => $product->id]);

        $this->assertCount(5, $product->variants);
    }

    public function test_variants_are_ordered_by_sort_order_on_product(): void
    {
        $product = Product::factory()->variant()->create();

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Third',
            'sort_order' => 3,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'First',
            'sort_order' => 1,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Second',
            'sort_order' => 2,
        ]);

        $variants = $product->variants;
        $this->assertEquals('First', $variants[0]->name);
        $this->assertEquals('Second', $variants[1]->name);
        $this->assertEquals('Third', $variants[2]->name);
    }

    // ==================== IMAGE TESTS ====================

    public function test_variant_can_have_image(): void
    {
        $variant = ProductVariant::factory()->create(['image' => 'variants/large.jpg']);
        $this->assertEquals('variants/large.jpg', $variant->image);
    }

    public function test_variant_image_can_be_null(): void
    {
        $variant = ProductVariant::factory()->create(['image' => null]);
        $this->assertNull($variant->image);
    }
}
