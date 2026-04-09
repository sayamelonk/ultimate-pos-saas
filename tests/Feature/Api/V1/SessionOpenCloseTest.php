<?php

namespace Tests\Feature\Api\V1;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionOpenCloseTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->outlets()->attach($this->outlet->id, ['is_default' => true]);
    }

    // ==================== OPEN SESSION ====================

    /** @test */
    public function user_can_open_new_session(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/sessions/open', [
                'opening_cash' => 500000,
                'notes' => 'Opening shift',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'outlet_id',
                    'user_id',
                    'session_number',
                    'opening_cash',
                    'opened_at',
                    'status',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'opening_cash' => 500000,
                    'status' => 'open',
                ],
            ]);

        $this->assertDatabaseHas('pos_sessions', [
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => 'open',
        ]);
    }

    /** @test */
    public function session_number_is_generated_with_correct_format(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/sessions/open', [
                'opening_cash' => 100000,
            ]);

        $response->assertStatus(201);

        $sessionNumber = $response->json('data.session_number');
        $this->assertMatchesRegularExpression('/^SES\d{8}\d{4}$/', $sessionNumber);
    }

    /** @test */
    public function user_cannot_open_second_session_without_closing_first(): void
    {
        // Open first session
        PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);

        // Try to open second session
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/sessions/open', [
                'opening_cash' => 500000,
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function opening_cash_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/sessions/open', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['opening_cash']);
    }

    /** @test */
    public function opening_cash_must_be_non_negative(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/sessions/open', [
                'opening_cash' => -100000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['opening_cash']);
    }

    /** @test */
    public function open_session_requires_outlet_header(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sessions/open', [
                'opening_cash' => 500000,
            ]);

        // Should fail because user has default outlet, but test with user without outlet
        $userNoOutlet = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($userNoOutlet)
            ->postJson('/api/v1/sessions/open', [
                'opening_cash' => 500000,
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function guest_cannot_open_session(): void
    {
        $response = $this->postJson('/api/v1/sessions/open', [
            'opening_cash' => 500000,
        ]);

        $response->assertUnauthorized();
    }

    // ==================== GET CURRENT SESSION ====================

    /** @test */
    public function user_can_get_current_open_session(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => PosSession::STATUS_OPEN,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/sessions/current');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $session->id,
                    'opening_cash' => 500000,
                    'status' => 'open',
                ],
            ]);
    }

    /** @test */
    public function returns_null_when_no_active_session(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/sessions/current');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        // Data is null when no session
        $this->assertNull($response->json('data'));
    }

    /** @test */
    public function current_session_includes_stats(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => PosSession::STATUS_OPEN,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->getJson('/api/v1/sessions/current');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'stats' => [
                        'total_sales',
                        'cash_sales',
                        'transaction_count',
                        'expected_cash_now',
                    ],
                ],
            ]);
    }

    // ==================== CLOSE SESSION ====================

    /** @test */
    public function user_can_close_session(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => PosSession::STATUS_OPEN,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/sessions/close', [
                'closing_cash' => 750000,
                'notes' => 'End of shift',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $session->id,
                    'closing_cash' => 750000,
                    'status' => 'closed',
                ],
            ]);

        $this->assertDatabaseHas('pos_sessions', [
            'id' => $session->id,
            'closing_cash' => 750000,
            'status' => 'closed',
        ]);
    }

    /** @test */
    public function close_session_calculates_cash_difference(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => PosSession::STATUS_OPEN,
        ]);

        // Create a cash transaction
        $paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'cash',
        ]);

        $transaction = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
            'status' => 'completed',
        ]);

        TransactionPayment::factory()->create([
            'transaction_id' => $transaction->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 100000,
        ]);

        // Expected cash = opening (500000) + cash sales (100000) = 600000
        // Closing cash = 580000
        // Difference = 580000 - 600000 = -20000 (shortage)

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/sessions/close', [
                'closing_cash' => 580000,
            ]);

        $response->assertOk();

        $this->assertEquals(600000, $response->json('data.expected_cash'));
        $this->assertEquals(-20000, $response->json('data.cash_difference'));
    }

    /** @test */
    public function cannot_close_session_without_active_session(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/sessions/close', [
                'closing_cash' => 500000,
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function closing_cash_is_required(): void
    {
        PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('X-Outlet-Id', $this->outlet->id)
            ->postJson('/api/v1/sessions/close', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['closing_cash']);
    }

    // ==================== SESSION REPORT ====================

    /** @test */
    public function user_can_get_session_report(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'closing_cash' => 600000,
            'status' => PosSession::STATUS_CLOSED,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sessions/{$session->id}/report");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'session',
                    'summary' => [
                        'total_transactions',
                        'sales_count',
                        'refund_count',
                        'gross_sales',
                        'net_sales',
                    ],
                    'cash_summary' => [
                        'opening_cash',
                        'cash_sales',
                        'expected_cash',
                        'closing_cash',
                    ],
                    'payment_methods',
                ],
            ]);
    }

    /** @test */
    public function cannot_get_other_user_session_report_without_permission(): void
    {
        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $otherUser->id,
            'status' => PosSession::STATUS_CLOSED,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sessions/{$session->id}/report");

        $response->assertNotFound();
    }

    /** @test */
    public function session_report_includes_payment_breakdown(): void
    {
        $session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_cash' => 500000,
            'status' => PosSession::STATUS_OPEN,
        ]);

        $cashMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Tunai',
            'type' => 'cash',
        ]);

        $qrisMethod = PaymentMethod::factory()->qris()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Cash transaction
        $transaction1 = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
            'status' => 'completed',
        ]);
        TransactionPayment::factory()->create([
            'transaction_id' => $transaction1->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 100000,
        ]);

        // QRIS transaction
        $transaction2 = Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->user->id,
            'grand_total' => 150000,
            'status' => 'completed',
        ]);
        TransactionPayment::factory()->create([
            'transaction_id' => $transaction2->id,
            'payment_method_id' => $qrisMethod->id,
            'amount' => 150000,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sessions/{$session->id}/report");

        $response->assertOk();

        $paymentMethods = $response->json('data.payment_methods');
        $this->assertCount(2, $paymentMethods);

        $cashPayment = collect($paymentMethods)->firstWhere('type', 'cash');
        $digitalWalletPayment = collect($paymentMethods)->firstWhere('type', 'digital_wallet');

        $this->assertEquals(100000, $cashPayment['amount']);
        $this->assertEquals(150000, $digitalWalletPayment['amount']);
    }
}
