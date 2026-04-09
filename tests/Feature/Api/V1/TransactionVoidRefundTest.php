<?php

namespace Tests\Feature\Api\V1;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionVoidRefundTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected Outlet $outlet;

    protected PosSession $session;

    protected Product $product;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet->users()->attach($this->user->id);

        $this->paymentMethod = PaymentMethod::factory()->cash()->create(['tenant_id' => $this->tenant->id]);

        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Nasi Goreng',
            'sku' => 'NG001',
            'base_price' => 25000,
        ]);

        $this->session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => 'open',
        ]);

        Sanctum::actingAs($this->user);
    }

    protected function createCompletedTransaction(array $overrides = []): Transaction
    {
        $transaction = Transaction::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'transaction_number' => 'TRX'.date('Ymd').'0001',
            'type' => Transaction::TYPE_SALE,
            'status' => Transaction::STATUS_COMPLETED,
            'order_type' => 'dine_in',
            'subtotal' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 5500,
            'service_charge_amount' => 0,
            'grand_total' => 55500,
            'payment_amount' => 60000,
            'change_amount' => 4500,
            'completed_at' => now(),
        ], $overrides));

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'item_name' => 'Nasi Goreng',
            'item_sku' => 'NG001',
            'quantity' => 2,
            'unit_price' => 25000,
            'base_price' => 25000,
            'subtotal' => 50000,
        ]);

        TransactionPayment::create([
            'transaction_id' => $transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 60000,
        ]);

        return $transaction;
    }

    // ==================== VOID TRANSACTION ====================

    public function test_can_void_completed_transaction(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/void", [
            'reason' => 'Customer changed mind',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'transaction_number',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => Transaction::STATUS_VOIDED,
        ]);
    }

    public function test_void_requires_reason(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/void", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_void_adds_reason_to_notes(): void
    {
        $transaction = $this->createCompletedTransaction();

        $this->postJson("/api/v1/transactions/{$transaction->id}/void", [
            'reason' => 'Wrong order',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'notes' => 'VOIDED: Wrong order',
        ]);
    }

    public function test_cannot_void_already_voided_transaction(): void
    {
        $transaction = $this->createCompletedTransaction([
            'status' => Transaction::STATUS_VOIDED,
        ]);

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/void", [
            'reason' => 'Another void attempt',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400);
    }

    public function test_cannot_void_refund_transaction(): void
    {
        $transaction = $this->createCompletedTransaction([
            'type' => Transaction::TYPE_REFUND,
        ]);

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/void", [
            'reason' => 'Cannot void refund',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400);
    }

    public function test_void_returns_404_for_invalid_transaction(): void
    {
        $response = $this->postJson('/api/v1/transactions/00000000-0000-0000-0000-000000000000/void', [
            'reason' => 'Invalid transaction',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(404);
    }

    // ==================== REFUND TRANSACTION ====================

    public function test_can_refund_completed_transaction(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 25000,
            'reason' => 'Item returned',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'transaction_number',
                    'type',
                    'grand_total',
                ],
            ]);

        // Verify refund transaction created
        $this->assertDatabaseHas('transactions', [
            'original_transaction_id' => $transaction->id,
            'type' => Transaction::TYPE_REFUND,
            'grand_total' => 25000,
        ]);
    }

    public function test_refund_creates_transaction_with_ref_prefix(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 10000,
            'reason' => 'Partial refund',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);
        $transactionNumber = $response->json('data.transaction_number');
        $this->assertStringStartsWith('REF', $transactionNumber);
    }

    public function test_can_do_full_refund(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 55500, // Full grand_total
            'reason' => 'Full refund requested',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(55500, $response->json('data.grand_total'));
    }

    public function test_cannot_refund_more_than_refundable_amount(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 100000, // More than grand_total
            'reason' => 'Over refund attempt',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_cannot_refund_already_fully_refunded_transaction(): void
    {
        $transaction = $this->createCompletedTransaction();

        // First refund - full amount
        $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 55500,
            'reason' => 'First refund',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        // Second refund attempt
        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 1000,
            'reason' => 'Second refund attempt',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400);
    }

    public function test_can_do_multiple_partial_refunds(): void
    {
        $transaction = $this->createCompletedTransaction();

        // First partial refund
        $response1 = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 20000,
            'reason' => 'First partial refund',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response1->assertStatus(200);

        // Second partial refund
        $response2 = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 30000,
            'reason' => 'Second partial refund',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response2->assertStatus(200);

        // Verify total refunds
        $refundTotal = Transaction::where('original_transaction_id', $transaction->id)
            ->where('type', Transaction::TYPE_REFUND)
            ->sum('grand_total');

        $this->assertEquals(50000, $refundTotal);
    }

    public function test_refund_requires_active_session(): void
    {
        $transaction = $this->createCompletedTransaction();

        // Close the session
        $this->session->update(['status' => 'closed']);

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 10000,
            'reason' => 'Refund without session',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400);
    }

    public function test_refund_requires_amount(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'reason' => 'Missing amount',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_refund_requires_reason(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 10000,
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_refund_requires_payment_method(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 10000,
            'reason' => 'Missing payment method',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method_id']);
    }

    public function test_cannot_refund_voided_transaction(): void
    {
        $transaction = $this->createCompletedTransaction([
            'status' => Transaction::STATUS_VOIDED,
        ]);

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 10000,
            'reason' => 'Cannot refund voided',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400);
    }

    public function test_refund_links_to_original_transaction(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 15000,
            'reason' => 'Linked refund',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);

        $refundId = $response->json('data.id');
        $this->assertDatabaseHas('transactions', [
            'id' => $refundId,
            'original_transaction_id' => $transaction->id,
        ]);
    }

    public function test_refund_creates_payment_record(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 20000,
            'reason' => 'With payment record',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);
        $refundId = $response->json('data.id');

        $this->assertDatabaseHas('transaction_payments', [
            'transaction_id' => $refundId,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 20000,
        ]);
    }

    // ==================== RECEIPT ====================

    public function test_can_get_transaction_receipt(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->getJson("/api/v1/transactions/{$transaction->id}/receipt", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'transaction' => [
                        'id',
                        'transaction_number',
                        'items',
                        'grand_total',
                    ],
                    'outlet' => [
                        'name',
                        'address',
                        'receipt_header',
                        'receipt_footer',
                    ],
                    'print_time',
                ],
            ]);
    }

    public function test_receipt_includes_outlet_info(): void
    {
        // Update outlet with receipt settings
        $this->outlet->update([
            'receipt_header' => 'Welcome to Our Store!',
            'receipt_footer' => 'Thank you for your purchase',
        ]);

        $transaction = $this->createCompletedTransaction();

        $response = $this->getJson("/api/v1/transactions/{$transaction->id}/receipt", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Welcome to Our Store!', $response->json('data.outlet.receipt_header'));
        $this->assertEquals('Thank you for your purchase', $response->json('data.outlet.receipt_footer'));
    }

    public function test_receipt_includes_print_time(): void
    {
        $transaction = $this->createCompletedTransaction();

        $response = $this->getJson("/api/v1/transactions/{$transaction->id}/receipt", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.print_time'));
    }

    public function test_receipt_returns_404_for_invalid_transaction(): void
    {
        $response = $this->getJson('/api/v1/transactions/00000000-0000-0000-0000-000000000000/receipt', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_can_get_refund_receipt(): void
    {
        $transaction = $this->createCompletedTransaction();

        // Create refund
        $refundResponse = $this->postJson("/api/v1/transactions/{$transaction->id}/refund", [
            'amount' => 25000,
            'reason' => 'Item returned',
            'payment_method_id' => $this->paymentMethod->id,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $refundId = $refundResponse->json('data.id');

        // Get refund receipt
        $response = $this->getJson("/api/v1/transactions/{$refundId}/receipt", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(Transaction::TYPE_REFUND, $response->json('data.transaction.type'));
    }
}
