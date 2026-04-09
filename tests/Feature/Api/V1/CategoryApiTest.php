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

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ==========================================
    // LIST CATEGORIES
    // ==========================================

    /** @test */
    public function authenticated_user_can_list_categories(): void
    {
        ProductCategory::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'parent_id',
                        'code',
                        'name',
                        'slug',
                        'description',
                        'image',
                        'color',
                        'icon',
                        'sort_order',
                        'products_count',
                        'is_active',
                        'show_in_pos',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function guest_cannot_list_categories(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertUnauthorized();
    }

    /** @test */
    public function only_active_categories_are_returned(): void
    {
        ProductCategory::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        ProductCategory::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function only_show_in_pos_categories_are_returned(): void
    {
        ProductCategory::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        ProductCategory::factory()->hiddenFromPos()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function only_tenant_categories_are_returned(): void
    {
        $otherTenant = Tenant::factory()->create();

        ProductCategory::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        ProductCategory::factory()->create([
            'tenant_id' => $otherTenant->id,
            'parent_id' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function categories_include_products_count(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        Product::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        // Inactive product should not be counted
        Product::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $this->assertEquals(5, $response->json('data.0.products_count'));
    }

    /** @test */
    public function default_returns_only_root_categories(): void
    {
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        ProductCategory::factory()->count(2)->withParent($parent)->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertNull($response->json('data.0.parent_id'));
    }

    /** @test */
    public function can_filter_categories_by_parent_id(): void
    {
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        ProductCategory::factory()->count(3)->withParent($parent)->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/categories?parent_id={$parent->id}");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $category) {
            $this->assertEquals($parent->id, $category['parent_id']);
        }
    }

    /** @test */
    public function can_include_children_with_categories(): void
    {
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        ProductCategory::factory()->count(2)->withParent($parent)->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/categories?with_children=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertArrayHasKey('children', $response->json('data.0'));
        $this->assertCount(2, $response->json('data.0.children'));
    }

    /** @test */
    public function children_only_include_active_categories(): void
    {
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        ProductCategory::factory()->count(2)->withParent($parent)->create();
        ProductCategory::factory()->inactive()->withParent($parent)->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/categories?with_children=1');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.0.children'));
    }

    // ==========================================
    // GET CATEGORY DETAIL
    // ==========================================

    /** @test */
    public function can_get_category_detail(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'parent_id',
                    'code',
                    'name',
                    'slug',
                    'description',
                    'image',
                    'color',
                    'icon',
                    'sort_order',
                    'products_count',
                    'is_active',
                    'show_in_pos',
                    'children',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $category->id);
    }

    /** @test */
    public function category_detail_includes_children(): void
    {
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        ProductCategory::factory()->count(3)->withParent($parent)->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/categories/{$parent->id}");

        $response->assertOk();
        $this->assertArrayHasKey('children', $response->json('data'));
        $this->assertCount(3, $response->json('data.children'));
    }

    /** @test */
    public function cannot_get_other_tenant_category_detail(): void
    {
        $otherTenant = Tenant::factory()->create();

        $category = ProductCategory::factory()->create([
            'tenant_id' => $otherTenant->id,
            'parent_id' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function returns_404_for_non_existent_category(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/categories/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    }

    /** @test */
    public function guest_cannot_get_category_detail(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertUnauthorized();
    }

    // ==========================================
    // GET PRODUCTS IN CATEGORY
    // ==========================================

    /** @test */
    public function can_get_products_in_category(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        Product::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'category_id',
                        'category_name',
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
                        'sort_order',
                        'tags',
                        'allergens',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function category_products_only_include_active_products(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        Product::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        Product::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function category_products_include_variants_for_variant_type(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $product = Product::factory()->variant()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
        ]);

        ProductVariant::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products");

        $response->assertOk();
        $data = $response->json('data.0');
        $this->assertEquals('variant', $data['product_type']);
        $this->assertArrayHasKey('variants', $data);
        $this->assertCount(3, $data['variants']);
    }

    /** @test */
    public function cannot_get_products_from_other_tenant_category(): void
    {
        $otherTenant = Tenant::factory()->create();

        $category = ProductCategory::factory()->create([
            'tenant_id' => $otherTenant->id,
            'parent_id' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products");

        $response->assertNotFound();
    }

    /** @test */
    public function guest_cannot_get_products_in_category(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products");

        $response->assertUnauthorized();
    }

    /** @test */
    public function category_products_pagination_works_correctly(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        Product::factory()->count(60)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products?per_page=10");

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(60, $response->json('meta.total'));
    }

    // ==========================================
    // OUTLET-SPECIFIC PRICING IN CATEGORY PRODUCTS
    // ==========================================

    /** @test */
    public function category_products_return_outlet_specific_price(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'base_price' => 25000,
        ]);

        ProductOutlet::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'is_available' => true,
            'custom_price' => 30000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $data = $response->json('data.0');
        $this->assertEquals(25000, $data['base_price']);
        $this->assertEquals(30000, $data['price']);
    }

    /** @test */
    public function category_products_show_availability_status_per_outlet(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => null,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
        ]);

        ProductOutlet::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'is_available' => false,
            'custom_price' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $data = $response->json('data.0');
        $this->assertFalse($data['is_available']);
    }
}
