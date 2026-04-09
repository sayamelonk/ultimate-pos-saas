<?php

namespace Tests\Feature\Api\V2;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Outlet $outlet;

    private PosSession $session;

    private PaymentMethod $paymentMethod;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_percentage' => 10,
            'service_charge_percentage' => 5,
        ]);
        $this->session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);
        $this->paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'cash',
        ]);

        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'base_price' => 50000,
            'cost_price' => 30000,
        ]);
    }

    // ==================== GET /orders ====================

    /** @test */
    public function can_list_orders(): void
    {
        Transaction::factory()->count(3)->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/orders', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'transaction_number',
                        'type',
                        'order_type',
                        'grand_total',
                        'status',
                        'created_at',
                    ],
                ],
                'meta',
            ]);
    }

    /** @test */
    public function guest_cannot_list_orders(): void
    {
        $response = $this->getJson('/api/v2/orders', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function orders_list_requires_outlet(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/orders');

        $response->assertStatus(400);
    }

    /** @test */
    public function can_filter_orders_by_session(): void
    {
        Transaction::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        $otherSession = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $otherSession->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/orders?session_id={$this->session->id}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function can_filter_orders_by_status(): void
    {
        Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->voided()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/orders?status=completed', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function only_tenant_orders_are_returned(): void
    {
        Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        $otherTenant = Tenant::factory()->create();
        $otherOutlet = Outlet::factory()->create(['tenant_id' => $otherTenant->id]);
        Transaction::factory()->create([
            'tenant_id' => $otherTenant->id,
            'outlet_id' => $otherOutlet->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/orders', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    // ==================== POST /orders/calculate ====================

    /** @test */
    public function can_calculate_cart(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'items',
                    'items_count',
                    'subtotal',
                    'discount_amount',
                    'tax_percentage',
                    'tax_amount',
                    'service_charge_percentage',
                    'service_charge_amount',
                    'grand_total',
                ],
            ]);

        // 2 x 50000 = 100000 subtotal
        $this->assertEquals(100000, $response->json('data.subtotal'));
    }

    /** @test */
    public function guest_cannot_calculate_cart(): void
    {
        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function calculate_applies_order_discount(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        // 100000 - 10% = 90000, then tax/service applied
        $this->assertEquals(10000, $response->json('data.discount_amount'));
    }

    /** @test */
    public function calculate_applies_item_discount(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/calculate', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'discount_amount' => 5000,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        // (50000 * 2) - 5000 = 95000
        $this->assertEquals(95000, $response->json('data.subtotal'));
    }

    // ==================== POST /orders/checkout ====================

    /** @test */
    public function can_checkout_order(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/checkout', [
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
                    'amount' => 60000,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'transaction_number',
                    'type',
                    'order_type',
                    'subtotal',
                    'grand_total',
                    'payment_amount',
                    'change_amount',
                    'status',
                    'items',
                    'payments',
                ],
            ]);

        $this->assertDatabaseHas('transactions', [
            'outlet_id' => $this->outlet->id,
            'type' => Transaction::TYPE_SALE,
            'status' => Transaction::STATUS_COMPLETED,
        ]);
    }

    /** @test */
    public function guest_cannot_checkout(): void
    {
        $response = $this->postJson('/api/v2/orders/checkout', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
            'order_type' => 'dine_in',
            'payments' => [
                ['payment_method_id' => $this->paymentMethod->id, 'amount' => 60000],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function checkout_requires_active_session(): void
    {
        $this->session->update(['status' => PosSession::STATUS_CLOSED]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/checkout', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
            'order_type' => 'dine_in',
            'payments' => [
                ['payment_method_id' => $this->paymentMethod->id, 'amount' => 60000],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'No active POS session. Please open a session first.']);
    }

    /** @test */
    public function checkout_requires_sufficient_payment(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/checkout', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
            'order_type' => 'dine_in',
            'payments' => [
                ['payment_method_id' => $this->paymentMethod->id, 'amount' => 1000],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function checkout_creates_transaction_items(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/checkout', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
            'order_type' => 'takeaway',
            'payments' => [
                ['payment_method_id' => $this->paymentMethod->id, 'amount' => 120000],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertCreated();

        $transactionId = $response->json('data.id');

        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transactionId,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    /** @test */
    public function checkout_creates_transaction_payments(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/checkout', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
            'order_type' => 'dine_in',
            'payments' => [
                ['payment_method_id' => $this->paymentMethod->id, 'amount' => 60000],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertCreated();

        $transactionId = $response->json('data.id');

        $this->assertDatabaseHas('transaction_payments', [
            'transaction_id' => $transactionId,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 60000,
        ]);
    }

    /** @test */
    public function checkout_supports_split_payment(): void
    {
        $otherPaymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'card',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/checkout', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
            'order_type' => 'dine_in',
            'payments' => [
                ['payment_method_id' => $this->paymentMethod->id, 'amount' => 60000],
                ['payment_method_id' => $otherPaymentMethod->id, 'amount' => 60000],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertCreated();
        $this->assertCount(2, $response->json('data.payments'));
    }

    // ==================== GET /orders/{order} ====================

    /** @test */
    public function can_get_order_detail(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/orders/{$transaction->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'transaction_number',
                    'type',
                    'order_type',
                    'subtotal',
                    'grand_total',
                    'status',
                    'items',
                    'payments',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_order_detail(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->getJson("/api/v2/orders/{$transaction->id}");

        $response->assertUnauthorized();
    }

    /** @test */
    public function cannot_get_other_tenant_order(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherOutlet = Outlet::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherTransaction = Transaction::factory()->create([
            'tenant_id' => $otherTenant->id,
            'outlet_id' => $otherOutlet->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/orders/{$otherTransaction->id}");

        $response->assertNotFound();
    }

    // ==================== POST /orders/{order}/void ====================

    /** @test */
    public function can_void_order(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/void", [
            'reason' => 'Customer cancelled',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => Transaction::STATUS_VOIDED,
        ]);
    }

    /** @test */
    public function guest_cannot_void_order(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/void", [
            'reason' => 'Test',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function void_requires_reason(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/void", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function cannot_void_already_voided_order(): void
    {
        $transaction = Transaction::factory()->voided()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/void", [
            'reason' => 'Test',
        ]);

        $response->assertStatus(400);
    }

    // ==================== POST /orders/{order}/refund ====================

    /** @test */
    public function can_refund_order(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/refund", [
            'amount' => 50000,
            'reason' => 'Partial refund',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'transaction_number',
                    'type',
                    'grand_total',
                ],
            ]);

        $this->assertDatabaseHas('transactions', [
            'original_transaction_id' => $transaction->id,
            'type' => Transaction::TYPE_REFUND,
            'grand_total' => 50000,
        ]);
    }

    /** @test */
    public function guest_cannot_refund_order(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/refund", [
            'amount' => 50000,
            'reason' => 'Test',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function refund_requires_amount_and_reason(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/refund", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'reason', 'payment_method_id']);
    }

    /** @test */
    public function cannot_refund_more_than_order_total(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/refund", [
            'amount' => 150000,
            'reason' => 'Test',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertUnprocessable();
    }

    // ==================== GET /orders/{order}/receipt ====================

    /** @test */
    public function can_get_receipt_data(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/orders/{$transaction->id}/receipt");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'transaction',
                    'outlet' => [
                        'name',
                        'address',
                        'phone',
                        'receipt_header',
                        'receipt_footer',
                    ],
                    'print_time',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_receipt(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $response = $this->getJson("/api/v2/orders/{$transaction->id}/receipt");

        $response->assertUnauthorized();
    }
}
