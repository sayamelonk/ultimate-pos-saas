<?php

namespace Tests\Feature\Api\V2;

use App\Models\CashDrawerLog;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashDrawerApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected PosSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 200000,
        ]);
    }

    // ==========================================
    // GET CASH DRAWER STATUS
    // ==========================================

    /** @test */
    public function authenticated_user_can_get_cash_drawer_status(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/cash-drawer/status', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'session_id',
                    'session_number',
                    'opening_cash',
                    'current_balance',
                    'cash_sales',
                    'cash_refunds',
                    'cash_in_total',
                    'cash_out_total',
                    'expected_cash',
                    'opened_at',
                    'status',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_cash_drawer_status(): void
    {
        $response = $this->getJson('/api/v2/cash-drawer/status');

        $response->assertUnauthorized();
    }

    /** @test */
    public function returns_null_when_no_active_session(): void
    {
        $this->session->update(['status' => PosSession::STATUS_CLOSED]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/cash-drawer/status', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data', null);
    }

    /** @test */
    public function cash_drawer_status_calculates_balance_correctly(): void
    {
        // Add some cash drawer logs
        CashDrawerLog::factory()->cashIn()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'amount' => 50000,
            'balance_before' => 200000,
            'balance_after' => 250000,
        ]);

        CashDrawerLog::factory()->cashOut()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'amount' => 30000,
            'balance_before' => 250000,
            'balance_after' => 220000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/cash-drawer/status', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals(200000, $data['opening_cash']);
        $this->assertEquals(50000, $data['cash_in_total']);
        $this->assertEquals(30000, $data['cash_out_total']);
    }

    // ==========================================
    // GET CASH DRAWER LOGS
    // ==========================================

    /** @test */
    public function can_get_cash_drawer_logs(): void
    {
        CashDrawerLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/cash-drawer/logs', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'type_label',
                        'amount',
                        'balance_before',
                        'balance_after',
                        'reference',
                        'reason',
                        'user_name',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function guest_cannot_get_cash_drawer_logs(): void
    {
        $response = $this->getJson('/api/v2/cash-drawer/logs');

        $response->assertUnauthorized();
    }

    /** @test */
    public function cash_drawer_logs_filtered_by_session(): void
    {
        // Logs for current session
        CashDrawerLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        // Logs for another session
        $anotherSession = PosSession::factory()->closed()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        CashDrawerLog::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $anotherSession->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/cash-drawer/logs?session_id={$this->session->id}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function cash_drawer_logs_filtered_by_type(): void
    {
        CashDrawerLog::factory()->cashIn()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        CashDrawerLog::factory()->cashOut()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/cash-drawer/logs?type=cash_in', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function cash_drawer_logs_filtered_by_date(): void
    {
        // Today's logs
        CashDrawerLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        // Yesterday's logs
        CashDrawerLog::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($this->user);

        $today = now()->format('Y-m-d');
        $response = $this->getJson("/api/v2/cash-drawer/logs?date={$today}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function only_tenant_logs_are_returned(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherOutlet = Outlet::factory()->create(['tenant_id' => $otherTenant->id]);

        CashDrawerLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        CashDrawerLog::factory()->count(2)->create([
            'tenant_id' => $otherTenant->id,
            'outlet_id' => $otherOutlet->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/cash-drawer/logs', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    // ==========================================
    // CASH IN
    // ==========================================

    /** @test */
    public function can_perform_cash_in(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/cash-in', [
            'amount' => 50000,
            'reason' => 'Change refill',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'amount',
                    'balance_before',
                    'balance_after',
                    'reason',
                ],
            ]);

        $this->assertEquals(CashDrawerLog::TYPE_CASH_IN, $response->json('data.type'));
        $this->assertEquals(50000, $response->json('data.amount'));

        $this->assertDatabaseHas('cash_drawer_logs', [
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'type' => CashDrawerLog::TYPE_CASH_IN,
            'amount' => 50000,
            'reason' => 'Change refill',
        ]);
    }

    /** @test */
    public function guest_cannot_perform_cash_in(): void
    {
        $response = $this->postJson('/api/v2/cash-drawer/cash-in', [
            'amount' => 50000,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function cash_in_requires_amount(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/cash-in', [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function cash_in_amount_must_be_positive(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/cash-in', [
            'amount' => -50000,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function cash_in_requires_active_session(): void
    {
        $this->session->update(['status' => PosSession::STATUS_CLOSED]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/cash-in', [
            'amount' => 50000,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'No active session found');
    }

    /** @test */
    public function cash_in_updates_balance_correctly(): void
    {
        // Initial opening balance is 200000
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/cash-in', [
            'amount' => 50000,
            'reason' => 'Test cash in',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals(200000, $response->json('data.balance_before'));
        $this->assertEquals(250000, $response->json('data.balance_after'));
    }

    // ==========================================
    // CASH OUT
    // ==========================================

    /** @test */
    public function can_perform_cash_out(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/cash-out', [
            'amount' => 30000,
            'reason' => 'Bank deposit',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'amount',
                    'balance_before',
                    'balance_after',
                    'reason',
                ],
            ]);

        $this->assertEquals(CashDrawerLog::TYPE_CASH_OUT, $response->json('data.type'));
        $this->assertEquals(30000, $response->json('data.amount'));
    }

    /** @test */
    public function guest_cannot_perform_cash_out(): void
    {
        $response = $this->postJson('/api/v2/cash-drawer/cash-out', [
            'amount' => 30000,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function cash_out_requires_amount(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/cash-out', [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function cash_out_requires_reason(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/cash-out', [
            'amount' => 30000,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function cash_out_cannot_exceed_balance(): void
    {
        // Opening cash is 200000
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/cash-out', [
            'amount' => 300000,
            'reason' => 'Too much',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function cash_out_requires_active_session(): void
    {
        $this->session->update(['status' => PosSession::STATUS_CLOSED]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/cash-out', [
            'amount' => 30000,
            'reason' => 'Test',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'No active session found');
    }

    /** @test */
    public function cash_out_updates_balance_correctly(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/cash-out', [
            'amount' => 30000,
            'reason' => 'Test cash out',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals(200000, $response->json('data.balance_before'));
        $this->assertEquals(170000, $response->json('data.balance_after'));
    }

    // ==========================================
    // GET CASH DRAWER BALANCE
    // ==========================================

    /** @test */
    public function can_get_current_balance(): void
    {
        // Add some transactions
        CashDrawerLog::factory()->cashIn()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'amount' => 50000,
            'balance_before' => 200000,
            'balance_after' => 250000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/cash-drawer/balance', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'current_balance',
                    'opening_cash',
                    'cash_in_total',
                    'cash_out_total',
                    'cash_sales',
                    'cash_refunds',
                ],
            ]);

        $this->assertEquals(250000, $response->json('data.current_balance'));
    }

    /** @test */
    public function guest_cannot_get_balance(): void
    {
        $response = $this->getJson('/api/v2/cash-drawer/balance');

        $response->assertUnauthorized();
    }

    // ==========================================
    // OPEN CASH DRAWER
    // ==========================================

    /** @test */
    public function can_open_cash_drawer(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/cash-drawer/open', [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Cash drawer opened');
    }

    /** @test */
    public function guest_cannot_open_cash_drawer(): void
    {
        $response = $this->postJson('/api/v2/cash-drawer/open');

        $response->assertUnauthorized();
    }

    // ==========================================
    // TENANT ISOLATION
    // ==========================================

    /** @test */
    public function cannot_access_other_tenant_cash_drawer(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherOutlet = Outlet::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherSession = PosSession::factory()->create([
            'outlet_id' => $otherOutlet->id,
            'user_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($this->user);

        // Try to access other tenant's outlet
        $response = $this->getJson('/api/v2/cash-drawer/status', [
            'X-Outlet-Id' => $otherOutlet->id,
        ]);

        // Should return null or forbidden
        $response->assertOk()
            ->assertJsonPath('data', null);
    }
}
