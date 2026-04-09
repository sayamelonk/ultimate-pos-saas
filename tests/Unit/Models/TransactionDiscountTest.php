<?php

namespace Tests\Unit\Models;

use App\Models\Discount;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionDiscount;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected User $user;

    protected PosSession $posSession;

    protected Transaction $transaction;

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
    }

    // ============================================================
    // BASIC CREATION TESTS
    // ============================================================

    public function test_can_create_transaction_discount(): void
    {
        $discount = TransactionDiscount::factory()->create([
            'transaction_id' => $this->transaction->id,
            'discount_name' => 'Test Discount',
            'amount' => 10000,
        ]);

        $this->assertDatabaseHas('transaction_discounts', [
            'id' => $discount->id,
            'discount_name' => 'Test Discount',
            'amount' => 10000,
        ]);
    }

    public function test_transaction_discount_belongs_to_transaction(): void
    {
        $discount = TransactionDiscount::factory()->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertInstanceOf(Transaction::class, $discount->transaction);
        $this->assertEquals($this->transaction->id, $discount->transaction->id);
    }

    public function test_transaction_discount_can_belong_to_transaction_item(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $product->id,
        ]);

        $discount = TransactionDiscount::factory()->itemLevel()->create([
            'transaction_id' => $this->transaction->id,
            'transaction_item_id' => $item->id,
        ]);

        $this->assertInstanceOf(TransactionItem::class, $discount->transactionItem);
        $this->assertEquals($item->id, $discount->transactionItem->id);
    }

    public function test_transaction_discount_can_belong_to_discount(): void
    {
        $masterDiscount = Discount::factory()->create(['tenant_id' => $this->tenant->id]);

        $discount = TransactionDiscount::factory()->create([
            'transaction_id' => $this->transaction->id,
            'discount_id' => $masterDiscount->id,
        ]);

        $this->assertInstanceOf(Discount::class, $discount->discount);
        $this->assertEquals($masterDiscount->id, $discount->discount->id);
    }

    // ============================================================
    // TYPE CONSTANTS TESTS
    // ============================================================

    public function test_type_constants(): void
    {
        $this->assertEquals('percentage', TransactionDiscount::TYPE_PERCENTAGE);
        $this->assertEquals('fixed_amount', TransactionDiscount::TYPE_FIXED_AMOUNT);
    }

    // ============================================================
    // IS ORDER LEVEL TESTS
    // ============================================================

    public function test_is_order_level_returns_true_when_no_item(): void
    {
        $discount = TransactionDiscount::factory()->orderLevel()->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertTrue($discount->isOrderLevel());
    }

    public function test_is_order_level_returns_false_when_has_item(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $product->id,
        ]);

        $discount = TransactionDiscount::factory()->create([
            'transaction_id' => $this->transaction->id,
            'transaction_item_id' => $item->id,
        ]);

        $this->assertFalse($discount->isOrderLevel());
    }

    // ============================================================
    // IS ITEM LEVEL TESTS
    // ============================================================

    public function test_is_item_level_returns_true_when_has_item(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $product->id,
        ]);

        $discount = TransactionDiscount::factory()->create([
            'transaction_id' => $this->transaction->id,
            'transaction_item_id' => $item->id,
        ]);

        $this->assertTrue($discount->isItemLevel());
    }

    public function test_is_item_level_returns_false_when_no_item(): void
    {
        $discount = TransactionDiscount::factory()->orderLevel()->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertFalse($discount->isItemLevel());
    }

    // ============================================================
    // GET TYPES TESTS
    // ============================================================

    public function test_get_types(): void
    {
        $types = TransactionDiscount::getTypes();

        $this->assertArrayHasKey(TransactionDiscount::TYPE_PERCENTAGE, $types);
        $this->assertArrayHasKey(TransactionDiscount::TYPE_FIXED_AMOUNT, $types);
        $this->assertEquals('Percentage', $types[TransactionDiscount::TYPE_PERCENTAGE]);
        $this->assertEquals('Fixed Amount', $types[TransactionDiscount::TYPE_FIXED_AMOUNT]);
    }

    // ============================================================
    // PERCENTAGE DISCOUNT TESTS
    // ============================================================

    public function test_percentage_discount(): void
    {
        $discount = TransactionDiscount::factory()->percentage(10)->create([
            'transaction_id' => $this->transaction->id,
            'amount' => 10000, // 10% of 100000
        ]);

        $this->assertEquals(TransactionDiscount::TYPE_PERCENTAGE, $discount->type);
        $this->assertEquals(10, $discount->value);
        $this->assertEquals(10000, $discount->amount);
    }

    public function test_percentage_discount_with_different_percentages(): void
    {
        $discount5 = TransactionDiscount::factory()->percentage(5)->create([
            'transaction_id' => $this->transaction->id,
            'amount' => 5000,
        ]);

        $discount15 = TransactionDiscount::factory()->percentage(15)->create([
            'transaction_id' => $this->transaction->id,
            'amount' => 15000,
        ]);

        $discount25 = TransactionDiscount::factory()->percentage(25)->create([
            'transaction_id' => $this->transaction->id,
            'amount' => 25000,
        ]);

        $this->assertEquals(5, $discount5->value);
        $this->assertEquals(15, $discount15->value);
        $this->assertEquals(25, $discount25->value);
    }

    // ============================================================
    // FIXED AMOUNT DISCOUNT TESTS
    // ============================================================

    public function test_fixed_amount_discount(): void
    {
        $discount = TransactionDiscount::factory()->fixedAmount(15000)->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertEquals(TransactionDiscount::TYPE_FIXED_AMOUNT, $discount->type);
        $this->assertEquals(15000, $discount->value);
        $this->assertEquals(15000, $discount->amount);
    }

    public function test_fixed_amount_discount_value_equals_amount(): void
    {
        $discount = TransactionDiscount::factory()->fixedAmount(20000)->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertEquals($discount->value, $discount->amount);
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_decimal_fields_are_properly_cast(): void
    {
        $discount = TransactionDiscount::factory()->create([
            'transaction_id' => $this->transaction->id,
            'value' => 10.50,
            'amount' => 15000.75,
        ]);

        $this->assertIsString($discount->value);
        $this->assertIsString($discount->amount);
    }

    // ============================================================
    // MULTIPLE DISCOUNTS TESTS
    // ============================================================

    public function test_transaction_can_have_multiple_discounts(): void
    {
        TransactionDiscount::factory()->percentage(10)->create([
            'transaction_id' => $this->transaction->id,
            'amount' => 10000,
        ]);

        TransactionDiscount::factory()->fixedAmount(5000)->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertCount(2, $this->transaction->discounts);
        $this->assertEquals(15000, $this->transaction->discounts->sum('amount'));
    }

    public function test_transaction_can_have_order_and_item_level_discounts(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $item = TransactionItem::factory()->create([
            'transaction_id' => $this->transaction->id,
            'product_id' => $product->id,
        ]);

        $orderDiscount = TransactionDiscount::factory()->orderLevel()->create([
            'transaction_id' => $this->transaction->id,
            'amount' => 10000,
        ]);

        $itemDiscount = TransactionDiscount::factory()->create([
            'transaction_id' => $this->transaction->id,
            'transaction_item_id' => $item->id,
            'amount' => 5000,
        ]);

        $this->assertTrue($orderDiscount->isOrderLevel());
        $this->assertTrue($itemDiscount->isItemLevel());
        $this->assertCount(2, $this->transaction->discounts);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_percentage_factory_state(): void
    {
        $discount = TransactionDiscount::factory()->percentage(20)->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertEquals(TransactionDiscount::TYPE_PERCENTAGE, $discount->type);
        $this->assertEquals(20, $discount->value);
        $this->assertStringContainsString('20%', $discount->discount_name);
    }

    public function test_fixed_amount_factory_state(): void
    {
        $discount = TransactionDiscount::factory()->fixedAmount(25000)->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertEquals(TransactionDiscount::TYPE_FIXED_AMOUNT, $discount->type);
        $this->assertEquals(25000, $discount->value);
        $this->assertEquals(25000, $discount->amount);
    }

    public function test_order_level_factory_state(): void
    {
        $discount = TransactionDiscount::factory()->orderLevel()->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertNull($discount->transaction_item_id);
        $this->assertTrue($discount->isOrderLevel());
    }

    public function test_item_level_factory_state(): void
    {
        $discount = TransactionDiscount::factory()->itemLevel()->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertNotNull($discount->transaction_item_id);
        $this->assertTrue($discount->isItemLevel());
    }

    public function test_with_discount_factory_state(): void
    {
        $discount = TransactionDiscount::factory()->withDiscount()->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertNotNull($discount->discount_id);
        $this->assertInstanceOf(Discount::class, $discount->discount);
    }

    public function test_with_amount_factory_state(): void
    {
        $discount = TransactionDiscount::factory()->withAmount(50000)->create([
            'transaction_id' => $this->transaction->id,
        ]);

        $this->assertEquals(50000, $discount->amount);
    }

    // ============================================================
    // DISCOUNT NAME TESTS
    // ============================================================

    public function test_discount_has_name(): void
    {
        $discount = TransactionDiscount::factory()->create([
            'transaction_id' => $this->transaction->id,
            'discount_name' => 'Birthday Discount',
        ]);

        $this->assertEquals('Birthday Discount', $discount->discount_name);
    }

    public function test_discount_name_from_master_discount(): void
    {
        $masterDiscount = Discount::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Member Discount',
        ]);

        $discount = TransactionDiscount::factory()->create([
            'transaction_id' => $this->transaction->id,
            'discount_id' => $masterDiscount->id,
            'discount_name' => 'Member Discount',
        ]);

        $this->assertEquals('Member Discount', $discount->discount_name);
        $this->assertEquals($masterDiscount->id, $discount->discount_id);
    }

    // ============================================================
    // EDGE CASES
    // ============================================================

    public function test_zero_amount_discount(): void
    {
        $discount = TransactionDiscount::factory()->create([
            'transaction_id' => $this->transaction->id,
            'value' => 0,
            'amount' => 0,
        ]);

        $this->assertEquals(0, $discount->amount);
    }

    public function test_100_percent_discount(): void
    {
        $discount = TransactionDiscount::factory()->percentage(100)->create([
            'transaction_id' => $this->transaction->id,
            'amount' => 100000, // Full amount
        ]);

        $this->assertEquals(100, $discount->value);
        $this->assertEquals(100000, $discount->amount);
    }

    public function test_decimal_percentage_discount(): void
    {
        $discount = TransactionDiscount::factory()->create([
            'transaction_id' => $this->transaction->id,
            'type' => TransactionDiscount::TYPE_PERCENTAGE,
            'value' => 12.5, // 12.5%
            'amount' => 12500,
        ]);

        $this->assertEquals(12.5, $discount->value);
    }
}
