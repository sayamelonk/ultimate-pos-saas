<?php

namespace Tests\Feature\Api\V2;

use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOutlet;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * TDD Tests for API Sync Product Completeness
 *
 * These tests ensure the API sync returns all fields needed for POS mobile app,
 * matching the completeness of POS web.
 */
class SyncProductCompletenessTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Outlet $outlet;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
            'color' => '#FF5733',
        ]);
    }

    // ==================== Category Info ====================

    /** @test */
    public function product_includes_category_name(): void
    {
        $product = $this->createProductWithOutlet();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $productData = $response->json('data.products.0');

        $this->assertArrayHasKey('category_name', $productData);
        $this->assertEquals($this->category->name, $productData['category_name']);
    }

    /** @test */
    public function product_includes_category_color(): void
    {
        $product = $this->createProductWithOutlet();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $productData = $response->json('data.products.0');

        $this->assertArrayHasKey('category_color', $productData);
        $this->assertEquals('#FF5733', $productData['category_color']);
    }

    // ==================== Helper Flags ====================

    /** @test */
    public function product_includes_has_variants_flag(): void
    {
        $product = $this->createProductWithOutlet(['product_type' => Product::TYPE_VARIANT]);

        // Add variant
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $productData = $response->json('data.products.0');

        $this->assertArrayHasKey('has_variants', $productData);
        $this->assertTrue($productData['has_variants']);
    }

    /** @test */
    public function product_includes_has_modifiers_flag(): void
    {
        $product = $this->createProductWithOutlet();

        // Add modifier group
        $modifierGroup = ModifierGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        $product->modifierGroups()->attach($modifierGroup->id, ['id' => Str::uuid()]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $productData = $response->json('data.products.0');

        $this->assertArrayHasKey('has_modifiers', $productData);
        $this->assertTrue($productData['has_modifiers']);
    }

    /** @test */
    public function product_includes_is_combo_flag(): void
    {
        $product = $this->createProductWithOutlet(['product_type' => Product::TYPE_COMBO]);

        // Add combo
        Combo::factory()->create(['product_id' => $product->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $productData = $response->json('data.products.0');

        $this->assertArrayHasKey('is_combo', $productData);
        $this->assertTrue($productData['is_combo']);
    }

    // ==================== Variants ====================

    /** @test */
    public function variant_includes_price_adjustment(): void
    {
        $product = $this->createProductWithOutlet([
            'product_type' => Product::TYPE_VARIANT,
            'base_price' => 10000,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 15000,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $variant = $response->json('data.products.0.variants.0');

        $this->assertArrayHasKey('price_adjustment', $variant);
        $this->assertEquals(5000, $variant['price_adjustment']);
    }

    // ==================== Modifier Groups ====================

    /** @test */
    public function modifier_group_includes_display_name(): void
    {
        $product = $this->createProductWithOutlet();

        $modifierGroup = ModifierGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Toppings',
            'display_name' => 'Choose Your Toppings',
            'is_active' => true,
        ]);
        $product->modifierGroups()->attach($modifierGroup->id, ['id' => Str::uuid()]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $modGroup = $response->json('data.products.0.modifier_groups.0');

        $this->assertArrayHasKey('display_name', $modGroup);
        $this->assertEquals('Choose Your Toppings', $modGroup['display_name']);
    }

    /** @test */
    public function modifier_group_includes_selection_type(): void
    {
        $product = $this->createProductWithOutlet();

        $modifierGroup = ModifierGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'selection_type' => ModifierGroup::SELECTION_MULTIPLE,
            'is_active' => true,
        ]);
        $product->modifierGroups()->attach($modifierGroup->id, ['id' => Str::uuid()]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $modGroup = $response->json('data.products.0.modifier_groups.0');

        $this->assertArrayHasKey('selection_type', $modGroup);
        $this->assertEquals('multiple', $modGroup['selection_type']);
    }

    // ==================== Modifiers ====================

    /** @test */
    public function modifier_includes_display_name(): void
    {
        $product = $this->createProductWithOutlet();

        $modifierGroup = ModifierGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Modifier::factory()->create([
            'modifier_group_id' => $modifierGroup->id,
            'name' => 'cheese',
            'display_name' => 'Extra Cheese',
            'is_active' => true,
        ]);

        $product->modifierGroups()->attach($modifierGroup->id, ['id' => Str::uuid()]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $modifier = $response->json('data.products.0.modifier_groups.0.modifiers.0');

        $this->assertArrayHasKey('display_name', $modifier);
        $this->assertEquals('Extra Cheese', $modifier['display_name']);
    }

    /** @test */
    public function modifier_includes_is_default(): void
    {
        $product = $this->createProductWithOutlet();

        $modifierGroup = ModifierGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Modifier::factory()->create([
            'modifier_group_id' => $modifierGroup->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        $product->modifierGroups()->attach($modifierGroup->id, ['id' => Str::uuid()]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $modifier = $response->json('data.products.0.modifier_groups.0.modifiers.0');

        $this->assertArrayHasKey('is_default', $modifier);
        $this->assertTrue($modifier['is_default']);
    }

    // ==================== Combo Items ====================

    /** @test */
    public function combo_product_includes_combo_items(): void
    {
        $product = $this->createProductWithOutlet(['product_type' => Product::TYPE_COMBO]);

        $comboItemProduct = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Burger Patty',
            'is_active' => true,
        ]);

        $combo = Combo::factory()->create(['product_id' => $product->id]);

        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $comboItemProduct->id,
            'quantity' => 2,
            'is_required' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $productData = $response->json('data.products.0');

        $this->assertArrayHasKey('combo_items', $productData);
        $this->assertNotEmpty($productData['combo_items']);
    }

    /** @test */
    public function combo_item_includes_product_name(): void
    {
        $product = $this->createProductWithOutlet(['product_type' => Product::TYPE_COMBO]);

        $comboItemProduct = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Burger Patty',
            'is_active' => true,
        ]);

        $combo = Combo::factory()->create(['product_id' => $product->id]);

        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $comboItemProduct->id,
            'quantity' => 2,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $comboItem = $response->json('data.products.0.combo_items.0');

        $this->assertArrayHasKey('product_name', $comboItem);
        $this->assertEquals('Burger Patty', $comboItem['product_name']);
    }

    /** @test */
    public function combo_item_includes_required_fields(): void
    {
        $product = $this->createProductWithOutlet(['product_type' => Product::TYPE_COMBO]);

        $comboItemProduct = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $combo = Combo::factory()->create(['product_id' => $product->id]);

        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $comboItemProduct->id,
            'category_id' => $this->category->id,
            'quantity' => 3,
            'is_required' => false,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $comboItem = $response->json('data.products.0.combo_items.0');

        $this->assertArrayHasKey('id', $comboItem);
        $this->assertArrayHasKey('product_id', $comboItem);
        $this->assertArrayHasKey('category_id', $comboItem);
        $this->assertArrayHasKey('quantity', $comboItem);
        $this->assertArrayHasKey('is_required', $comboItem);
        $this->assertEquals(3, $comboItem['quantity']);
        $this->assertFalse($comboItem['is_required']);
    }

    // ==================== Full Structure Test ====================

    /** @test */
    public function product_sync_matches_pos_web_structure(): void
    {
        // Create product with all features
        $product = $this->createProductWithOutlet([
            'product_type' => Product::TYPE_SINGLE,
            'base_price' => 25000,
        ]);

        // Add modifier group with modifiers
        $modifierGroup = ModifierGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Size',
            'display_name' => 'Choose Size',
            'selection_type' => ModifierGroup::SELECTION_SINGLE,
            'is_active' => true,
        ]);

        Modifier::factory()->create([
            'modifier_group_id' => $modifierGroup->id,
            'name' => 'large',
            'display_name' => 'Large Size',
            'price' => 5000,
            'is_default' => false,
            'is_active' => true,
        ]);

        $product->modifierGroups()->attach($modifierGroup->id, [
            'id' => Str::uuid(),
            'is_required' => true,
            'min_selections' => 1,
            'max_selections' => 1,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $productData = $response->json('data.products.0');

        // Verify all expected fields exist
        $expectedFields = [
            'id',
            'sku',
            'barcode',
            'name',
            'image',
            'category_id',
            'category_name',
            'category_color',
            'product_type',
            'base_price',
            'price',
            'cost_price',
            'allow_notes',
            'has_variants',
            'has_modifiers',
            'is_combo',
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $productData, "Missing field: {$field}");
        }

        // Verify modifier group structure
        $modGroup = $productData['modifier_groups'][0];
        $this->assertArrayHasKey('display_name', $modGroup);
        $this->assertArrayHasKey('selection_type', $modGroup);

        // Verify modifier structure
        $modifier = $modGroup['modifiers'][0];
        $this->assertArrayHasKey('display_name', $modifier);
        $this->assertArrayHasKey('is_default', $modifier);
    }

    // ==================== Helper Methods ====================

    private function createProductWithOutlet(array $attributes = []): Product
    {
        $product = Product::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'is_active' => true,
            'show_in_pos' => true,
        ], $attributes));

        ProductOutlet::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'is_available' => true,
        ]);

        return $product;
    }
}
