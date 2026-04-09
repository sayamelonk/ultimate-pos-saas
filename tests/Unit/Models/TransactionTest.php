<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionDiscount;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected User $user;

    protected PosSession $posSession;

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
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'transaction_number' => 'TRX-TEST-001',
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'transaction_number' => 'TRX-TEST-001',
        ]);
    }

    public function test_transaction_belongs_to_tenant(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $transaction->tenant);
        $this->assertEquals($this->tenant->id, $transaction->tenant->id);
    }

    public function test_transaction_belongs_to_outlet(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $transaction->outlet);
        $this->assertEquals($this->outlet->id, $transaction->outlet->id);
    }

    public function test_transaction_belongs_to_pos_session(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(PosSession::class, $transaction->posSession);
        $this->assertEquals($this->posSession->id, $transaction->posSession->id);
    }

    public function test_transaction_belongs_to_user(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $transaction->user);
        $this->assertEquals($this->user->id, $transaction->user->id);
    }

    public function test_transaction_can_belong_to_customer(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertInstanceOf(Customer::class, $transaction->customer);
        $this->assertEquals($customer->id, $transaction->customer->id);
    }

    public function test_transaction_can_belong_to_table(): void
    {
        $table = Table::factory()->create(['tenant_id' => $this->tenant->id]);

        $transaction = Transaction::factory()->dineIn()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'table_id' => $table->id,
        ]);

        $this->assertInstanceOf(Table::class, $transaction->table);
        $this->assertEquals($table->id, $transaction->table->id);
    }

    // ============================================================
    // TYPE CONSTANTS TESTS
    // ============================================================

    public function test_type_constants(): void
    {
        $this->assertEquals('sale', Transaction::TYPE_SALE);
        $this->assertEquals('refund', Transaction::TYPE_REFUND);
        $this->assertEquals('void', Transaction::TYPE_VOID);
    }

    // ============================================================
    // STATUS CONSTANTS TESTS
    // ============================================================

    public function test_status_constants(): void
    {
        $this->assertEquals('pending', Transaction::STATUS_PENDING);
        $this->assertEquals('completed', Transaction::STATUS_COMPLETED);
        $this->assertEquals('voided', Transaction::STATUS_VOIDED);
    }

    // ============================================================
    // ORDER TYPE CONSTANTS TESTS
    // ============================================================

    public function test_order_type_constants(): void
    {
        $this->assertEquals('dine_in', Transaction::ORDER_TYPE_DINE_IN);
        $this->assertEquals('takeaway', Transaction::ORDER_TYPE_TAKEAWAY);
        $this->assertEquals('delivery', Transaction::ORDER_TYPE_DELIVERY);
    }

    // ============================================================
    // STATUS CHECK TESTS
    // ============================================================

    public function test_is_completed_returns_true_when_completed(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($transaction->isCompleted());
    }

    public function test_is_completed_returns_false_when_pending(): void
    {
        $transaction = Transaction::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($transaction->isCompleted());
    }

    public function test_is_voided_returns_true_when_voided(): void
    {
        $transaction = Transaction::factory()->voided()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($transaction->isVoided());
    }

    public function test_is_voided_returns_false_when_not_voided(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($transaction->isVoided());
    }

    // ============================================================
    // ORDER TYPE CHECK TESTS
    // ============================================================

    public function test_is_dine_in(): void
    {
        $transaction = Transaction::factory()->dineIn()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($transaction->isDineIn());
        $this->assertFalse($transaction->isTakeaway());
        $this->assertFalse($transaction->isDelivery());
    }

    public function test_is_takeaway(): void
    {
        $transaction = Transaction::factory()->takeaway()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($transaction->isTakeaway());
        $this->assertFalse($transaction->isDineIn());
        $this->assertFalse($transaction->isDelivery());
    }

    public function test_is_delivery(): void
    {
        $transaction = Transaction::factory()->delivery()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($transaction->isDelivery());
        $this->assertFalse($transaction->isDineIn());
        $this->assertFalse($transaction->isTakeaway());
    }

    // ============================================================
    // CAN VOID/REFUND TESTS
    // ============================================================

    public function test_can_void_when_completed_sale(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'type' => Transaction::TYPE_SALE,
        ]);

        $this->assertTrue($transaction->canVoid());
    }

    public function test_cannot_void_when_pending(): void
    {
        $transaction = Transaction::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'type' => Transaction::TYPE_SALE,
        ]);

        $this->assertFalse($transaction->canVoid());
    }

    public function test_cannot_void_refund_transaction(): void
    {
        $transaction = Transaction::factory()->completed()->refund()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($transaction->canVoid());
    }

    public function test_can_refund_when_completed_sale(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'type' => Transaction::TYPE_SALE,
        ]);

        $this->assertTrue($transaction->canRefund());
    }

    public function test_cannot_refund_when_voided(): void
    {
        $transaction = Transaction::factory()->voided()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'type' => Transaction::TYPE_SALE,
        ]);

        $this->assertFalse($transaction->canRefund());
    }

    // ============================================================
    // ITEMS RELATIONSHIP TESTS
    // ============================================================

    public function test_transaction_has_many_items(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        TransactionItem::factory()->count(3)->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
        ]);

        $this->assertCount(3, $transaction->items);
    }

    // ============================================================
    // PAYMENTS RELATIONSHIP TESTS
    // ============================================================

    public function test_transaction_has_many_payments(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        TransactionPayment::factory()->count(2)->create([
            'transaction_id' => $transaction->id,
        ]);

        $this->assertCount(2, $transaction->payments);
    }

    // ============================================================
    // DISCOUNTS RELATIONSHIP TESTS
    // ============================================================

    public function test_transaction_has_many_discounts(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        TransactionDiscount::factory()->count(2)->create([
            'transaction_id' => $transaction->id,
        ]);

        $this->assertCount(2, $transaction->discounts);
    }

    // ============================================================
    // REFUND RELATIONSHIP TESTS
    // ============================================================

    public function test_transaction_can_have_original_transaction(): void
    {
        $originalTransaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $refundTransaction = Transaction::factory()->refund()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'original_transaction_id' => $originalTransaction->id,
        ]);

        $this->assertInstanceOf(Transaction::class, $refundTransaction->originalTransaction);
        $this->assertEquals($originalTransaction->id, $refundTransaction->originalTransaction->id);
    }

    public function test_transaction_has_many_refund_transactions(): void
    {
        $originalTransaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        Transaction::factory()->refund()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'original_transaction_id' => $originalTransaction->id,
        ]);

        $this->assertCount(2, $originalTransaction->refundTransactions);
    }

    // ============================================================
    // GET PROFIT TESTS
    // ============================================================

    public function test_get_profit(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'subtotal' => 100000,
            'discount_amount' => 10000,
        ]);

        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50000,
            'cost_price' => 30000,
        ]);

        // profit = subtotal - discount - total_cost
        // profit = 100000 - 10000 - (30000 * 2) = 30000
        $this->assertEquals(30000, $transaction->getProfit());
    }

    // ============================================================
    // GET REFUNDED AMOUNT TESTS
    // ============================================================

    public function test_get_refunded_amount(): void
    {
        $originalTransaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        Transaction::factory()->refund()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'original_transaction_id' => $originalTransaction->id,
            'grand_total' => 25000,
        ]);

        Transaction::factory()->refund()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'original_transaction_id' => $originalTransaction->id,
            'grand_total' => 15000,
        ]);

        $this->assertEquals(40000, $originalTransaction->getRefundedAmount());
    }

    public function test_get_refundable_amount(): void
    {
        $originalTransaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        Transaction::factory()->refund()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'original_transaction_id' => $originalTransaction->id,
            'grand_total' => 30000,
        ]);

        // refundable = grand_total - refunded = 100000 - 30000
        $this->assertEquals(70000, $originalTransaction->getRefundableAmount());
    }

    // ============================================================
    // COMPLETE METHOD TESTS
    // ============================================================

    public function test_complete_method(): void
    {
        $transaction = Transaction::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $transaction->complete();
        $transaction->refresh();

        $this->assertEquals(Transaction::STATUS_COMPLETED, $transaction->status);
        $this->assertNotNull($transaction->completed_at);
    }

    // ============================================================
    // VOID METHOD TESTS
    // ============================================================

    public function test_void_method(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $transaction->void();
        $transaction->refresh();

        $this->assertEquals(Transaction::STATUS_VOIDED, $transaction->status);
    }

    // ============================================================
    // GET TYPES/STATUSES/ORDER TYPES TESTS
    // ============================================================

    public function test_get_types(): void
    {
        $types = Transaction::getTypes();

        $this->assertArrayHasKey(Transaction::TYPE_SALE, $types);
        $this->assertArrayHasKey(Transaction::TYPE_REFUND, $types);
        $this->assertArrayHasKey(Transaction::TYPE_VOID, $types);
    }

    public function test_get_statuses(): void
    {
        $statuses = Transaction::getStatuses();

        $this->assertArrayHasKey(Transaction::STATUS_PENDING, $statuses);
        $this->assertArrayHasKey(Transaction::STATUS_COMPLETED, $statuses);
        $this->assertArrayHasKey(Transaction::STATUS_VOIDED, $statuses);
    }

    public function test_get_order_types(): void
    {
        $orderTypes = Transaction::getOrderTypes();

        $this->assertArrayHasKey(Transaction::ORDER_TYPE_DINE_IN, $orderTypes);
        $this->assertArrayHasKey(Transaction::ORDER_TYPE_TAKEAWAY, $orderTypes);
        $this->assertArrayHasKey(Transaction::ORDER_TYPE_DELIVERY, $orderTypes);
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_decimal_fields_are_properly_cast(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'subtotal' => 100000.50,
            'grand_total' => 110000.75,
        ]);

        $this->assertIsString($transaction->subtotal);
        $this->assertIsString($transaction->grand_total);
    }

    public function test_datetime_fields_are_properly_cast(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->completed_at);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_pending_factory_state(): void
    {
        $transaction = Transaction::factory()->pending()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(Transaction::STATUS_PENDING, $transaction->status);
        $this->assertNull($transaction->completed_at);
    }

    public function test_completed_factory_state(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(Transaction::STATUS_COMPLETED, $transaction->status);
        $this->assertNotNull($transaction->completed_at);
    }

    public function test_voided_factory_state(): void
    {
        $transaction = Transaction::factory()->voided()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(Transaction::STATUS_VOIDED, $transaction->status);
    }

    public function test_refund_factory_state(): void
    {
        $transaction = Transaction::factory()->refund()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(Transaction::TYPE_REFUND, $transaction->type);
    }

    public function test_with_grand_total_factory_state(): void
    {
        $transaction = Transaction::factory()->withGrandTotal(250000)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(250000, $transaction->grand_total);
        $this->assertEquals(250000, $transaction->subtotal);
    }

    public function test_with_customer_factory_state(): void
    {
        $transaction = Transaction::factory()->withCustomer()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertNotNull($transaction->customer_id);
        $this->assertInstanceOf(Customer::class, $transaction->customer);
    }
}
