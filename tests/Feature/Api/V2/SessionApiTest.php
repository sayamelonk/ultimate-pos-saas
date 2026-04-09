<?php

namespace Tests\Feature\Api\V2;

use App\Models\CashDrawerLog;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SessionApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ==================== GET /sessions/current ====================

    /** @test */
    public function authenticated_user_can_get_current_session(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sessions/current', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'session_number',
                    'opening_cash',
                    'status',
                    'opened_at',
                    'stats' => [
                        'total_sales',
                        'cash_sales',
                        'transaction_count',
                        'expected_cash',
                    ],
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_current_session(): void
    {
        $response = $this->getJson('/api/v2/sessions/current', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function returns_null_when_no_active_session(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sessions/current', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'No active session',
            ]);
    }

    /** @test */
    public function requires_outlet_header_for_current_session(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sessions/current');

        $response->assertStatus(400);
    }

    // ==================== POST /sessions/open ====================

    /** @test */
    public function can_open_new_session(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sessions/open', [
            'opening_cash' => 500000,
            'notes' => 'Opening shift',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'session_number',
                    'opening_cash',
                    'status',
                    'opened_at',
                ],
            ]);

        $this->assertDatabaseHas('pos_sessions', [
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => PosSession::STATUS_OPEN,
        ]);
    }

    /** @test */
    public function guest_cannot_open_session(): void
    {
        $response = $this->postJson('/api/v2/sessions/open', [
            'opening_cash' => 500000,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function cannot_open_session_when_already_open(): void
    {
        PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sessions/open', [
            'opening_cash' => 500000,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'You already have an open session. Please close it first.']);
    }

    /** @test */
    public function open_session_requires_opening_cash(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sessions/open', [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['opening_cash']);
    }

    /** @test */
    public function open_session_requires_non_negative_cash(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sessions/open', [
            'opening_cash' => -100,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['opening_cash']);
    }

    /** @test */
    public function opening_session_creates_cash_drawer_log(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sessions/open', [
            'opening_cash' => 500000,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertCreated();

        $sessionId = $response->json('data.id');

        $this->assertDatabaseHas('cash_drawer_logs', [
            'pos_session_id' => $sessionId,
            'type' => CashDrawerLog::TYPE_OPENING,
            'amount' => 500000,
        ]);
    }

    // ==================== POST /sessions/close ====================

    /** @test */
    public function can_close_session(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => PosSession::STATUS_OPEN,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sessions/close', [
            'closing_cash' => 750000,
            'notes' => 'End of shift',
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'session_number',
                    'opening_cash',
                    'closing_cash',
                    'expected_cash',
                    'cash_difference',
                    'status',
                    'opened_at',
                    'closed_at',
                ],
            ]);

        $this->assertDatabaseHas('pos_sessions', [
            'id' => $session->id,
            'closing_cash' => 750000,
            'status' => PosSession::STATUS_CLOSED,
        ]);
    }

    /** @test */
    public function guest_cannot_close_session(): void
    {
        $response = $this->postJson('/api/v2/sessions/close', [
            'closing_cash' => 750000,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function cannot_close_when_no_active_session(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sessions/close', [
            'closing_cash' => 750000,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'No active session to close.']);
    }

    /** @test */
    public function close_session_requires_closing_cash(): void
    {
        PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sessions/close', [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['closing_cash']);
    }

    /** @test */
    public function closing_session_calculates_expected_cash(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => PosSession::STATUS_OPEN,
        ]);

        // Create cash payment method
        $cashMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => PaymentMethod::TYPE_CASH,
            'name' => 'Cash',
        ]);

        // Create a transaction with cash payment
        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'status' => Transaction::STATUS_COMPLETED,
            'grand_total' => 250000,
        ]);

        TransactionPayment::create([
            'transaction_id' => $transaction->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 250000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sessions/close', [
            'closing_cash' => 750000,
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();

        $session->refresh();
        $this->assertEquals(750000, $session->expected_cash);
        $this->assertEquals(0, $session->cash_difference);
    }

    // ==================== GET /sessions/history ====================

    /** @test */
    public function can_get_session_history(): void
    {
        PosSession::factory()->count(3)->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_CLOSED,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sessions/history', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'session_number',
                        'opening_cash',
                        'closing_cash',
                        'status',
                        'opened_at',
                        'closed_at',
                    ],
                ],
                'meta',
            ]);
    }

    /** @test */
    public function guest_cannot_get_session_history(): void
    {
        $response = $this->getJson('/api/v2/sessions/history', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function session_history_is_paginated(): void
    {
        PosSession::factory()->count(25)->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sessions/history?per_page=10', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    /** @test */
    public function session_history_can_filter_by_date(): void
    {
        PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opened_at' => now()->subDays(5),
        ]);

        PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opened_at' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sessions/history?date='.now()->toDateString(), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    // ==================== GET /sessions/{session}/report ====================

    /** @test */
    public function can_get_session_report(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_CLOSED,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/sessions/{$session->id}/report");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'session',
                    'summary' => [
                        'total_transactions',
                        'sales_count',
                        'refund_count',
                        'gross_sales',
                        'total_refunds',
                        'net_sales',
                        'total_discount',
                        'total_tax',
                    ],
                    'cash_summary' => [
                        'opening_cash',
                        'cash_sales',
                        'cash_refunds',
                        'expected_cash',
                        'closing_cash',
                        'difference',
                    ],
                    'payment_methods',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_session_report(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v2/sessions/{$session->id}/report");

        $response->assertUnauthorized();
    }

    /** @test */
    public function cannot_get_other_tenant_session_report(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherOutlet = Outlet::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

        $otherSession = PosSession::factory()->create([
            'outlet_id' => $otherOutlet->id,
            'user_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/sessions/{$otherSession->id}/report");

        $response->assertNotFound();
    }

    // ==================== GET /sessions/active-any ====================

    /** @test */
    public function can_check_if_any_session_active_at_outlet(): void
    {
        PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sessions/active-any', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'has_active_session',
                    'session',
                ],
            ])
            ->assertJsonPath('data.has_active_session', true);
    }

    /** @test */
    public function returns_false_when_no_active_session_at_outlet(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sessions/active-any', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.has_active_session', false)
            ->assertJsonPath('data.session', null);
    }
}
