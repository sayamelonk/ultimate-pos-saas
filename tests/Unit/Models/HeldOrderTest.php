<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\HeldOrder;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeldOrderTest extends TestCase
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

    public function test_can_create_held_order(): void
    {
        $heldOrder = HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'hold_number' => 'HLD-TEST-001',
        ]);

        $this->assertDatabaseHas('held_orders', [
            'id' => $heldOrder->id,
            'hold_number' => 'HLD-TEST-001',
        ]);
    }

    public function test_held_order_belongs_to_tenant(): void
    {
        $heldOrder = HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $heldOrder->tenant);
        $this->assertEquals($this->tenant->id, $heldOrder->tenant->id);
    }

    public function test_held_order_belongs_to_outlet(): void
    {
        $heldOrder = HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $heldOrder->outlet);
        $this->assertEquals($this->outlet->id, $heldOrder->outlet->id);
    }

    public function test_held_order_belongs_to_pos_session(): void
    {
        $heldOrder = HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(PosSession::class, $heldOrder->posSession);
        $this->assertEquals($this->posSession->id, $heldOrder->posSession->id);
    }

    public function test_held_order_belongs_to_user(): void
    {
        $heldOrder = HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $heldOrder->user);
        $this->assertEquals($this->user->id, $heldOrder->user->id);
    }

    public function test_held_order_can_belong_to_customer(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $heldOrder = HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertInstanceOf(Customer::class, $heldOrder->customer);
        $this->assertEquals($customer->id, $heldOrder->customer->id);
    }

    // ============================================================
    // IS EXPIRED TESTS
    // ============================================================

    public function test_is_expired_returns_true_when_expired(): void
    {
        $heldOrder = HeldOrder::factory()->expired()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($heldOrder->isExpired());
    }

    public function test_is_expired_returns_false_when_not_expired(): void
    {
        $heldOrder = HeldOrder::factory()->expiresIn(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($heldOrder->isExpired());
    }

    public function test_is_expired_returns_false_when_no_expiry(): void
    {
        $heldOrder = HeldOrder::factory()->notExpiring()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($heldOrder->isExpired());
    }

    // ============================================================
    // GET ITEM COUNT TESTS
    // ============================================================

    public function test_get_item_count(): void
    {
        $items = [
            ['product_id' => 'uuid1', 'product_name' => 'Product 1', 'quantity' => 2, 'unit_price' => 25000, 'subtotal' => 50000],
            ['product_id' => 'uuid2', 'product_name' => 'Product 2', 'quantity' => 3, 'unit_price' => 15000, 'subtotal' => 45000],
        ];

        $heldOrder = HeldOrder::factory()->withItems($items)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        // Total quantity = 2 + 3 = 5
        $this->assertEquals(5, $heldOrder->getItemCount());
    }

    public function test_get_item_count_with_empty_items(): void
    {
        $heldOrder = HeldOrder::factory()->withItems([])->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(0, $heldOrder->getItemCount());
    }

    public function test_get_item_count_with_single_item(): void
    {
        $items = [
            ['product_id' => 'uuid1', 'product_name' => 'Product 1', 'quantity' => 1, 'unit_price' => 50000, 'subtotal' => 50000],
        ];

        $heldOrder = HeldOrder::factory()->withItems($items)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(1, $heldOrder->getItemCount());
    }

    // ============================================================
    // GET DISPLAY NAME TESTS
    // ============================================================

    public function test_get_display_name_returns_reference_when_set(): void
    {
        $heldOrder = HeldOrder::factory()->withReference('Order for John')->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'table_number' => '5',
            'hold_number' => 'HLD-001',
        ]);

        $this->assertEquals('Order for John', $heldOrder->getDisplayName());
    }

    public function test_get_display_name_returns_table_number_when_no_reference(): void
    {
        $heldOrder = HeldOrder::factory()->withTableNumber('12')->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'reference' => null,
            'hold_number' => 'HLD-001',
        ]);

        $this->assertEquals('Table 12', $heldOrder->getDisplayName());
    }

    public function test_get_display_name_returns_hold_number_when_no_reference_or_table(): void
    {
        $heldOrder = HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'reference' => null,
            'table_number' => null,
            'hold_number' => 'HLD-20240101-0001',
        ]);

        $this->assertEquals('HLD-20240101-0001', $heldOrder->getDisplayName());
    }

    // ============================================================
    // GENERATE HOLD NUMBER TESTS
    // ============================================================

    public function test_generate_hold_number(): void
    {
        $holdNumber = HeldOrder::generateHoldNumber($this->outlet->id);

        $this->assertStringStartsWith('HLD-', $holdNumber);
        $this->assertStringContainsString(now()->format('Ymd'), $holdNumber);
    }

    public function test_generate_hold_number_increments(): void
    {
        // Create first held order
        HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        // Generate next hold number
        $holdNumber = HeldOrder::generateHoldNumber($this->outlet->id);

        // Should end with 0002
        $this->assertStringEndsWith('0002', $holdNumber);
    }

    public function test_generate_hold_number_resets_daily(): void
    {
        // Create held order for yesterday
        $yesterday = now()->subDay();
        HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'created_at' => $yesterday,
        ]);

        // Generate hold number for today
        $holdNumber = HeldOrder::generateHoldNumber($this->outlet->id);

        // Should end with 0001 (reset for new day)
        $this->assertStringEndsWith('0001', $holdNumber);
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_items_are_cast_to_array(): void
    {
        $items = [
            ['product_id' => 'uuid1', 'product_name' => 'Product 1', 'quantity' => 2],
        ];

        $heldOrder = HeldOrder::factory()->withItems($items)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertIsArray($heldOrder->items);
    }

    public function test_discounts_are_cast_to_array(): void
    {
        $discounts = [
            ['name' => 'Member Discount', 'type' => 'percentage', 'value' => 10, 'amount' => 5000],
        ];

        $heldOrder = HeldOrder::factory()->withDiscounts($discounts)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertIsArray($heldOrder->discounts);
        $this->assertEquals($discounts, $heldOrder->discounts);
    }

    public function test_decimal_fields_are_properly_cast(): void
    {
        $heldOrder = HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'subtotal' => 100000.50,
            'grand_total' => 110000.75,
        ]);

        $this->assertIsString($heldOrder->subtotal);
        $this->assertIsString($heldOrder->grand_total);
    }

    public function test_expires_at_is_cast_to_datetime(): void
    {
        $heldOrder = HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(\DateTimeInterface::class, $heldOrder->expires_at);
    }

    // ============================================================
    // TOTALS TESTS
    // ============================================================

    public function test_held_order_totals(): void
    {
        $heldOrder = HeldOrder::factory()->withTotals(
            subtotal: 100000,
            discountAmount: 10000,
            taxAmount: 9000,
            serviceCharge: 4500
        )->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(100000, $heldOrder->subtotal);
        $this->assertEquals(10000, $heldOrder->discount_amount);
        $this->assertEquals(9000, $heldOrder->tax_amount);
        $this->assertEquals(4500, $heldOrder->service_charge_amount);
        // grand_total = 100000 - 10000 + 9000 + 4500 = 103500
        $this->assertEquals(103500, $heldOrder->grand_total);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_with_customer_factory_state(): void
    {
        $heldOrder = HeldOrder::factory()->withCustomer()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertNotNull($heldOrder->customer_id);
        $this->assertInstanceOf(Customer::class, $heldOrder->customer);
    }

    public function test_with_reference_factory_state(): void
    {
        $heldOrder = HeldOrder::factory()->withReference('Test Reference')->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals('Test Reference', $heldOrder->reference);
    }

    public function test_with_table_number_factory_state(): void
    {
        $heldOrder = HeldOrder::factory()->withTableNumber('A5')->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals('A5', $heldOrder->table_number);
    }

    public function test_expired_factory_state(): void
    {
        $heldOrder = HeldOrder::factory()->expired()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($heldOrder->expires_at->isPast());
        $this->assertTrue($heldOrder->isExpired());
    }

    public function test_not_expiring_factory_state(): void
    {
        $heldOrder = HeldOrder::factory()->notExpiring()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertNull($heldOrder->expires_at);
    }

    public function test_expires_in_factory_state(): void
    {
        $heldOrder = HeldOrder::factory()->expiresIn(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($heldOrder->expires_at->isFuture());
        $this->assertFalse($heldOrder->isExpired());
    }

    // ============================================================
    // NOTES TESTS
    // ============================================================

    public function test_held_order_can_have_notes(): void
    {
        $heldOrder = HeldOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'notes' => 'Customer will return in 30 minutes',
        ]);

        $this->assertEquals('Customer will return in 30 minutes', $heldOrder->notes);
    }

    // ============================================================
    // ITEMS STRUCTURE TESTS
    // ============================================================

    public function test_items_contain_expected_structure(): void
    {
        $items = [
            [
                'product_id' => 'uuid-123',
                'product_name' => 'Nasi Goreng',
                'quantity' => 2,
                'unit_price' => 25000,
                'subtotal' => 50000,
                'modifiers' => [
                    ['name' => 'Extra Egg', 'price' => 5000],
                ],
                'notes' => 'No chili',
            ],
        ];

        $heldOrder = HeldOrder::factory()->withItems($items)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertCount(1, $heldOrder->items);
        $this->assertEquals('Nasi Goreng', $heldOrder->items[0]['product_name']);
        $this->assertEquals(2, $heldOrder->items[0]['quantity']);
        $this->assertEquals('No chili', $heldOrder->items[0]['notes']);
    }
}
