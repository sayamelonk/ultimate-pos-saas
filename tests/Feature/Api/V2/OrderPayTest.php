<?php

namespace Tests\Feature\Api\V2;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderPayTest extends TestCase
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

    private function createPendingWaiterOrder(): Transaction
    {
        $transaction = Transaction::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'waiter_id' => $this->user->id,
            'transaction_number' => 'WTR-20260303-0001',
            'type' => 'sale',
            'order_type' => 'dine_in',
            'subtotal' => 50000,
            'tax_amount' => 5000,
            'grand_total' => 55000,
            'payment_amount' => 0,
            'status' => 'pending',
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'item_name' => $this->product->name,
            'item_sku' => $this->product->sku ?? $this->product->id,
            'unit_name' => 'pcs',
            'quantity' => 1,
            'unit_price' => 50000,
            'base_price' => 50000,
            'cost_price' => 30000,
            'subtotal' => 50000,
        ]);

        return $transaction;
    }

    /** @test */
    public function can_pay_pending_waiter_order(): void
    {
        $transaction = $this->createPendingWaiterOrder();

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/pay", [
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 55000,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Payment completed successfully');

        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals(55000, $transaction->payment_amount);
        $this->assertEquals(0, $transaction->change_amount);
        $this->assertNotNull($transaction->completed_at);
        $this->assertEquals($this->session->id, $transaction->pos_session_id);
    }

    /** @test */
    public function pay_links_transaction_to_pos_session(): void
    {
        $transaction = $this->createPendingWaiterOrder();
        $this->assertNull($transaction->pos_session_id);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v2/orders/{$transaction->id}/pay", [
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 55000,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $transaction->refresh();
        $this->assertEquals($this->session->id, $transaction->pos_session_id);
    }

    /** @test */
    public function pay_calculates_change_correctly(): void
    {
        $transaction = $this->createPendingWaiterOrder();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v2/orders/{$transaction->id}/pay", [
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 100000,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $transaction->refresh();
        $this->assertEquals(100000, $transaction->payment_amount);
        $this->assertEquals(45000, $transaction->change_amount);
    }

    /** @test */
    public function pay_supports_split_payment(): void
    {
        $transaction = $this->createPendingWaiterOrder();

        $paymentMethod2 = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'digital_wallet',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/pay", [
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 30000,
                ],
                [
                    'payment_method_id' => $paymentMethod2->id,
                    'amount' => 25000,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();

        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals(2, $transaction->payments()->count());
    }

    /** @test */
    public function cannot_pay_completed_order(): void
    {
        $transaction = $this->createPendingWaiterOrder();
        $transaction->complete();

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/pay", [
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 55000,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Order is not pending.');
    }

    /** @test */
    public function cannot_pay_with_insufficient_amount(): void
    {
        $transaction = $this->createPendingWaiterOrder();

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/pay", [
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 10000,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Payment amount is less than grand total.');
    }

    /** @test */
    public function cannot_pay_without_active_session(): void
    {
        $transaction = $this->createPendingWaiterOrder();

        // Close the session
        $this->session->update(['status' => PosSession::STATUS_CLOSED]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/pay", [
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 55000,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'No active POS session. Please open a session first.');
    }

    /** @test */
    public function cannot_pay_nonexistent_order(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/orders/00000000-0000-0000-0000-000000000000/pay', [
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 55000,
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function pay_requires_payments_array(): void
    {
        $transaction = $this->createPendingWaiterOrder();

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v2/orders/{$transaction->id}/pay", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422);
    }
}
