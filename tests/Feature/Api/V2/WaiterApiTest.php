<?php

namespace Tests\Feature\Api\V2;

use App\Models\Floor;
use App\Models\KitchenOrder;
use App\Models\KitchenStation;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WaiterApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Outlet $outlet;

    private User $waiter;

    private UserPin $waiterPin;

    private Floor $floor;

    private Table $table;

    private ProductCategory $category;

    private Product $product;

    private KitchenStation $station;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_percentage' => 10,
            'service_charge_percentage' => 0,
        ]);

        // Create waiter role
        $waiterRole = Role::factory()->create([
            'name' => 'Waiter',
            'slug' => 'waiter',
        ]);

        // Create waiter user
        $this->waiter = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Waiter Test',
            'email' => 'waiter.test@demo.com',
        ]);
        $this->waiter->roles()->attach($waiterRole->id);
        $this->waiter->outlets()->attach($this->outlet->id, ['is_default' => true]);

        // Create PIN for waiter
        $this->waiterPin = UserPin::factory()->create([
            'user_id' => $this->waiter->id,
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
        ]);

        // Create floor and tables
        $this->floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Ground Floor',
        ]);

        $this->table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'number' => '1',
            'name' => 'Table 1',
            'capacity' => 4,
            'status' => Table::STATUS_AVAILABLE,
        ]);

        // Create product category and product
        $this->category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Makanan',
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Nasi Goreng',
            'base_price' => 25000,
            'is_active' => true,
        ]);

        // Create kitchen station
        $this->station = KitchenStation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Main Kitchen',
            'code' => 'MAIN',
            'is_active' => true,
        ]);
    }

    private function loginWaiter(): string
    {
        $response = $this->postJson('/api/v2/waiter/auth/login', [
            'outlet_id' => $this->outlet->id,
            'pin' => '1234',
        ]);

        return $response->json('data.token');
    }

    private function waiterHeaders(): array
    {
        if (! isset($this->token)) {
            $this->token = $this->loginWaiter();
        }

        return [
            'Authorization' => 'Bearer '.$this->token,
            'X-Outlet-Id' => $this->outlet->id,
        ];
    }

    // ==================== AUTH TESTS ====================

    public function test_can_list_outlets_for_waiter_login(): void
    {
        $response = $this->getJson('/api/v2/waiter/auth/outlets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'address'],
                ],
            ]);
    }

    public function test_can_login_with_valid_pin(): void
    {
        $response = $this->postJson('/api/v2/waiter/auth/login', [
            'outlet_id' => $this->outlet->id,
            'pin' => '1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                    'outlet' => ['id', 'name'],
                ],
            ]);
    }

    public function test_cannot_login_with_invalid_pin(): void
    {
        $response = $this->postJson('/api/v2/waiter/auth/login', [
            'outlet_id' => $this->outlet->id,
            'pin' => '9999',
        ]);

        $response->assertStatus(401);
    }

    public function test_cannot_login_with_inactive_pin(): void
    {
        $this->waiterPin->update(['is_active' => false]);

        $response = $this->postJson('/api/v2/waiter/auth/login', [
            'outlet_id' => $this->outlet->id,
            'pin' => '1234',
        ]);

        $response->assertStatus(401);
    }

    public function test_can_logout(): void
    {
        $response = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/auth/logout');

        $response->assertStatus(200);
    }

    // ==================== TABLE TESTS ====================

    public function test_can_list_tables(): void
    {
        // Create more tables
        Table::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/tables');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'number',
                        'name',
                        'capacity',
                        'status',
                        'floor',
                        'current_session',
                    ],
                ],
            ])
            ->assertJsonCount(4, 'data');
    }

    public function test_can_filter_tables_by_status(): void
    {
        Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $this->floor->id,
            'status' => Table::STATUS_OCCUPIED,
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/tables?status=occupied');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_tables_by_floor(): void
    {
        $floor2 = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Second Floor',
        ]);

        Table::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor2->id,
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/tables?floor_id='.$floor2->id);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_table_detail(): void
    {
        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/tables/'.$this->table->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'name',
                    'capacity',
                    'status',
                    'floor',
                    'current_session',
                    'current_order',
                ],
            ]);
    }

    public function test_can_open_table(): void
    {
        $response = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/tables/'.$this->table->id.'/open', [
                'guest_count' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'occupied');

        $this->assertDatabaseHas('table_sessions', [
            'table_id' => $this->table->id,
            'guest_count' => 2,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('tables', [
            'id' => $this->table->id,
            'status' => 'occupied',
        ]);
    }

    public function test_cannot_open_occupied_table(): void
    {
        $this->table->markAsOccupied();

        $response = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/tables/'.$this->table->id.'/open', [
                'guest_count' => 2,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_close_table(): void
    {
        // First open the table
        TableSession::openTable($this->table, 2, $this->waiter->id);

        $response = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/tables/'.$this->table->id.'/close');

        $response->assertStatus(200);

        $this->assertDatabaseHas('table_sessions', [
            'table_id' => $this->table->id,
            'status' => 'closed',
        ]);
    }

    public function test_can_update_table_status(): void
    {
        $response = $this->withHeaders($this->waiterHeaders())
            ->patchJson('/api/v2/waiter/tables/'.$this->table->id.'/status', [
                'status' => 'reserved',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'reserved');
    }

    // ==================== MENU TESTS ====================

    public function test_can_list_menu_items(): void
    {
        Product::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/menu');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'category',
                        'variants',
                    ],
                ],
            ])
            ->assertJsonCount(6, 'data'); // 1 from setup + 5 new
    }

    public function test_can_filter_menu_by_category(): void
    {
        $category2 = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Minuman',
        ]);

        Product::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category2->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/menu?category_id='.$category2->id);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_search_menu(): void
    {
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Mie Goreng Seafood',
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/menu?search=goreng');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Nasi Goreng + Mie Goreng
    }

    public function test_can_list_categories(): void
    {
        ProductCategory::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ])
            ->assertJsonCount(4, 'data'); // 1 from setup + 3 new
    }

    // ==================== ORDER TESTS ====================

    public function test_can_create_dine_in_order(): void
    {
        // First open the table
        TableSession::openTable($this->table, 2, $this->waiter->id);

        $response = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders', [
                'table_id' => $this->table->id,
                'order_type' => 'dine_in',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'notes' => 'Extra pedas',
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'order_type',
                    'table',
                    'items',
                    'subtotal',
                    'status',
                ],
            ])
            ->assertJsonPath('data.order_type', 'dine_in');

        // Verify transaction created
        $this->assertDatabaseHas('transactions', [
            'table_id' => $this->table->id,
            'order_type' => 'dine_in',
            'waiter_id' => $this->waiter->id,
        ]);
    }

    public function test_can_create_take_away_order(): void
    {
        $response = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders', [
                'order_type' => 'takeaway',
                'customer_name' => 'Budi',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.order_type', 'takeaway')
            ->assertJsonPath('data.customer_name', 'Budi');
    }

    public function test_cannot_create_dine_in_order_without_table(): void
    {
        $response = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders', [
                'order_type' => 'dine_in',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_can_list_waiter_orders(): void
    {
        // Create some orders
        $this->test_can_create_take_away_order();

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'order_type',
                        'status',
                        'items_count',
                        'total',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_can_filter_orders_by_status(): void
    {
        // Create order first
        $this->test_can_create_take_away_order();

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/orders?status=pending');

        $response->assertStatus(200);
    }

    public function test_can_get_order_detail(): void
    {
        // Create order first
        TableSession::openTable($this->table, 2, $this->waiter->id);

        $createResponse = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders', [
                'table_id' => $this->table->id,
                'order_type' => 'dine_in',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $orderId = $createResponse->json('data.id');

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/orders/'.$orderId);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'order_type',
                    'table',
                    'items' => [
                        '*' => [
                            'id',
                            'product_name',
                            'quantity',
                            'unit_price',
                            'subtotal',
                            'notes',
                        ],
                    ],
                    'subtotal',
                    'tax',
                    'total',
                    'status',
                    'kitchen_status',
                ],
            ]);
    }

    public function test_can_add_items_to_existing_order(): void
    {
        // Create initial order
        TableSession::openTable($this->table, 2, $this->waiter->id);

        $createResponse = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders', [
                'table_id' => $this->table->id,
                'order_type' => 'dine_in',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $orderId = $createResponse->json('data.id');

        // Create another product
        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Es Teh',
            'base_price' => 8000,
            'is_active' => true,
        ]);

        // Add more items
        $response = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders/'.$orderId.'/items', [
                'items' => [
                    [
                        'product_id' => $product2->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertStatus(200);

        // Verify items count
        $this->assertDatabaseCount('transaction_items', 2);
    }

    public function test_can_send_order_to_kitchen(): void
    {
        // Create order first
        TableSession::openTable($this->table, 2, $this->waiter->id);

        $createResponse = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders', [
                'table_id' => $this->table->id,
                'order_type' => 'dine_in',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $orderId = $createResponse->json('data.id');

        $response = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders/'.$orderId.'/send');

        $response->assertStatus(200)
            ->assertJsonPath('data.kitchen_status', 'pending');

        // Verify kitchen order created
        $this->assertDatabaseHas('kitchen_orders', [
            'transaction_id' => $orderId,
            'status' => 'pending',
        ]);
    }

    public function test_can_mark_order_as_picked_up(): void
    {
        // Create and send order
        TableSession::openTable($this->table, 2, $this->waiter->id);

        $createResponse = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders', [
                'table_id' => $this->table->id,
                'order_type' => 'dine_in',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $orderId = $createResponse->json('data.id');

        // Send to kitchen
        $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders/'.$orderId.'/send');

        // Simulate kitchen ready
        $kitchenOrder = KitchenOrder::where('transaction_id', $orderId)->first();
        $kitchenOrder->update(['status' => KitchenOrder::STATUS_READY]);

        // Mark as picked up
        $response = $this->withHeaders($this->waiterHeaders())
            ->patchJson('/api/v2/waiter/orders/'.$orderId.'/pickup');

        $response->assertStatus(200)
            ->assertJsonPath('data.kitchen_status', 'served');

        $this->assertDatabaseHas('kitchen_orders', [
            'transaction_id' => $orderId,
            'status' => 'served',
        ]);
    }

    public function test_kitchen_order_auto_created_on_send(): void
    {
        TableSession::openTable($this->table, 2, $this->waiter->id);

        $createResponse = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders', [
                'table_id' => $this->table->id,
                'order_type' => 'dine_in',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 3,
                        'notes' => 'No onion',
                    ],
                ],
            ]);

        $createResponse->assertStatus(201);
        $orderId = $createResponse->json('data.id');
        $this->assertNotNull($orderId, 'Order ID should not be null');

        // Before send - no kitchen order
        $this->assertDatabaseMissing('kitchen_orders', [
            'transaction_id' => $orderId,
        ]);

        // Send to kitchen
        $sendResponse = $this->withHeaders($this->waiterHeaders())
            ->postJson('/api/v2/waiter/orders/'.$orderId.'/send');

        $sendResponse->assertStatus(200);

        // After send - kitchen order exists
        $this->assertDatabaseHas('kitchen_orders', [
            'transaction_id' => $orderId,
            'status' => 'pending',
        ]);

        // Kitchen order items created
        $kitchenOrder = KitchenOrder::where('transaction_id', $orderId)->first();
        $this->assertNotNull($kitchenOrder, 'Kitchen order should exist');
        $this->assertCount(1, $kitchenOrder->items);
        $this->assertEquals('No onion', $kitchenOrder->items->first()->notes);
    }

    // ==================== FLOORS TEST ====================

    public function test_can_list_floors(): void
    {
        Floor::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => fn () => 'Floor '.fake()->unique()->randomNumber(4),
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/v2/waiter/floors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'tables_count'],
                ],
            ])
            ->assertJsonCount(3, 'data'); // 1 from setup + 2 new
    }
}
