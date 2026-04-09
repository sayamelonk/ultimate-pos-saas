<?php

namespace Tests\Feature\Api\V2;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->unit = Unit::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ==========================================
    // LIST INVENTORY ITEMS
    // ==========================================

    /** @test */
    public function can_list_inventory_items(): void
    {
        InventoryItem::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/inventory/items', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sku',
                        'barcode',
                        'name',
                        'unit_name',
                        'cost_price',
                        'stock_quantity',
                        'min_stock',
                        'max_stock',
                        'reorder_point',
                        'is_low_stock',
                        'is_active',
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
    public function guest_cannot_list_inventory_items(): void
    {
        $response = $this->getJson('/api/v2/inventory/items');

        $response->assertUnauthorized();
    }

    /** @test */
    public function can_search_inventory_items(): void
    {
        InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'name' => 'Kopi Robusta',
        ]);

        InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'name' => 'Gula Pasir',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/inventory/items?q=Kopi', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('Kopi', $response->json('data.0.name'));
    }

    /** @test */
    public function can_filter_low_stock_items(): void
    {
        $item1 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'reorder_point' => 20,
        ]);
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item1->id,
            'quantity' => 5, // Low stock
        ]);

        $item2 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'reorder_point' => 10,
        ]);
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item2->id,
            'quantity' => 100, // Normal stock
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/inventory/items?low_stock=1', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function only_tenant_items_are_returned(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUnit = Unit::factory()->create(['tenant_id' => $otherTenant->id]);

        InventoryItem::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        InventoryItem::factory()->count(2)->create([
            'tenant_id' => $otherTenant->id,
            'unit_id' => $otherUnit->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/inventory/items', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    // ==========================================
    // GET INVENTORY ITEM DETAIL
    // ==========================================

    /** @test */
    public function can_get_inventory_item_detail(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
            'quantity' => 100,
            'avg_cost' => 5000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/inventory/items/{$item->id}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'sku',
                    'barcode',
                    'name',
                    'description',
                    'unit_id',
                    'unit_name',
                    'cost_price',
                    'min_stock',
                    'max_stock',
                    'reorder_point',
                    'reorder_qty',
                    'track_batches',
                    'is_active',
                    'stock' => [
                        'quantity',
                        'reserved_qty',
                        'available_qty',
                        'avg_cost',
                        'stock_value',
                        'is_low_stock',
                    ],
                ],
            ])
            ->assertJsonPath('data.id', $item->id);
    }

    /** @test */
    public function guest_cannot_get_inventory_item_detail(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->getJson("/api/v2/inventory/items/{$item->id}");

        $response->assertUnauthorized();
    }

    /** @test */
    public function cannot_get_other_tenant_item(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUnit = Unit::factory()->create(['tenant_id' => $otherTenant->id]);

        $item = InventoryItem::factory()->create([
            'tenant_id' => $otherTenant->id,
            'unit_id' => $otherUnit->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/inventory/items/{$item->id}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertNotFound();
    }

    // ==========================================
    // GET STOCK LEVELS
    // ==========================================

    /** @test */
    public function can_get_stock_levels(): void
    {
        $item1 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item1->id,
            'quantity' => 100,
        ]);

        $item2 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item2->id,
            'quantity' => 50,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/inventory/stock', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'item_id',
                        'item_name',
                        'item_sku',
                        'unit_name',
                        'quantity',
                        'reserved_qty',
                        'available_qty',
                        'avg_cost',
                        'stock_value',
                        'is_low_stock',
                        'reorder_point',
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function guest_cannot_get_stock_levels(): void
    {
        $response = $this->getJson('/api/v2/inventory/stock');

        $response->assertUnauthorized();
    }

    // ==========================================
    // CHECK PRODUCT STOCK
    // ==========================================

    /** @test */
    public function can_check_product_stock(): void
    {
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'track_stock' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/inventory/products/{$product->id}/stock", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'product_id',
                    'product_name',
                    'track_stock',
                    'has_recipe',
                    'stock_available',
                    'can_sell_quantity',
                    'ingredients' => [
                        '*' => [
                            'item_id',
                            'item_name',
                            'required_qty',
                            'available_qty',
                            'is_sufficient',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_check_product_stock(): void
    {
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson("/api/v2/inventory/products/{$product->id}/stock");

        $response->assertUnauthorized();
    }

    // ==========================================
    // STOCK ADJUSTMENT
    // ==========================================

    /** @test */
    public function can_create_stock_adjustment(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
            'quantity' => 100,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/inventory/adjustments', [
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'adjustment_type' => 'add',
                    'quantity' => 50,
                    'reason' => 'Stock count adjustment',
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'adjustment_id',
                    'items' => [
                        '*' => [
                            'item_id',
                            'item_name',
                            'previous_qty',
                            'adjustment_qty',
                            'new_qty',
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('inventory_stocks', [
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
            'quantity' => 150,
        ]);
    }

    /** @test */
    public function guest_cannot_create_stock_adjustment(): void
    {
        $response = $this->postJson('/api/v2/inventory/adjustments', [
            'items' => [],
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function stock_adjustment_requires_items(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/inventory/adjustments', [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function stock_adjustment_validates_quantity(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item->id,
            'quantity' => 10,
        ]);

        Sanctum::actingAs($this->user);

        // Try to subtract more than available
        $response = $this->postJson('/api/v2/inventory/adjustments', [
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'adjustment_type' => 'subtract',
                    'quantity' => 50,
                    'reason' => 'Waste',
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable();
    }

    // ==========================================
    // LOW STOCK ALERTS
    // ==========================================

    /** @test */
    public function can_get_low_stock_alerts(): void
    {
        $item1 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'name' => 'Low Item',
            'reorder_point' => 50,
        ]);
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item1->id,
            'quantity' => 10, // Below reorder point
        ]);

        $item2 = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'name' => 'Normal Item',
            'reorder_point' => 20,
        ]);
        InventoryStock::factory()->create([
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $item2->id,
            'quantity' => 100, // Above reorder point
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/inventory/alerts/low-stock', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'item_id',
                        'item_name',
                        'item_sku',
                        'current_qty',
                        'reorder_point',
                        'reorder_qty',
                        'unit_name',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Low Item', $response->json('data.0.item_name'));
    }

    /** @test */
    public function guest_cannot_get_low_stock_alerts(): void
    {
        $response = $this->getJson('/api/v2/inventory/alerts/low-stock');

        $response->assertUnauthorized();
    }

    // ==========================================
    // STOCK HISTORY
    // ==========================================

    /** @test */
    public function can_get_stock_history(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/inventory/items/{$item->id}/history", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'quantity',
                        'balance_before',
                        'balance_after',
                        'reference',
                        'reason',
                        'user_name',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_stock_history(): void
    {
        $item = InventoryItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->getJson("/api/v2/inventory/items/{$item->id}/history");

        $response->assertUnauthorized();
    }
}
