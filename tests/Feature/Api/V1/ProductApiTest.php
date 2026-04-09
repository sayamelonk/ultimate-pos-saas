<?php

namespace Tests\Feature\Api\V1;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOutlet;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ==========================================
    // LIST PRODUCTS
    // ==========================================

    /** @test */
    public function authenticated_user_can_list_products(): void
    {
        Product::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'category_id',
                        'category_name',
                        'category_color',
                        'sku',
                        'barcode',
                        'name',
                        'image',
                        'base_price',
                        'price',
                        'product_type',
                        'track_stock',
                        'is_available',
                        'is_featured',
                        'allow_notes',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function guest_cannot_list_products(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertUnauthorized();
    }

    /** @test */
    public function only_active_products_are_returned(): void
    {
        Product::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        Product::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function only_show_in_pos_products_are_returned(): void
    {
        Product::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        Product::factory()->hiddenFromPos()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function only_tenant_products_are_returned(): void
    {
        $otherTenant = Tenant::factory()->create();

        Product::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Product::factory()->create([
            'tenant_id' => $otherTenant->id,
            'category_id' => ProductCategory::factory()->create(['tenant_id' => $otherTenant->id])->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function can_filter_products_by_category(): void
    {
        $category2 = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        Product::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category2->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/products?category_id={$this->category->id}");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function can_filter_products_by_type(): void
    {
        Product::factory()->single()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Product::factory()->variant()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products?type=single');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function can_filter_featured_products(): void
    {
        Product::factory()->featured()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'is_featured' => false,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products?featured=1');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function products_include_variants_for_variant_type(): void
    {
        $product = Product::factory()->variant()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        ProductVariant::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $data = $response->json('data.0');
        $this->assertEquals('variant', $data['product_type']);
        $this->assertArrayHasKey('variants', $data);
        $this->assertCount(3, $data['variants']);
    }

    /** @test */
    public function pagination_works_correctly(): void
    {
        Product::factory()->count(60)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products?per_page=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(60, $response->json('meta.total'));
    }

    /** @test */
    public function max_per_page_is_limited_to_100(): void
    {
        Product::factory()->count(150)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products?per_page=200');

        $response->assertOk();
        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }

    // ==========================================
    // SEARCH PRODUCTS
    // ==========================================

    /** @test */
    public function can_search_products_by_name(): void
    {
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Nasi Goreng Special',
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Mie Goreng',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products/search?q=Nasi');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('Nasi', $response->json('data.0.name'));
    }

    /** @test */
    public function can_search_products_by_sku(): void
    {
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'sku' => 'PRD-001',
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'sku' => 'PRD-002',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products/search?q=PRD-001');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('PRD-001', $response->json('data.0.sku'));
    }

    /** @test */
    public function can_search_products_by_barcode(): void
    {
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'barcode' => '8991234567890',
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'barcode' => '8990987654321',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products/search?q=8991234567890');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('8991234567890', $response->json('data.0.barcode'));
    }

    /** @test */
    public function search_requires_query_parameter(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products/search');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }

    /** @test */
    public function search_limits_results_to_20(): void
    {
        Product::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Kopi Susu',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products/search?q=Kopi');

        $response->assertOk();
        $this->assertLessThanOrEqual(20, count($response->json('data')));
    }

    /** @test */
    public function guest_cannot_search_products(): void
    {
        $response = $this->getJson('/api/v1/products/search?q=test');

        $response->assertUnauthorized();
    }

    // ==========================================
    // GET PRODUCT BY BARCODE
    // ==========================================

    /** @test */
    public function can_get_product_by_barcode(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'barcode' => '8991234567890',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products/barcode/8991234567890');

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.barcode', '8991234567890');
    }

    /** @test */
    public function can_get_product_by_variant_barcode(): void
    {
        $product = Product::factory()->variant()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'barcode' => '8997777777777',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products/barcode/8997777777777');

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.matched_variant_id', $variant->id);
    }

    /** @test */
    public function returns_404_for_non_existent_barcode(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products/barcode/0000000000000');

        $response->assertNotFound();
    }

    /** @test */
    public function cannot_get_other_tenant_product_by_barcode(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCategory = ProductCategory::factory()->create(['tenant_id' => $otherTenant->id]);

        Product::factory()->create([
            'tenant_id' => $otherTenant->id,
            'category_id' => $otherCategory->id,
            'barcode' => '8991234567890',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products/barcode/8991234567890');

        $response->assertNotFound();
    }

    /** @test */
    public function guest_cannot_get_product_by_barcode(): void
    {
        $response = $this->getJson('/api/v1/products/barcode/8991234567890');

        $response->assertUnauthorized();
    }

    // ==========================================
    // GET PRODUCT DETAIL
    // ==========================================

    /** @test */
    public function can_get_product_detail(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'category_id',
                    'category_name',
                    'category_color',
                    'sku',
                    'barcode',
                    'name',
                    'slug',
                    'description',
                    'image',
                    'base_price',
                    'price',
                    'cost_price',
                    'product_type',
                    'track_stock',
                    'is_available',
                    'is_featured',
                    'allow_notes',
                    'prep_time_minutes',
                    'tags',
                    'allergens',
                    'nutritional_info',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $product->id);
    }

    /** @test */
    public function product_detail_includes_variants_for_variant_type(): void
    {
        $product = Product::factory()->variant()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        ProductVariant::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('variant', $data['product_type']);
        $this->assertArrayHasKey('variants', $data);
        $this->assertCount(3, $data['variants']);

        $variant = $data['variants'][0];
        $this->assertArrayHasKey('id', $variant);
        $this->assertArrayHasKey('sku', $variant);
        $this->assertArrayHasKey('barcode', $variant);
        $this->assertArrayHasKey('name', $variant);
        $this->assertArrayHasKey('price', $variant);
    }

    /** @test */
    public function cannot_get_other_tenant_product_detail(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCategory = ProductCategory::factory()->create(['tenant_id' => $otherTenant->id]);

        $product = Product::factory()->create([
            'tenant_id' => $otherTenant->id,
            'category_id' => $otherCategory->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function returns_404_for_non_existent_product(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    }

    /** @test */
    public function guest_cannot_get_product_detail(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertUnauthorized();
    }

    // ==========================================
    // OUTLET-SPECIFIC PRICING
    // ==========================================

    /** @test */
    public function products_return_outlet_specific_price_when_header_provided(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 25000,
        ]);

        ProductOutlet::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'is_available' => true,
            'custom_price' => 30000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $data = $response->json('data.0');
        $this->assertEquals(25000, $data['base_price']);
        $this->assertEquals(30000, $data['price']);
    }

    /** @test */
    public function products_return_base_price_when_no_outlet_header(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 25000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $data = $response->json('data.0');
        $this->assertEquals(25000, $data['base_price']);
        $this->assertEquals(25000, $data['price']);
    }

    /** @test */
    public function products_show_availability_status_per_outlet(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        ProductOutlet::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'is_available' => false,
            'custom_price' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $data = $response->json('data.0');
        $this->assertFalse($data['is_available']);
    }

    /** @test */
    public function product_detail_returns_outlet_specific_price(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 25000,
        ]);

        ProductOutlet::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'is_available' => true,
            'custom_price' => 35000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/products/{$product->id}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals(25000, $response->json('data.base_price'));
        $this->assertEquals(35000, $response->json('data.price'));
    }

    /** @test */
    public function barcode_search_returns_outlet_specific_price(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'barcode' => '8991234567890',
            'base_price' => 25000,
        ]);

        ProductOutlet::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'is_available' => true,
            'custom_price' => 28000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/products/barcode/8991234567890', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals(25000, $response->json('data.base_price'));
        $this->assertEquals(28000, $response->json('data.price'));
    }
}
