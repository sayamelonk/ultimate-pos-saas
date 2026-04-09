<?php

namespace Tests\Feature\Api\V1;

use App\Models\HeldOrder;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HeldOrderTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected Outlet $outlet;

    protected PosSession $session;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet->users()->attach($this->user->id);

        PaymentMethod::factory()->cash()->create(['tenant_id' => $this->tenant->id]);

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

    protected function createHeldOrderPayload(array $overrides = []): array
    {
        return array_merge([
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => 'Nasi Goreng',
                    'quantity' => 2,
                    'unit_price' => 25000,
                    'subtotal' => 50000,
                    'notes' => 'Extra spicy',
                ],
            ],
            'reference' => 'Customer A',
            'table_number' => '5',
            'notes' => 'Customer waiting',
        ], $overrides);
    }

    // ==================== CREATE HELD ORDER ====================

    public function test_can_create_held_order(): void
    {
        $payload = $this->createHeldOrderPayload();

        $response = $this->postJson('/api/v1/held-orders', $payload, [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'hold_number',
                    'reference',
                    'table_number',
                    'items_count',
                    'grand_total',
                    'notes',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('held_orders', [
            'outlet_id' => $this->outlet->id,
            'reference' => 'Customer A',
            'table_number' => '5',
        ]);
    }

    public function test_held_order_generates_unique_hold_number(): void
    {
        $payload = $this->createHeldOrderPayload();

        $response1 = $this->postJson('/api/v1/held-orders', $payload, [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response2 = $this->postJson('/api/v1/held-orders', $payload, [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $holdNumber1 = $response1->json('data.hold_number');
        $holdNumber2 = $response2->json('data.hold_number');

        $this->assertNotEquals($holdNumber1, $holdNumber2);
        $this->assertStringStartsWith('HLD', $holdNumber1);
    }

    public function test_held_order_calculates_grand_total(): void
    {
        $payload = $this->createHeldOrderPayload([
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => 'Nasi Goreng',
                    'quantity' => 3,
                    'unit_price' => 25000,
                    'subtotal' => 75000,
                ],
            ],
            'subtotal' => 75000,
            'grand_total' => 75000,
        ]);

        $response = $this->postJson('/api/v1/held-orders', $payload, [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(201);
        $this->assertEquals(75000, $response->json('data.grand_total'));
    }

    public function test_create_held_order_requires_items(): void
    {
        $payload = $this->createHeldOrderPayload(['items' => []]);

        $response = $this->postJson('/api/v1/held-orders', $payload, [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_create_held_order_requires_active_session(): void
    {
        $this->session->update(['status' => 'closed']);

        $payload = $this->createHeldOrderPayload();

        $response = $this->postJson('/api/v1/held-orders', $payload, [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400);
    }

    public function test_create_held_order_requires_outlet(): void
    {
        $payload = $this->createHeldOrderPayload();

        $response = $this->postJson('/api/v1/held-orders', $payload);

        $response->assertStatus(400);
    }

    // ==================== LIST HELD ORDERS ====================

    public function test_can_list_held_orders(): void
    {
        // Create held orders via API
        $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload(['reference' => 'Order 1']), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);
        $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload(['reference' => 'Order 2']), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);
        $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload(['reference' => 'Order 3']), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response = $this->getJson('/api/v1/held-orders', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'hold_number',
                        'reference',
                        'items_count',
                        'grand_total',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_filter_held_orders_by_session(): void
    {
        // Create order in current session
        $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload(['reference' => 'Current Session']), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response = $this->getJson('/api/v1/held-orders?session_only=true', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_list_only_shows_outlet_held_orders(): void
    {
        $otherOutlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherSession = PosSession::factory()->create([
            'outlet_id' => $otherOutlet->id,
            'user_id' => $this->user->id,
            'status' => 'open',
        ]);

        // Create order in current outlet
        $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload(['reference' => 'My Outlet']), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        // Create order in other outlet (attach user first)
        $otherOutlet->users()->attach($this->user->id);
        $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload(['reference' => 'Other Outlet']), [
            'X-Outlet-Id' => $otherOutlet->id,
        ]);

        // List from current outlet
        $response = $this->getJson('/api/v1/held-orders', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('My Outlet', $response->json('data.0.reference'));
    }

    // ==================== GET HELD ORDER ====================

    public function test_can_get_held_order_detail(): void
    {
        $createResponse = $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload(), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $heldOrderId = $createResponse->json('data.id');

        $response = $this->getJson("/api/v1/held-orders/{$heldOrderId}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'hold_number',
                    'reference',
                    'table_number',
                    'items',
                    'subtotal',
                    'grand_total',
                    'notes',
                    'created_at',
                ],
            ]);
    }

    public function test_get_held_order_returns_404_for_invalid_id(): void
    {
        $response = $this->getJson('/api/v1/held-orders/00000000-0000-0000-0000-000000000000', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(404);
    }

    // ==================== DELETE HELD ORDER ====================

    public function test_can_delete_held_order(): void
    {
        $createResponse = $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload(), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $heldOrderId = $createResponse->json('data.id');

        $response = $this->deleteJson("/api/v1/held-orders/{$heldOrderId}", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('held_orders', [
            'id' => $heldOrderId,
        ]);
    }

    // ==================== RESTORE HELD ORDER ====================

    public function test_can_restore_held_order_to_cart(): void
    {
        $createResponse = $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload(), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $heldOrderId = $createResponse->json('data.id');

        $response = $this->postJson("/api/v1/held-orders/{$heldOrderId}/restore", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'table_number',
                    'notes',
                    'grand_total',
                    'held_order_id',
                    'hold_number',
                ],
            ]);

        // Order should be deleted after restore by default
        $this->assertDatabaseMissing('held_orders', [
            'id' => $heldOrderId,
        ]);
    }

    public function test_restore_can_keep_held_order(): void
    {
        $createResponse = $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload(), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $heldOrderId = $createResponse->json('data.id');

        $response = $this->postJson("/api/v1/held-orders/{$heldOrderId}/restore?delete_after_restore=false", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);

        // Order should still exist
        $this->assertDatabaseHas('held_orders', [
            'id' => $heldOrderId,
        ]);
    }

    public function test_cannot_restore_expired_held_order(): void
    {
        $createResponse = $this->postJson('/api/v1/held-orders', array_merge(
            $this->createHeldOrderPayload(),
            ['expires_in_hours' => 1]
        ), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $heldOrderId = $createResponse->json('data.id');

        // Manually expire the order
        HeldOrder::where('id', $heldOrderId)->update([
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->postJson("/api/v1/held-orders/{$heldOrderId}/restore", [], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400);
    }

    // ==================== EXPIRY ====================

    public function test_expired_orders_excluded_by_default(): void
    {
        // Create normal order
        $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload(['reference' => 'Active Order']), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        // Create and expire an order
        $expiredResponse = $this->postJson('/api/v1/held-orders', array_merge(
            $this->createHeldOrderPayload(['reference' => 'Expired Order']),
            ['expires_in_hours' => 1]
        ), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        HeldOrder::where('id', $expiredResponse->json('data.id'))->update([
            'expires_at' => now()->subHour(),
        ]);

        // List should only show active order
        $response = $this->getJson('/api/v1/held-orders', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);
        $references = collect($response->json('data'))->pluck('reference')->toArray();
        $this->assertContains('Active Order', $references);
        $this->assertNotContains('Expired Order', $references);
    }

    public function test_can_include_expired_orders(): void
    {
        // Create and expire an order
        $expiredResponse = $this->postJson('/api/v1/held-orders', array_merge(
            $this->createHeldOrderPayload(['reference' => 'Expired Order']),
            ['expires_in_hours' => 1]
        ), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        HeldOrder::where('id', $expiredResponse->json('data.id'))->update([
            'expires_at' => now()->subHour(),
        ]);

        // Include expired
        $response = $this->getJson('/api/v1/held-orders?include_expired=true', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(200);
        $references = collect($response->json('data'))->pluck('reference')->toArray();
        $this->assertContains('Expired Order', $references);
    }

    // ==================== DISPLAY NAME ====================

    public function test_display_name_shows_reference(): void
    {
        $response = $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload([
            'reference' => 'John Doe',
        ]), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $this->assertEquals('John Doe', $response->json('data.display_name'));
    }

    public function test_display_name_shows_table_when_no_reference(): void
    {
        $response = $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload([
            'reference' => null,
            'table_number' => '10',
        ]), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $this->assertEquals('Table 10', $response->json('data.display_name'));
    }

    public function test_display_name_shows_hold_number_as_fallback(): void
    {
        $response = $this->postJson('/api/v1/held-orders', $this->createHeldOrderPayload([
            'reference' => null,
            'table_number' => null,
        ]), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $holdNumber = $response->json('data.hold_number');
        $displayName = $response->json('data.display_name');
        $this->assertEquals($holdNumber, $displayName);
    }
}
