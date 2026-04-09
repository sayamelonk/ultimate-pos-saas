<?php

namespace Tests\Feature\Api\V2;

use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\KitchenStation;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KDSApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private Outlet $outlet;

    private PosSession $session;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->session = PosSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'open',
        ]);

        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'base_price' => 25000,
        ]);

        Sanctum::actingAs($this->user);
    }

    // ==================== KDS Auth ====================

    public function test_can_login_to_kds_with_valid_pin(): void
    {
        // Create user with PIN for KDS
        $kitchenUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create UserPin
        UserPin::create([
            'user_id' => $kitchenUser->id,
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
        ]);

        // KDS login is outlet-scoped, requires outlet_id
        $response = $this->postJson('/api/v2/kds/auth/login', [
            'outlet_id' => $this->outlet->id,
            'pin' => '1234',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                    ],
                    'outlet' => [
                        'id',
                        'name',
                    ],
                ],
            ]);
    }

    public function test_kds_login_fails_with_invalid_pin(): void
    {
        $kitchenUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        UserPin::create([
            'user_id' => $kitchenUser->id,
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v2/kds/auth/login', [
            'outlet_id' => $this->outlet->id,
            'pin' => '9999',
        ]);

        $response->assertStatus(401);
    }

    public function test_can_get_kds_outlets_list(): void
    {
        // This endpoint doesn't require auth - for outlet selection before login
        $response = $this->getJson('/api/v2/kds/auth/outlets');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
            ]);
    }

    // ==================== Kitchen Orders List ====================

    public function test_can_list_kitchen_orders(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder();

        $response = $this->getJson('/api/v2/kds/orders', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'order_type',
                        'table_name',
                        'status',
                        'priority',
                        'items',
                        'created_at',
                        'elapsed_time',
                    ],
                ],
            ]);
    }

    public function test_can_filter_kitchen_orders_by_status(): void
    {
        $this->createTransactionWithKitchenOrder('pending');
        $this->createTransactionWithKitchenOrder('preparing');
        $this->createTransactionWithKitchenOrder('ready');

        $response = $this->getJson('/api/v2/kds/orders?status=pending', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $data = $response->json('data');

        foreach ($data as $order) {
            $this->assertEquals('pending', $order['status']);
        }
    }

    public function test_can_filter_kitchen_orders_by_station(): void
    {
        $station = KitchenStation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Grill Station',
        ]);

        $this->createTransactionWithKitchenOrder('pending', $station->id);

        $response = $this->getJson("/api/v2/kds/orders?station_id={$station->id}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
    }

    // ==================== Kitchen Order Detail ====================

    public function test_can_get_kitchen_order_detail(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder();
        $kitchenOrder = $transaction->kitchenOrder;

        $response = $this->getJson("/api/v2/kds/orders/{$kitchenOrder->id}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'order_number',
                    'order_type',
                    'table_name',
                    'customer_name',
                    'status',
                    'priority',
                    'notes',
                    'items' => [
                        '*' => [
                            'id',
                            'item_name',
                            'quantity',
                            'modifiers',
                            'notes',
                            'status',
                        ],
                    ],
                    'created_at',
                    'started_at',
                    'completed_at',
                    'elapsed_time',
                ],
            ]);
    }

    // ==================== Update Order Status ====================

    public function test_can_start_preparing_order(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder('pending');
        $kitchenOrder = $transaction->kitchenOrder;

        $response = $this->postJson("/api/v2/kds/orders/{$kitchenOrder->id}/start", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'preparing',
                ],
            ]);

        $this->assertDatabaseHas('kitchen_orders', [
            'id' => $kitchenOrder->id,
            'status' => 'preparing',
        ]);
    }

    public function test_can_mark_order_as_ready(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder('preparing');
        $kitchenOrder = $transaction->kitchenOrder;

        $response = $this->postJson("/api/v2/kds/orders/{$kitchenOrder->id}/ready", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'ready',
                ],
            ]);

        $this->assertDatabaseHas('kitchen_orders', [
            'id' => $kitchenOrder->id,
            'status' => 'ready',
        ]);
    }

    public function test_can_mark_order_as_served(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder('ready');
        $kitchenOrder = $transaction->kitchenOrder;

        $response = $this->postJson("/api/v2/kds/orders/{$kitchenOrder->id}/served", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'served',
                ],
            ]);
    }

    public function test_can_cancel_kitchen_order(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder('pending');
        $kitchenOrder = $transaction->kitchenOrder;

        $response = $this->postJson("/api/v2/kds/orders/{$kitchenOrder->id}/cancel", [
            'reason' => 'Customer cancelled',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);
    }

    public function test_cannot_start_already_preparing_order(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder('preparing');
        $kitchenOrder = $transaction->kitchenOrder;

        $response = $this->postJson("/api/v2/kds/orders/{$kitchenOrder->id}/start", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422);
    }

    // ==================== Update Item Status ====================

    public function test_can_mark_item_as_preparing(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder('preparing');
        $kitchenOrder = $transaction->kitchenOrder;
        $item = $kitchenOrder->items->first();

        $response = $this->postJson("/api/v2/kds/orders/{$kitchenOrder->id}/items/{$item->id}/start", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'preparing',
                ],
            ]);
    }

    public function test_can_mark_item_as_ready(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder('preparing');
        $kitchenOrder = $transaction->kitchenOrder;
        $item = $kitchenOrder->items->first();
        $item->update(['status' => 'preparing']);

        $response = $this->postJson("/api/v2/kds/orders/{$kitchenOrder->id}/items/{$item->id}/ready", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'ready',
                ],
            ]);
    }

    public function test_order_auto_ready_when_all_items_ready(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder('preparing');
        $kitchenOrder = $transaction->kitchenOrder;

        // Mark all items as ready
        foreach ($kitchenOrder->items as $item) {
            $item->update(['status' => 'preparing']);
            $this->postJson("/api/v2/kds/orders/{$kitchenOrder->id}/items/{$item->id}/ready", [], [
                'X-Outlet-Id' => $this->outlet->id,
            ]);
        }

        $kitchenOrder->refresh();
        $this->assertEquals('ready', $kitchenOrder->status);
    }

    // ==================== Recall Order ====================

    public function test_can_recall_served_order(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder('served');
        $kitchenOrder = $transaction->kitchenOrder;

        $response = $this->postJson("/api/v2/kds/orders/{$kitchenOrder->id}/recall", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'ready',
                ],
            ]);
    }

    // ==================== Bump Order ====================

    public function test_can_bump_order_to_next_status(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder('pending');
        $kitchenOrder = $transaction->kitchenOrder;

        $response = $this->postJson("/api/v2/kds/orders/{$kitchenOrder->id}/bump", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();

        $kitchenOrder->refresh();
        $this->assertEquals('preparing', $kitchenOrder->status);
    }

    // ==================== Priority ====================

    public function test_can_set_order_priority(): void
    {
        $transaction = $this->createTransactionWithKitchenOrder('pending');
        $kitchenOrder = $transaction->kitchenOrder;

        $response = $this->postJson("/api/v2/kds/orders/{$kitchenOrder->id}/priority", [
            'priority' => 'rush',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'priority' => 'rush',
                ],
            ]);
    }

    // ==================== Kitchen Stations ====================

    public function test_can_list_kitchen_stations(): void
    {
        KitchenStation::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->getJson('/api/v2/kds/stations', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'color',
                        'is_active',
                        'pending_orders_count',
                    ],
                ],
            ]);
    }

    public function test_can_create_kitchen_station(): void
    {
        $response = $this->postJson('/api/v2/kds/stations', [
            'name' => 'Grill Station',
            'code' => 'GRILL',
            'color' => '#FF5733',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Grill Station',
                    'code' => 'GRILL',
                ],
            ]);
    }

    public function test_can_update_kitchen_station(): void
    {
        $station = KitchenStation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->putJson("/api/v2/kds/stations/{$station->id}", [
            'name' => 'Updated Station',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Station',
                ],
            ]);
    }

    public function test_can_delete_kitchen_station(): void
    {
        $station = KitchenStation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->deleteJson("/api/v2/kds/stations/{$station->id}", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertSoftDeleted('kitchen_stations', ['id' => $station->id]);
    }

    // ==================== KDS Stats ====================

    public function test_can_get_kds_stats(): void
    {
        $this->createTransactionWithKitchenOrder('pending');
        $this->createTransactionWithKitchenOrder('preparing');
        $this->createTransactionWithKitchenOrder('ready');

        $response = $this->getJson('/api/v2/kds/stats', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'pending_count',
                    'preparing_count',
                    'ready_count',
                    'served_today',
                    'avg_preparation_time',
                    'orders_by_hour',
                ],
            ]);
    }

    // ==================== Auto-create Kitchen Order ====================

    /**
     * This test should be in OrderCheckoutTest, not KDSApiTest
     * The auto-create kitchen order logic is tested through integration tests
     */
    public function test_kitchen_order_auto_created_on_checkout(): void
    {
        $this->markTestSkipped('Kitchen order auto-creation is tested in OrderCheckoutTest');
    }

    // ==================== Helper Methods ====================

    private function createTransactionWithKitchenOrder(string $status = 'pending', ?string $stationId = null): Transaction
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'order_type' => 'dine_in',
            'status' => 'completed',
        ]);

        $transactionItem = TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'item_name' => $this->product->name,
            'quantity' => 2,
            'unit_price' => 25000,
            'subtotal' => 50000,
        ]);

        $kitchenOrder = KitchenOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'transaction_id' => $transaction->id,
            'order_number' => $transaction->transaction_number,
            'status' => $status,
            'station_id' => $stationId,
            'started_at' => $status !== 'pending' ? now() : null,
            'completed_at' => in_array($status, ['ready', 'served']) ? now() : null,
        ]);

        KitchenOrderItem::factory()->create([
            'kitchen_order_id' => $kitchenOrder->id,
            'transaction_item_id' => $transactionItem->id,
            'item_name' => $transactionItem->item_name,
            'quantity' => $transactionItem->quantity,
            'status' => $status === 'pending' ? 'pending' : ($status === 'preparing' ? 'pending' : 'ready'),
        ]);

        // Refresh to get relationship
        $transaction->load('kitchenOrder.items');

        return $transaction;
    }
}
