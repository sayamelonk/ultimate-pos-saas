<?php

namespace Tests\Feature\Api\V1;

use App\Models\Floor;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSyncMasterTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->outlets()->attach($this->outlet->id, ['is_default' => true]);
    }

    /** @test */
    public function authenticated_user_can_sync_master_data(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'categories',
                    'products',
                    'payment_methods',
                    'floors',
                    'outlet',
                    'sync_timestamp',
                    'counts' => [
                        'categories',
                        'products',
                        'payment_methods',
                        'floors',
                        'tables',
                    ],
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_sync_master_data(): void
    {
        $response = $this->getJson('/api/v1/mobile/sync/master');

        $response->assertUnauthorized();
    }

    /** @test */
    public function sync_uses_default_outlet_when_no_header(): void
    {
        // User sudah punya default outlet dari setUp
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/mobile/sync/master');

        // Harus berhasil karena fallback ke default outlet
        $response->assertOk();
        $this->assertEquals($this->outlet->id, $response->json('data.outlet.id'));
    }

    /** @test */
    public function sync_fails_when_user_has_no_outlet_access(): void
    {
        // Create user without outlet access
        $userNoOutlet = User::factory()->create(['tenant_id' => $this->tenant->id]);
        // Don't attach any outlet

        $response = $this->actingAs($userNoOutlet)
            ->getJson('/api/v1/mobile/sync/master');

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function sync_returns_categories_with_correct_structure(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $response->assertOk();

        $categories = $response->json('data.categories');
        $this->assertCount(1, $categories);
        $this->assertEquals($category->id, $categories[0]['id']);
        $this->assertArrayHasKey('name', $categories[0]);
        $this->assertArrayHasKey('slug', $categories[0]);
        $this->assertArrayHasKey('image', $categories[0]);
        $this->assertArrayHasKey('color', $categories[0]);
        $this->assertArrayHasKey('sort_order', $categories[0]);
        $this->assertArrayHasKey('products_count', $categories[0]);
        $this->assertArrayHasKey('updated_at', $categories[0]);
    }

    /** @test */
    public function sync_only_returns_active_categories(): void
    {
        ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Active Category',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Inactive Category',
            'is_active' => false,
            'show_in_pos' => true,
        ]);

        ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hidden Category',
            'is_active' => true,
            'show_in_pos' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $categories = $response->json('data.categories');
        $this->assertCount(1, $categories);
        $this->assertEquals('Active Category', $categories[0]['name']);
    }

    /** @test */
    public function sync_returns_products_with_correct_structure(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
            'show_in_pos' => true,
            'base_price' => 25000,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $response->assertOk();

        $products = $response->json('data.products');
        $this->assertCount(1, $products);
        $this->assertEquals($product->id, $products[0]['id']);
        $this->assertArrayHasKey('name', $products[0]);
        $this->assertArrayHasKey('sku', $products[0]);
        $this->assertArrayHasKey('barcode', $products[0]);
        $this->assertArrayHasKey('base_price', $products[0]);
        $this->assertArrayHasKey('price', $products[0]);
        $this->assertArrayHasKey('image', $products[0]);
        $this->assertArrayHasKey('product_type', $products[0]);
        $this->assertArrayHasKey('is_available', $products[0]);
        $this->assertArrayHasKey('updated_at', $products[0]);
    }

    /** @test */
    public function sync_only_returns_active_products(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'name' => 'Active Product',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'name' => 'Inactive Product',
            'is_active' => false,
            'show_in_pos' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $products = $response->json('data.products');
        $this->assertCount(1, $products);
        $this->assertEquals('Active Product', $products[0]['name']);
    }

    /** @test */
    public function sync_returns_products_with_outlet_specific_price(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
            'show_in_pos' => true,
            'base_price' => 25000,
        ]);

        // Set outlet-specific price
        $product->productOutlets()->create([
            'outlet_id' => $this->outlet->id,
            'custom_price' => 30000,
            'is_available' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $products = $response->json('data.products');
        $this->assertEquals(25000, $products[0]['base_price']);
        $this->assertEquals(30000, $products[0]['price']);
    }

    /** @test */
    public function sync_returns_payment_methods_with_correct_structure(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'code' => 'CASH',
            'name' => 'Tunai',
            'type' => 'cash',
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $response->assertOk();

        $paymentMethods = $response->json('data.payment_methods');
        $this->assertCount(1, $paymentMethods);
        $this->assertEquals($paymentMethod->id, $paymentMethods[0]['id']);
        $this->assertArrayHasKey('code', $paymentMethods[0]);
        $this->assertArrayHasKey('name', $paymentMethods[0]);
        $this->assertArrayHasKey('type', $paymentMethods[0]);
        $this->assertArrayHasKey('charge_percentage', $paymentMethods[0]);
        $this->assertArrayHasKey('charge_fixed', $paymentMethods[0]);
        $this->assertArrayHasKey('requires_reference', $paymentMethods[0]);
        $this->assertArrayHasKey('opens_cash_drawer', $paymentMethods[0]);
    }

    /** @test */
    public function sync_returns_floors_with_tables(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Lantai 1',
            'is_active' => true,
        ]);

        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
            'name' => 'Meja 1',
            'number' => 1,
            'capacity' => 4,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $response->assertOk();

        $floors = $response->json('data.floors');
        $this->assertCount(1, $floors);
        $this->assertEquals('Lantai 1', $floors[0]['name']);
        $this->assertCount(1, $floors[0]['tables']);
        $this->assertEquals('Meja 1', $floors[0]['tables'][0]['name']);
        $this->assertEquals(4, $floors[0]['tables'][0]['capacity']);
    }

    /** @test */
    public function sync_returns_outlet_settings(): void
    {
        $this->outlet->update([
            'tax_percentage' => 11.00,
            'service_charge_percentage' => 5.00,
            'receipt_header' => 'Welcome!',
            'receipt_footer' => 'Thank you!',
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $response->assertOk();

        $outlet = $response->json('data.outlet');
        $this->assertEquals($this->outlet->id, $outlet['id']);
        $this->assertEquals($this->outlet->name, $outlet['name']);
        $this->assertEquals(11.00, $outlet['tax_percentage']);
        $this->assertEquals(5.00, $outlet['service_charge_percentage']);
        $this->assertEquals('Welcome!', $outlet['receipt_header']);
        $this->assertEquals('Thank you!', $outlet['receipt_footer']);
    }

    /** @test */
    public function sync_returns_correct_counts(): void
    {
        // Create 3 categories
        ProductCategory::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        // Create 2 payment methods
        PaymentMethod::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        // Create 1 floor with 5 tables
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
        ]);

        Table::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $counts = $response->json('data.counts');
        $this->assertEquals(3, $counts['categories']);
        $this->assertEquals(2, $counts['payment_methods']);
        $this->assertEquals(1, $counts['floors']);
        $this->assertEquals(5, $counts['tables']);
    }

    /** @test */
    public function sync_only_returns_data_for_current_tenant(): void
    {
        // Create another tenant with data
        $otherTenant = Tenant::factory()->create();
        ProductCategory::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Tenant Category',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        // Create data for current tenant
        ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'My Category',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $categories = $response->json('data.categories');
        $this->assertCount(1, $categories);
        $this->assertEquals('My Category', $categories[0]['name']);
    }

    /** @test */
    public function sync_only_returns_floors_for_current_outlet(): void
    {
        // Create another outlet with floor
        $otherOutlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $otherOutlet->id,
            'name' => 'Other Outlet Floor',
            'is_active' => true,
        ]);

        // Create floor for current outlet
        Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'My Floor',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $floors = $response->json('data.floors');
        $this->assertCount(1, $floors);
        $this->assertEquals('My Floor', $floors[0]['name']);
    }

    /** @test */
    public function sync_includes_sync_timestamp(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/mobile/sync/master');

        $response->assertOk();
        $this->assertNotNull($response->json('data.sync_timestamp'));
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $response->json('data.sync_timestamp')
        );
    }
}
