<?php

namespace Tests\Unit\Models;

use App\Models\CashDrawerLog;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashDrawerLogTest extends TestCase
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

    public function test_can_create_cash_drawer_log(): void
    {
        $log = CashDrawerLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'reference' => 'REF-TEST-001',
        ]);

        $this->assertDatabaseHas('cash_drawer_logs', [
            'id' => $log->id,
            'reference' => 'REF-TEST-001',
        ]);
    }

    public function test_cash_drawer_log_belongs_to_tenant(): void
    {
        $log = CashDrawerLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $log->tenant);
        $this->assertEquals($this->tenant->id, $log->tenant->id);
    }

    public function test_cash_drawer_log_belongs_to_outlet(): void
    {
        $log = CashDrawerLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(Outlet::class, $log->outlet);
        $this->assertEquals($this->outlet->id, $log->outlet->id);
    }

    public function test_cash_drawer_log_belongs_to_pos_session(): void
    {
        $log = CashDrawerLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(PosSession::class, $log->posSession);
        $this->assertEquals($this->posSession->id, $log->posSession->id);
    }

    public function test_cash_drawer_log_belongs_to_user(): void
    {
        $log = CashDrawerLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($this->user->id, $log->user->id);
    }

    public function test_cash_drawer_log_can_belong_to_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $log = CashDrawerLog::factory()->sale()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'transaction_id' => $transaction->id,
        ]);

        $this->assertInstanceOf(Transaction::class, $log->transaction);
        $this->assertEquals($transaction->id, $log->transaction->id);
    }

    // ============================================================
    // TYPE CONSTANTS TESTS
    // ============================================================

    public function test_type_constants(): void
    {
        $this->assertEquals('cash_in', CashDrawerLog::TYPE_CASH_IN);
        $this->assertEquals('cash_out', CashDrawerLog::TYPE_CASH_OUT);
        $this->assertEquals('sale', CashDrawerLog::TYPE_SALE);
        $this->assertEquals('refund', CashDrawerLog::TYPE_REFUND);
        $this->assertEquals('opening', CashDrawerLog::TYPE_OPENING);
        $this->assertEquals('closing', CashDrawerLog::TYPE_CLOSING);
    }

    // ============================================================
    // IS INFLOW TESTS
    // ============================================================

    public function test_is_inflow_returns_true_for_cash_in(): void
    {
        $log = CashDrawerLog::factory()->cashIn()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($log->isInflow());
    }

    public function test_is_inflow_returns_true_for_sale(): void
    {
        $log = CashDrawerLog::factory()->sale()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($log->isInflow());
    }

    public function test_is_inflow_returns_true_for_opening(): void
    {
        $log = CashDrawerLog::factory()->opening()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($log->isInflow());
    }

    public function test_is_inflow_returns_false_for_cash_out(): void
    {
        $log = CashDrawerLog::factory()->cashOut()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($log->isInflow());
    }

    // ============================================================
    // IS OUTFLOW TESTS
    // ============================================================

    public function test_is_outflow_returns_true_for_cash_out(): void
    {
        $log = CashDrawerLog::factory()->cashOut()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($log->isOutflow());
    }

    public function test_is_outflow_returns_true_for_refund(): void
    {
        $log = CashDrawerLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'type' => CashDrawerLog::TYPE_REFUND,
        ]);

        $this->assertTrue($log->isOutflow());
    }

    public function test_is_outflow_returns_false_for_sale(): void
    {
        $log = CashDrawerLog::factory()->sale()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($log->isOutflow());
    }

    // ============================================================
    // GET TYPES TESTS
    // ============================================================

    public function test_get_types(): void
    {
        $types = CashDrawerLog::getTypes();

        $this->assertArrayHasKey(CashDrawerLog::TYPE_CASH_IN, $types);
        $this->assertArrayHasKey(CashDrawerLog::TYPE_CASH_OUT, $types);
        $this->assertArrayHasKey(CashDrawerLog::TYPE_SALE, $types);
        $this->assertArrayHasKey(CashDrawerLog::TYPE_REFUND, $types);
        $this->assertArrayHasKey(CashDrawerLog::TYPE_OPENING, $types);
        $this->assertArrayHasKey(CashDrawerLog::TYPE_CLOSING, $types);
    }

    public function test_get_type_label(): void
    {
        $this->assertEquals('Cash In', CashDrawerLog::getTypeLabel(CashDrawerLog::TYPE_CASH_IN));
        $this->assertEquals('Cash Out', CashDrawerLog::getTypeLabel(CashDrawerLog::TYPE_CASH_OUT));
        $this->assertEquals('Sale', CashDrawerLog::getTypeLabel(CashDrawerLog::TYPE_SALE));
        $this->assertEquals('Refund', CashDrawerLog::getTypeLabel(CashDrawerLog::TYPE_REFUND));
        $this->assertEquals('Opening Balance', CashDrawerLog::getTypeLabel(CashDrawerLog::TYPE_OPENING));
        $this->assertEquals('Closing Balance', CashDrawerLog::getTypeLabel(CashDrawerLog::TYPE_CLOSING));
    }

    public function test_get_type_label_returns_original_for_unknown(): void
    {
        $this->assertEquals('unknown', CashDrawerLog::getTypeLabel('unknown'));
    }

    // ============================================================
    // CASTING TESTS
    // ============================================================

    public function test_decimal_fields_are_properly_cast(): void
    {
        $log = CashDrawerLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
            'amount' => 50000.50,
            'balance_before' => 200000.25,
            'balance_after' => 250000.75,
        ]);

        $this->assertIsString($log->amount);
        $this->assertIsString($log->balance_before);
        $this->assertIsString($log->balance_after);
    }

    // ============================================================
    // BALANCE CALCULATION TESTS
    // ============================================================

    public function test_cash_in_increases_balance(): void
    {
        $log = CashDrawerLog::factory()
            ->withAmount(50000)
            ->withBalanceBefore(200000)
            ->cashIn()
            ->create([
                'tenant_id' => $this->tenant->id,
                'outlet_id' => $this->outlet->id,
                'pos_session_id' => $this->posSession->id,
                'user_id' => $this->user->id,
            ]);

        $this->assertEquals(250000, $log->balance_after);
    }

    public function test_cash_out_decreases_balance(): void
    {
        $log = CashDrawerLog::factory()
            ->withAmount(30000)
            ->withBalanceBefore(200000)
            ->cashOut()
            ->create([
                'tenant_id' => $this->tenant->id,
                'outlet_id' => $this->outlet->id,
                'pos_session_id' => $this->posSession->id,
                'user_id' => $this->user->id,
            ]);

        $this->assertEquals(170000, $log->balance_after);
    }

    public function test_sale_increases_balance(): void
    {
        $log = CashDrawerLog::factory()
            ->withAmount(75000)
            ->withBalanceBefore(200000)
            ->sale()
            ->create([
                'tenant_id' => $this->tenant->id,
                'outlet_id' => $this->outlet->id,
                'pos_session_id' => $this->posSession->id,
                'user_id' => $this->user->id,
            ]);

        $this->assertEquals(275000, $log->balance_after);
    }

    public function test_opening_starts_from_zero(): void
    {
        $log = CashDrawerLog::factory()
            ->withAmount(200000)
            ->opening()
            ->create([
                'tenant_id' => $this->tenant->id,
                'outlet_id' => $this->outlet->id,
                'pos_session_id' => $this->posSession->id,
                'user_id' => $this->user->id,
            ]);

        $this->assertEquals(0, $log->balance_before);
        $this->assertEquals(200000, $log->balance_after);
    }

    // ============================================================
    // FACTORY STATE TESTS
    // ============================================================

    public function test_cash_in_factory_state(): void
    {
        $log = CashDrawerLog::factory()->cashIn()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(CashDrawerLog::TYPE_CASH_IN, $log->type);
        $this->assertNotNull($log->reason);
    }

    public function test_cash_out_factory_state(): void
    {
        $log = CashDrawerLog::factory()->cashOut()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(CashDrawerLog::TYPE_CASH_OUT, $log->type);
        $this->assertNotNull($log->reason);
    }

    public function test_sale_factory_state(): void
    {
        $log = CashDrawerLog::factory()->sale()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(CashDrawerLog::TYPE_SALE, $log->type);
    }

    public function test_opening_factory_state(): void
    {
        $log = CashDrawerLog::factory()->opening()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(CashDrawerLog::TYPE_OPENING, $log->type);
        $this->assertEquals(0, $log->balance_before);
    }

    public function test_with_amount_factory_state(): void
    {
        $log = CashDrawerLog::factory()->withAmount(100000)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(100000, $log->amount);
    }

    public function test_with_balance_before_factory_state(): void
    {
        $log = CashDrawerLog::factory()->withBalanceBefore(500000)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->posSession->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(500000, $log->balance_before);
    }
}
