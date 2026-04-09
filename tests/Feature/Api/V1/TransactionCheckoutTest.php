<?php

namespace Tests\Feature\Api\V1;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected Outlet $outlet;

    protected PosSession $session;

    protected PaymentMethod $cashMethod;

    protected ProductCategory $category;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_percentage' => 11,
            'service_charge_percentage' => 5,
        ]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->outlets()->attach($this->outlet->id, ['is_default' => true]);

        // Create open session
        $this->session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => PosSession::STATUS_OPEN,
        ]);

        // Create cash payment method
        $this->cashMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create category and product
        $this->category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Nasi Goreng',
            'sku' => 'NG001',
            'base_price' => 25000,
            'cost_price' => 15000,
            'is_active' => true,
            'show_in_pos' => true,
        ]);
    }

    // ==================== CHECKOUT ====================

    /** @test */
    public function user_can_checkout_simple_transaction(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
                'order_type' => 'takeaway',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 100000,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'transaction_number',
                    'type',
                    'order_type',
                    'subtotal',
                    'tax_amount',
                    'grand_total',
                    'payment_amount',
                    'change_amount',
                    'status',
                    'items',
                    'payments',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'sale',
                    'order_type' => 'takeaway',
                    'status' => 'completed',
                ],
            ]);

        $this->assertDatabaseHas('transactions', [
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'pos_session_id' => $this->session->id,
            'type' => 'sale',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function transaction_calculates_subtotal_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 3, // 3 x 25000 = 75000
                    ],
                ],
                'order_type' => 'takeaway',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 100000,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertEquals(75000, $response->json('data.subtotal'));
    }

    /** @test */
    public function transaction_calculates_tax_and_service_charge(): void
    {
        // Subtotal: 25000 x 2 = 50000
        // Tax 11%: 5500
        // Service 5%: 2500
        // Grand total before rounding: 58000

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
                'order_type' => 'dine_in',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 60000,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertEquals(50000, $response->json('data.subtotal'));
        $this->assertEquals(5500, $response->json('data.tax_amount'));
        $this->assertEquals(2500, $response->json('data.service_charge_amount'));
    }

    /** @test */
    public function transaction_number_is_generated_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
                'order_type' => 'takeaway',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 50000,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $transactionNumber = $response->json('data.transaction_number');
        // Format: TRX{YYYYMMDD}{0001}
        $this->assertMatchesRegularExpression('/^TRX\d{8}\d{4}$/', $transactionNumber);
    }

    /** @test */
    public function checkout_requires_active_session(): void
    {
        // Close session
        $this->session->update(['status' => PosSession::STATUS_CLOSED]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
                'order_type' => 'takeaway',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 50000,
                    ],
                ],
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function checkout_requires_outlet_header(): void
    {
        $userNoOutlet = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($userNoOutlet)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
                'order_type' => 'takeaway',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 50000,
                    ],
                ],
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function checkout_validates_minimum_items(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [],
                'order_type' => 'takeaway',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 50000,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function checkout_validates_order_type(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
                'order_type' => 'invalid_type',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 50000,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_type']);
    }

    /** @test */
    public function checkout_validates_payment_amount_sufficient(): void
    {
        // Product: 25000, with tax/service = ~29000
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
                'order_type' => 'takeaway',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 1000, // Too low
                    ],
                ],
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function checkout_calculates_change_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
                'order_type' => 'takeaway',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 50000,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $grandTotal = $response->json('data.grand_total');
        $paymentAmount = $response->json('data.payment_amount');
        $change = $response->json('data.change_amount');

        $this->assertEquals(50000, $paymentAmount);
        $this->assertEquals($paymentAmount - $grandTotal, $change);
    }

    /** @test */
    public function checkout_creates_transaction_items(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'notes' => 'Extra pedas',
                    ],
                ],
                'order_type' => 'takeaway',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 100000,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        $this->assertEquals($this->product->id, $items[0]['product_id']);
        $this->assertEquals(2, $items[0]['quantity']);
        $this->assertEquals('Extra pedas', $items[0]['notes']);
    }

    /** @test */
    public function checkout_creates_payment_records(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/checkout', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
                'order_type' => 'takeaway',
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 50000,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $payments = $response->json('data.payments');
        $this->assertCount(1, $payments);
        $this->assertEquals($this->cashMethod->id, $payments[0]['payment_method_id']);
        $this->assertEquals(50000, $payments[0]['amount']);
    }

    /** @test */
    public function guest_cannot_checkout(): void
    {
        $response = $this->postJson('/api/v1/transactions/checkout', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
            'order_type' => 'takeaway',
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 50000,
                ],
            ],
        ]);

        $response->assertUnauthorized();
    }

    // ==================== CALCULATE ====================

    /** @test */
    public function user_can_calculate_cart_preview(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/calculate', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
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

        $this->assertEquals(50000, $response->json('data.subtotal'));
        $this->assertEquals(1, $response->json('data.items_count')); // 1 line item (with qty 2)
    }

    /** @test */
    public function calculate_applies_order_discount_percentage(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/calculate', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 4, // 100000
                    ],
                ],
                'discount_type' => 'percentage',
                'discount_value' => 10, // 10% = 10000
            ]);

        $response->assertOk();

        $this->assertEquals(100000, $response->json('data.subtotal'));
        $this->assertEquals(10000, $response->json('data.discount_amount'));
        $this->assertEquals(90000, $response->json('data.after_discount'));
    }

    /** @test */
    public function calculate_applies_order_discount_fixed(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/transactions/calculate', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 4, // 100000
                    ],
                ],
                'discount_type' => 'fixed',
                'discount_value' => 15000,
            ]);

        $response->assertOk();

        $this->assertEquals(100000, $response->json('data.subtotal'));
        $this->assertEquals(15000, $response->json('data.discount_amount'));
        $this->assertEquals(85000, $response->json('data.after_discount'));
    }

    // ==================== TRANSACTION DETAIL ====================

    /** @test */
    public function user_can_get_transaction_detail(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'pos_session_id' => $this->session->id,
            'status' => Transaction::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/transactions/{$transaction->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $transaction->id,
                ],
            ]);
    }

    /** @test */
    public function cannot_access_other_tenant_transaction(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherOutlet = Outlet::factory()->create(['tenant_id' => $otherTenant->id]);

        $transaction = Transaction::factory()->create([
            'tenant_id' => $otherTenant->id,
            'outlet_id' => $otherOutlet->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/transactions/{$transaction->id}");

        $response->assertNotFound();
    }

    // ==================== TRANSACTION LIST ====================

    /** @test */
    public function user_can_list_transactions(): void
    {
        Transaction::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'pos_session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/transactions');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function transaction_list_filters_by_session(): void
    {
        // Create transactions in current session
        Transaction::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'pos_session_id' => $this->session->id,
        ]);

        // Create transactions in different session
        $otherSession = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'pos_session_id' => $otherSession->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson("/api/v1/transactions?session_id={$this->session->id}");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }
}
