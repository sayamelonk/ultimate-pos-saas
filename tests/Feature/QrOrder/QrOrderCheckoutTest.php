<?php

namespace Tests\Feature\QrOrder;

use App\Models\Floor;
use App\Models\KitchenStation;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QrOrder;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrOrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Outlet $outlet;

    private Table $table;

    private Product $product;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        SubscriptionPlan::factory()->professional()->create();
        $proPlan = SubscriptionPlan::where('slug', 'professional')->first();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $proPlan->id,
        ]);

        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);
        $this->table->generateQrToken();

        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'base_price' => 25000,
            'is_active' => true,
            'show_in_menu' => true,
        ]);

        // Create kitchen station for KDS integration
        KitchenStation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
        ]);
    }

    public function test_can_place_pay_at_counter_order(): void
    {
        $response = $this->postJson("/qr/{$this->table->qr_token}/order", [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'notes' => 'Extra spicy',
            'payment_method' => 'pay_at_counter',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'order' => ['id', 'order_number', 'grand_total', 'status_url'],
            ]);

        $this->assertDatabaseHas('qr_orders', [
            'table_id' => $this->table->id,
            'customer_name' => 'John Doe',
            'status' => QrOrder::STATUS_PAY_AT_COUNTER,
            'payment_method' => 'pay_at_counter',
        ]);

        // Verify QR order items created
        $qrOrder = QrOrder::where('table_id', $this->table->id)->first();
        $this->assertCount(1, $qrOrder->items);
        $this->assertEquals($this->product->id, $qrOrder->items->first()->product_id);
        $this->assertEquals(2, $qrOrder->items->first()->quantity);
    }

    public function test_pay_at_counter_creates_kitchen_order(): void
    {
        $this->postJson("/qr/{$this->table->qr_token}/order", [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'pay_at_counter',
        ]);

        // KitchenOrder should be created immediately for pay-at-counter
        $this->assertDatabaseHas('kitchen_orders', [
            'outlet_id' => $this->outlet->id,
            'table_id' => $this->table->id,
        ]);
    }

    public function test_order_fails_with_empty_items(): void
    {
        $response = $this->postJson("/qr/{$this->table->qr_token}/order", [
            'items' => [],
            'payment_method' => 'pay_at_counter',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_order_fails_with_invalid_product(): void
    {
        $response = $this->postJson("/qr/{$this->table->qr_token}/order", [
            'items' => [
                [
                    'product_id' => '00000000-0000-0000-0000-000000000000',
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'pay_at_counter',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_order_fails_with_invalid_payment_method(): void
    {
        $response = $this->postJson("/qr/{$this->table->qr_token}/order", [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'bitcoin',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_order_calculates_totals_correctly(): void
    {
        $this->postJson("/qr/{$this->table->qr_token}/order", [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                ],
            ],
            'payment_method' => 'pay_at_counter',
        ]);

        $qrOrder = QrOrder::where('table_id', $this->table->id)->first();

        // 3 x 25000 = 75000 subtotal
        $this->assertEquals(75000, (float) $qrOrder->subtotal);
    }

    public function test_order_generates_unique_order_number(): void
    {
        // Place two orders
        $this->postJson("/qr/{$this->table->qr_token}/order", [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payment_method' => 'pay_at_counter',
        ]);

        $this->postJson("/qr/{$this->table->qr_token}/order", [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payment_method' => 'pay_at_counter',
        ]);

        $orders = QrOrder::where('table_id', $this->table->id)->pluck('order_number');
        $this->assertCount(2, $orders);
        $this->assertCount(2, $orders->unique());

        // Verify QR order number format
        foreach ($orders as $number) {
            $this->assertStringStartsWith('QR-', $number);
        }
    }

    public function test_can_view_order_status_page(): void
    {
        $this->postJson("/qr/{$this->table->qr_token}/order", [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payment_method' => 'pay_at_counter',
        ]);

        $qrOrder = QrOrder::where('table_id', $this->table->id)->first();

        $response = $this->get("/qr/order/{$qrOrder->id}/status");

        $response->assertOk();
        $response->assertViewIs('qr-menu.order-status');
        $response->assertViewHas('order');
    }

    public function test_can_poll_order_status_json(): void
    {
        $this->postJson("/qr/{$this->table->qr_token}/order", [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payment_method' => 'pay_at_counter',
        ]);

        $qrOrder = QrOrder::where('table_id', $this->table->id)->first();

        $response = $this->getJson("/qr/order/{$qrOrder->id}/status.json");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'order_number', 'status', 'grand_total'],
            ]);
    }
}
