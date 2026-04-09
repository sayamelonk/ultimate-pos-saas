<?php

namespace Tests\Unit\Models;

use App\Models\Combo;
use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOutlet;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_product(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Test Product',
            'sku' => 'SKU-TEST-001',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Test Product',
            'sku' => 'SKU-TEST-001',
        ]);
    }

    public function test_product_belongs_to_tenant(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $product->tenant);
        $this->assertEquals($this->tenant->id, $product->tenant->id);
    }

    public function test_product_belongs_to_category(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertInstanceOf(ProductCategory::class, $product->category);
        $this->assertEquals($this->category->id, $product->category->id);
    }

    public function test_product_can_belong_to_recipe(): void
    {
        $unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $inventoryItem = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $unit->id,
        ]);
        $recipe = Recipe::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $inventoryItem->id,
            'yield_unit_id' => $unit->id,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'recipe_id' => $recipe->id,
        ]);

        $this->assertInstanceOf(Recipe::class, $product->recipe);
        $this->assertEquals($recipe->id, $product->recipe->id);
    }

    public function test_product_can_belong_to_inventory_item(): void
    {
        $unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
        $inventoryItem = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $unit->id,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'inventory_item_id' => $inventoryItem->id,
            'track_stock' => true,
        ]);

        $this->assertInstanceOf(InventoryItem::class, $product->inventoryItem);
        $this->assertEquals($inventoryItem->id, $product->inventoryItem->id);
    }

    // ============================================================
    // SLUG AUTO-GENERATION TEST
    // ============================================================

    public function test_slug_is_auto_generated_from_name(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Test Product Name',
            'slug' => null,
        ]);

        $this->assertStringContainsString('test-product-name', $product->slug);
    }

    // ============================================================
    // TYPE CONSTANTS TESTS
    // ============================================================

    public function test_type_constants(): void
    {
        $this->assertEquals('single', Product::TYPE_SINGLE);
        $this->assertEquals('variant', Product::TYPE_VARIANT);
        $this->assertEquals('combo', Product::TYPE_COMBO);
    }

    // ============================================================
    // TYPE CHECK TESTS
    // ============================================================

    public function test_is_single(): void
    {
        $product = Product::factory()->single()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertTrue($product->isSingle());
        $this->assertFalse($product->isVariant());
        $this->assertFalse($product->isCombo());
    }

    public function test_is_variant(): void
    {
        $product = Product::factory()->variant()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertTrue($product->isVariant());
        $this->assertFalse($product->isSingle());
        $this->assertFalse($product->isCombo());
    }

    public function test_is_combo(): void
    {
        $product = Product::factory()->combo()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertTrue($product->isCombo());
        $this->assertFalse($product->isSingle());
        $this->assertFalse($product->isVariant());
    }

    // ============================================================
    // VARIANTS RELATIONSHIP TESTS
    // ============================================================

    public function test_product_has_many_variants(): void
    {
        $product = Product::factory()->variant()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        ProductVariant::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        $this->assertCount(3, $product->variants);
    }

    public function test_product_has_active_variants(): void
    {
        $product = Product::factory()->variant()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        ProductVariant::factory()->count(2)->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        ProductVariant::factory()->inactive()->create([
            'product_id' => $product->id,
        ]);

        $this->assertCount(2, $product->activeVariants);
    }

    public function test_variants_are_ordered_by_sort_order(): void
    {
        $product = Product::factory()->variant()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Third',
            'sort_order' => 3,
        ]);

        $variant2 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'First',
            'sort_order' => 1,
        ]);

        $variant3 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Second',
            'sort_order' => 2,
        ]);

        $variants = $product->variants;
        $this->assertEquals('First', $variants[0]->name);
        $this->assertEquals('Second', $variants[1]->name);
        $this->assertEquals('Third', $variants[2]->name);
    }

    // ============================================================
    // COMBO RELATIONSHIP TESTS
    // ============================================================

    public function test_product_has_one_combo(): void
    {
        $product = Product::factory()->combo()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $combo = Combo::factory()->create([
            'product_id' => $product->id,
        ]);

        $this->assertInstanceOf(Combo::class, $product->combo);
        $this->assertEquals($combo->id, $product->combo->id);
    }

    // ============================================================
    // OUTLETS RELATIONSHIP TESTS
    // ============================================================

    public function test_product_has_many_product_outlets(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $outlet1 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $outlet2 = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet1->id,
        ]);

        ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet2->id,
        ]);

        $this->assertCount(2, $product->productOutlets);
    }

    // ============================================================
    // GET PRICE FOR OUTLET TESTS
    // ============================================================

    public function test_get_price_for_outlet_returns_base_price_when_no_custom_price(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 50000,
        ]);

        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
            'custom_price' => null,
        ]);

        $this->assertEquals(50000, $product->getPriceForOutlet($outlet->id));
    }

    public function test_get_price_for_outlet_returns_custom_price_when_set(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 50000,
        ]);

        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        ProductOutlet::factory()->withCustomPrice(45000)->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
        ]);

        $this->assertEquals(45000, $product->getPriceForOutlet($outlet->id));
    }

    public function test_get_price_for_outlet_returns_base_price_when_no_outlet_specified(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 50000,
        ]);

        $this->assertEquals(50000, $product->getPriceForOutlet());
    }

    public function test_get_price_for_outlet_returns_base_price_when_outlet_not_found(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 50000,
        ]);

        $this->assertEquals(50000, $product->getPriceForOutlet('non-existent-id'));
    }

    // ============================================================
    // IS AVAILABLE AT OUTLET TESTS
    // ============================================================

    public function test_is_available_at_outlet_returns_true_when_available(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        ProductOutlet::factory()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
            'is_available' => true,
        ]);

        $this->assertTrue($product->isAvailableAtOutlet($outlet->id));
    }

    public function test_is_available_at_outlet_returns_false_when_unavailable(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        ProductOutlet::factory()->unavailable()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
        ]);

        $this->assertFalse($product->isAvailableAtOutlet($outlet->id));
    }

    public function test_is_available_at_outlet_returns_true_when_no_product_outlet_record(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);

        // No ProductOutlet record exists - should default to true
        $this->assertTrue($product->isAvailableAtOutlet($outlet->id));
    }

    // ============================================================
    // MARGIN ATTRIBUTE TESTS
    // ============================================================

    public function test_get_margin_attribute(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 100000,
            'cost_price' => 60000,
        ]);

        // margin = ((100000 - 60000) / 100000) * 100 = 40%
        $this->assertEquals(40, $product->margin);
    }

    public function test_get_margin_returns_100_when_cost_is_zero(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 100000,
            'cost_price' => 0,
        ]);

        $this->assertEquals(100, $product->margin);
    }

    // ============================================================
    // SCOPE TESTS
    // ============================================================

    public function test_scope_active(): void
    {
        Product::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        Product::factory()->inactive()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertCount(3, Product::active()->get());
    }

    public function test_scope_for_pos(): void
    {
        Product::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'show_in_pos' => true,
        ]);

        Product::factory()->hiddenFromPos()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertCount(3, Product::forPos()->get());
    }

    public function test_scope_for_menu(): void
    {
        Product::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'show_in_menu' => true,
        ]);

        Product::factory()->hiddenFromMenu()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertCount(3, Product::forMenu()->get());
    }

    public function test_scope_featured(): void
    {
        Product::factory()->featured()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Product::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'is_featured' => false,
        ]);

        $this->assertCount(2, Product::featured()->get());
    }

    public function test_scope_of_type(): void
    {
        Product::factory()->single()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Product::factory()->variant()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Product::factory()->combo()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertCount(2, Product::ofType(Product::TYPE_SINGLE)->get());
        $this->assertCount(3, Product::ofType(Product::TYPE_VARIANT)->get());
        $this->assertCount(1, Product::ofType(Product::TYPE_COMBO)->get());
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_decimal_fields_are_properly_cast(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 50000.50,
            'cost_price' => 30000.25,
        ]);

        $this->assertIsString($product->base_price);
        $this->assertIsString($product->cost_price);
    }

    public function test_boolean_fields_are_properly_cast(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'is_active' => 1,
            'is_featured' => 0,
            'track_stock' => true,
        ]);

        $this->assertTrue($product->is_active);
        $this->assertFalse($product->is_featured);
        $this->assertTrue($product->track_stock);
    }

    public function test_array_fields_are_properly_cast(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'tags' => ['spicy', 'popular'],
            'allergens' => ['gluten', 'nuts'],
            'nutritional_info' => ['calories' => 500, 'protein' => 25],
        ]);

        $this->assertIsArray($product->tags);
        $this->assertIsArray($product->allergens);
        $this->assertIsArray($product->nutritional_info);
        $this->assertEquals(['spicy', 'popular'], $product->tags);
    }

    // ============================================================
    // SOFT DELETE TESTS
    // ============================================================

    public function test_product_can_be_soft_deleted(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $productId = $product->id;
        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $productId]);
    }

    public function test_soft_deleted_product_can_be_restored(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $product->delete();
        $product->restore();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_inactive_factory_state(): void
    {
        $product = Product::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertFalse($product->is_active);
    }

    public function test_featured_factory_state(): void
    {
        $product = Product::factory()->featured()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertTrue($product->is_featured);
    }

    public function test_with_price_factory_state(): void
    {
        $product = Product::factory()->withPrice(75000)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertEquals(75000, $product->base_price);
        $this->assertEquals(30000, $product->cost_price); // 40% of base price
    }

    public function test_with_barcode_factory_state(): void
    {
        $product = Product::factory()->withBarcode('1234567890123')->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $this->assertEquals('1234567890123', $product->barcode);
    }
}
