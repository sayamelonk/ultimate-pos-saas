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

class TransactionPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected User $user;

    protected PosSession $posSession;

    protected Transaction $transaction;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->posSession = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $this->transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);
        $this->paymentMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_transaction_payment(): void
    {
        $payment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 100000,
        ]);

        $this->assertDatabaseHas('transaction_payments', [
            'id' => $payment->id,
            'amount' => 100000,
        ]);
    }

    public function test_transaction_payment_belongs_to_transaction(): void
    {
        $payment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $this->assertInstanceOf(Transaction::class, $payment->transaction);
        $this->assertEquals($this->transaction->id, $payment->transaction->id);
    }

    public function test_transaction_payment_belongs_to_payment_method(): void
    {
        $payment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $this->assertInstanceOf(PaymentMethod::class, $payment->paymentMethod);
        $this->assertEquals($this->paymentMethod->id, $payment->paymentMethod->id);
    }

    // ============================================================
    // GET NET AMOUNT TESTS
    // ============================================================

    public function test_get_net_amount(): void
    {
        $payment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 100000,
            'charge_amount' => 0,
        ]);

        // net_amount = amount - charge_amount = 100000 - 0
        $this->assertEquals(100000, $payment->getNetAmount());
    }

    public function test_get_net_amount_with_charge(): void
    {
        $payment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 100000,
            'charge_amount' => 2500, // 2.5% charge
        ]);

        // net_amount = amount - charge_amount = 100000 - 2500
        $this->assertEquals(97500, $payment->getNetAmount());
    }

    public function test_get_net_amount_with_larger_charge(): void
    {
        $payment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500000,
            'charge_amount' => 15000, // 3% charge
        ]);

        // net_amount = 500000 - 15000 = 485000
        $this->assertEquals(485000, $payment->getNetAmount());
    }

    // ============================================================
    // CASH PAYMENT TESTS
    // ============================================================

    public function test_cash_payment_has_no_charge(): void
    {
        $cashMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $payment = TransactionPayment::factory()->cash()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 100000,
        ]);

        $this->assertEquals(0, $payment->charge_amount);
        $this->assertEquals(100000, $payment->getNetAmount());
    }

    public function test_cash_payment_has_no_reference(): void
    {
        $cashMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $payment = TransactionPayment::factory()->cash()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $cashMethod->id,
        ]);

        $this->assertNull($payment->reference_number);
    }

    // ============================================================
    // CARD/NON-CASH PAYMENT TESTS
    // ============================================================

    public function test_card_payment_can_have_charge(): void
    {
        $cardMethod = PaymentMethod::factory()->card()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $payment = TransactionPayment::factory()
            ->withAmount(100000)
            ->withCharge(2.5)
            ->create([
                'transaction_id' => $this->transaction->id,
                'payment_method_id' => $cardMethod->id,
            ]);

        $this->assertEquals(2500, $payment->charge_amount);
        $this->assertEquals(97500, $payment->getNetAmount());
    }

    public function test_card_payment_can_have_reference(): void
    {
        $cardMethod = PaymentMethod::factory()->card()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $payment = TransactionPayment::factory()->withReference('AUTH-12345')->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $cardMethod->id,
        ]);

        $this->assertEquals('AUTH-12345', $payment->reference_number);
    }

    // ============================================================
    // APPROVAL CODE TESTS
    // ============================================================

    public function test_payment_can_have_approval_code(): void
    {
        $payment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
            'approval_code' => 'APR-67890',
        ]);

        $this->assertEquals('APR-67890', $payment->approval_code);
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_decimal_fields_are_properly_cast(): void
    {
        $payment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 100000.50,
            'charge_amount' => 2500.25,
        ]);

        $this->assertIsString($payment->amount);
        $this->assertIsString($payment->charge_amount);
    }

    // ============================================================
    // SPLIT PAYMENT TESTS
    // ============================================================

    public function test_transaction_can_have_multiple_payments(): void
    {
        $cashMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $cardMethod = PaymentMethod::factory()->card()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 50000,
        ]);

        TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $cardMethod->id,
            'amount' => 50000,
        ]);

        $this->assertCount(2, $this->transaction->payments);
        $this->assertEquals(100000, $this->transaction->payments->sum('amount'));
    }

    public function test_split_payment_with_different_charges(): void
    {
        $cashMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $cardMethod = PaymentMethod::factory()->card()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $cashPayment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 50000,
            'charge_amount' => 0,
        ]);

        $cardPayment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $cardMethod->id,
            'amount' => 50000,
            'charge_amount' => 1250, // 2.5%
        ]);

        $this->assertEquals(50000, $cashPayment->getNetAmount());
        $this->assertEquals(48750, $cardPayment->getNetAmount());

        // Total net = 50000 + 48750 = 98750
        $totalNet = $cashPayment->getNetAmount() + $cardPayment->getNetAmount();
        $this->assertEquals(98750, $totalNet);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_cash_factory_state(): void
    {
        $payment = TransactionPayment::factory()->cash()->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertEquals(0, $payment->charge_amount);
        $this->assertNull($payment->reference_number);
    }

    public function test_with_charge_factory_state(): void
    {
        $payment = TransactionPayment::factory()
            ->withAmount(200000)
            ->withCharge(3)
            ->create([
                'transaction_id' => $this->transaction->id,
                'payment_method_id' => $this->paymentMethod->id,
            ]);

        // 3% of 200000 = 6000
        $this->assertEquals(6000, $payment->charge_amount);
    }

    public function test_with_reference_factory_state(): void
    {
        $payment = TransactionPayment::factory()->withReference('REF-TEST-123')->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $this->assertEquals('REF-TEST-123', $payment->reference_number);
    }

    public function test_with_reference_factory_state_generates_reference_if_null(): void
    {
        $payment = TransactionPayment::factory()->withReference()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $this->assertNotNull($payment->reference_number);
        $this->assertStringStartsWith('REF-', $payment->reference_number);
    }

    public function test_with_amount_factory_state(): void
    {
        $payment = TransactionPayment::factory()->withAmount(75000)->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $this->assertEquals(75000, $payment->amount);
    }

    // ============================================================
    // EDGE CASES
    // ============================================================

    public function test_zero_amount_payment(): void
    {
        $payment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 0,
            'charge_amount' => 0,
        ]);

        $this->assertEquals(0, $payment->amount);
        $this->assertEquals(0, $payment->getNetAmount());
    }

    public function test_charge_equal_to_amount(): void
    {
        $payment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 1000,
            'charge_amount' => 1000,
        ]);

        $this->assertEquals(0, $payment->getNetAmount());
    }

    public function test_large_amount_payment(): void
    {
        $payment = TransactionPayment::factory()->create([
            'transaction_id' => $this->transaction->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 999999999.99,
            'charge_amount' => 0,
        ]);

        $this->assertEquals(999999999.99, $payment->getNetAmount());
    }
}
