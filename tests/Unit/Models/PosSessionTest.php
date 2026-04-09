<?php

namespace Tests\Unit\Models;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSessionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_pos_session(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'session_number' => 'SES-TEST-001',
        ]);

        $this->assertDatabaseHas('pos_sessions', [
            'id' => $session->id,
            'session_number' => 'SES-TEST-001',
        ]);
    }

    public function test_pos_session_belongs_to_outlet(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $session->outlet);
        $this->assertEquals($this->outlet->id, $session->outlet->id);
    }

    public function test_pos_session_belongs_to_user(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $session->user);
        $this->assertEquals($this->user->id, $session->user->id);
    }

    public function test_pos_session_can_have_closed_by_user(): void
    {
        $closedByUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $session = PosSession::factory()->closed()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'closed_by' => $closedByUser->id,
        ]);

        $this->assertInstanceOf(User::class, $session->closedByUser);
        $this->assertEquals($closedByUser->id, $session->closedByUser->id);
    }

    // ============================================================
    // STATUS CONSTANTS TESTS
    // ============================================================

    public function test_status_constants(): void
    {
        $this->assertEquals('open', PosSession::STATUS_OPEN);
        $this->assertEquals('closed', PosSession::STATUS_CLOSED);
    }

    // ============================================================
    // STATUS CHECK TESTS
    // ============================================================

    public function test_is_open_returns_true_when_open(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);

        $this->assertTrue($session->isOpen());
    }

    public function test_is_open_returns_false_when_closed(): void
    {
        $session = PosSession::factory()->closed()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($session->isOpen());
    }

    // ============================================================
    // TRANSACTIONS RELATIONSHIP TESTS
    // ============================================================

    public function test_pos_session_has_many_transactions(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertCount(3, $session->transactions);
    }

    // ============================================================
    // GET TOTAL SALES TESTS
    // ============================================================

    public function test_get_total_sales(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 150000,
        ]);

        $this->assertEquals(250000, $session->getTotalSales());
    }

    public function test_get_total_sales_excludes_pending_transactions(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        Transaction::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 50000,
        ]);

        $this->assertEquals(100000, $session->getTotalSales());
    }

    public function test_get_total_sales_excludes_voided_transactions(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        Transaction::factory()->voided()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 75000,
        ]);

        $this->assertEquals(100000, $session->getTotalSales());
    }

    // ============================================================
    // GET TRANSACTION COUNT TESTS
    // ============================================================

    public function test_get_transaction_count(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->completed()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(5, $session->getTransactionCount());
    }

    public function test_get_transaction_count_excludes_non_completed(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->completed()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->voided()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(3, $session->getTransactionCount());
    }

    // ============================================================
    // GET CASH SALES TESTS
    // ============================================================

    public function test_get_cash_sales(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $cashMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        TransactionPayment::factory()->create([
            'transaction_id' => $transaction->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 100000,
        ]);

        $this->assertEquals(100000, $session->getCashSales());
    }

    public function test_get_cash_sales_excludes_non_cash_payments(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $cashMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $cardMethod = PaymentMethod::factory()->card()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $transaction1 = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        TransactionPayment::factory()->create([
            'transaction_id' => $transaction1->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 100000,
        ]);

        $transaction2 = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 200000,
        ]);

        TransactionPayment::factory()->create([
            'transaction_id' => $transaction2->id,
            'payment_method_id' => $cardMethod->id,
            'amount' => 200000,
        ]);

        $this->assertEquals(100000, $session->getCashSales());
    }

    // ============================================================
    // GET CASH REFUNDS TESTS
    // ============================================================

    public function test_get_cash_refunds(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $cashMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $refundTransaction = Transaction::factory()->completed()->refund()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 25000,
        ]);

        TransactionPayment::factory()->create([
            'transaction_id' => $refundTransaction->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 25000,
        ]);

        $this->assertEquals(25000, $session->getCashRefunds());
    }

    // ============================================================
    // GET EXPECTED CASH TESTS
    // ============================================================

    public function test_get_expected_cash(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 200000,
        ]);

        $cashMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $saleTransaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
            'type' => Transaction::TYPE_SALE,
        ]);

        TransactionPayment::factory()->create([
            'transaction_id' => $saleTransaction->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 100000,
        ]);

        // opening_cash + cash_sales - cash_refunds = 200000 + 100000 - 0
        $this->assertEquals(300000, $session->getExpectedCash());
    }

    // ============================================================
    // CLOSE SESSION TESTS
    // ============================================================

    public function test_close_session(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 200000,
            'status' => PosSession::STATUS_OPEN,
        ]);

        $session->close(250000, $this->user->id, 'Shift ended normally');

        $session->refresh();

        $this->assertEquals(PosSession::STATUS_CLOSED, $session->status);
        $this->assertEquals(250000, $session->closing_cash);
        $this->assertEquals('Shift ended normally', $session->closing_notes);
        $this->assertEquals($this->user->id, $session->closed_by);
        $this->assertNotNull($session->closed_at);
    }

    public function test_close_session_calculates_cash_difference(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 200000,
            'status' => PosSession::STATUS_OPEN,
        ]);

        // Expected cash is opening_cash since no transactions
        $session->close(195000, $this->user->id);

        $session->refresh();

        // expected_cash = 200000, closing_cash = 195000
        // cash_difference = 195000 - 200000 = -5000
        $this->assertEquals(-5000, $session->cash_difference);
    }

    // ============================================================
    // GET STATUSES TESTS
    // ============================================================

    public function test_get_statuses(): void
    {
        $statuses = PosSession::getStatuses();

        $this->assertArrayHasKey(PosSession::STATUS_OPEN, $statuses);
        $this->assertArrayHasKey(PosSession::STATUS_CLOSED, $statuses);
        $this->assertEquals('Open', $statuses[PosSession::STATUS_OPEN]);
        $this->assertEquals('Closed', $statuses[PosSession::STATUS_CLOSED]);
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_decimal_fields_are_properly_cast(): void
    {
        $session = PosSession::factory()->closed()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 200000.50,
            'closing_cash' => 350000.75,
        ]);

        $this->assertIsString($session->opening_cash);
        $this->assertIsString($session->closing_cash);
    }

    public function test_datetime_fields_are_properly_cast(): void
    {
        $session = PosSession::factory()->closed()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(\DateTimeInterface::class, $session->opened_at);
        $this->assertInstanceOf(\DateTimeInterface::class, $session->closed_at);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_default_factory_state_is_open(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(PosSession::STATUS_OPEN, $session->status);
        $this->assertNull($session->closed_at);
    }

    public function test_closed_factory_state(): void
    {
        $session = PosSession::factory()->closed()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(PosSession::STATUS_CLOSED, $session->status);
        $this->assertNotNull($session->closing_cash);
        $this->assertNotNull($session->closed_at);
        $this->assertNotNull($session->closed_by);
    }

    public function test_with_opening_cash_factory_state(): void
    {
        $session = PosSession::factory()->withOpeningCash(500000)->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(500000, $session->opening_cash);
    }
}
