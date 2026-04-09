<?php

namespace Tests\Feature\Api\V1;

use App\Models\KitchenOrder;
use App\Models\KitchenStation;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenOrderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private Outlet $outlet;

    private PosSession $session;

    private Product $product;

    private PaymentMethod $paymentMethod;

    private KitchenStation $station;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_percentage' => 0,
            'service_charge_percentage' => 0,
        ]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->outlets()->attach($this->outlet->id, ['is_default' => true]);

        $this->session = PosSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => PosSession::STATUS_OPEN,
        ]);

        $this->category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 50000,
            'is_active' => true,
        ]);

        $this->paymentMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create a kitchen station
        $this->station = KitchenStation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Main Kitchen',
            'code' => 'MAIN',
            'is_active' => true,
        ]);
    }

    private function checkout(array $data)
    {
        return $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', $data);
    }

    public function test_kitchen_order_created_on_dine_in_checkout(): void
    {
        $response = $this->checkout([
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
            'order_type' => 'dine_in',
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 100000,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $transactionId = $response->json('data.id');

        // Verify kitchen order was created
        $kitchenOrder = KitchenOrder::where('transaction_id', $transactionId)->first();

        $this->assertNotNull($kitchenOrder);
        $this->assertEquals('pending', $kitchenOrder->status);
        $this->assertEquals('dine_in', $kitchenOrder->order_type);
        $this->assertEquals($this->station->id, $kitchenOrder->station_id);

        // Verify kitchen order items
        $this->assertCount(1, $kitchenOrder->items);
        $this->assertEquals($this->product->name, $kitchenOrder->items->first()->item_name);
        $this->assertEquals(2, $kitchenOrder->items->first()->quantity);
    }

    public function test_kitchen_order_created_on_takeaway_checkout(): void
    {
        $response = $this->checkout([
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
            'order_type' => 'takeaway',
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 50000,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $transactionId = $response->json('data.id');

        // Verify kitchen order was created
        $kitchenOrder = KitchenOrder::where('transaction_id', $transactionId)->first();

        $this->assertNotNull($kitchenOrder);
        $this->assertEquals('takeaway', $kitchenOrder->order_type);
    }

    public function test_kitchen_order_not_created_for_delivery(): void
    {
        $response = $this->checkout([
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
            'order_type' => 'delivery',
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 50000,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $transactionId = $response->json('data.id');

        // Verify no kitchen order was created for delivery
        $kitchenOrder = KitchenOrder::where('transaction_id', $transactionId)->first();

        $this->assertNull($kitchenOrder);
    }

    public function test_kitchen_order_not_created_without_station(): void
    {
        // Delete the station
        $this->station->delete();

        $response = $this->checkout([
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
            'order_type' => 'dine_in',
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 50000,
                ],
            ],
        ]);

        // Transaction should still succeed
        $response->assertStatus(201);

        $transactionId = $response->json('data.id');

        // But no kitchen order should be created
        $kitchenOrder = KitchenOrder::where('transaction_id', $transactionId)->first();

        $this->assertNull($kitchenOrder);
    }

    public function test_kitchen_order_includes_item_notes(): void
    {
        $response = $this->checkout([
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'notes' => 'Extra spicy please',
                ],
            ],
            'order_type' => 'dine_in',
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 50000,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $transactionId = $response->json('data.id');
        $kitchenOrder = KitchenOrder::where('transaction_id', $transactionId)->first();

        $this->assertNotNull($kitchenOrder);
        $this->assertEquals('Extra spicy please', $kitchenOrder->items->first()->notes);
    }

    public function test_kitchen_order_visible_in_kds(): void
    {
        // Create transaction via checkout
        $response = $this->checkout([
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
            'order_type' => 'dine_in',
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 100000,
                ],
            ],
        ]);

        $response->assertStatus(201);

        // Now fetch KDS orders
        $kdsResponse = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v2/kds/orders');

        $kdsResponse->assertStatus(200);
        $kdsResponse->assertJsonCount(1, 'data');
        $kdsResponse->assertJsonPath('data.0.status', 'pending');
    }

    public function test_multiple_items_create_multiple_kitchen_order_items(): void
    {
        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'base_price' => 30000,
            'is_active' => true,
        ]);

        $response = $this->checkout([
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 2,
                ],
            ],
            'order_type' => 'dine_in',
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 110000,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $transactionId = $response->json('data.id');
        $kitchenOrder = KitchenOrder::where('transaction_id', $transactionId)->with('items')->first();

        $this->assertNotNull($kitchenOrder);
        $this->assertCount(2, $kitchenOrder->items);
    }
}
