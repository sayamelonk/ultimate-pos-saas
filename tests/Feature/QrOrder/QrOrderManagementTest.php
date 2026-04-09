<?php

namespace Tests\Feature\QrOrder;

use App\Models\Floor;
use App\Models\KitchenStation;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QrOrder;
use App\Models\QrOrderItem;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Outlet $outlet;

    private User $user;

    private Table $table;

    private QrOrder $qrOrder;

    protected function setUp(): void
    {
        parent::setUp();

        SubscriptionPlan::factory()->professional()->create();
        $proPlan = SubscriptionPlan::where('slug', 'professional')->first();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $proPlan->id,
        ]);

        $this->user->outlets()->attach($this->outlet->id, ['is_default' => true]);

        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);
        $this->table->generateQrToken();

        KitchenStation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
        ]);

        // Create a QR order
        $this->qrOrder = QrOrder::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'table_id' => $this->table->id,
            'order_number' => 'QR-TEST-20260303-0001',
            'customer_name' => 'Test Customer',
            'status' => QrOrder::STATUS_PAY_AT_COUNTER,
            'payment_method' => QrOrder::PAYMENT_PAY_AT_COUNTER,
            'subtotal' => 50000,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'grand_total' => 50000,
            'tax_mode' => 'exclusive',
            'tax_percentage' => 0,
            'service_charge_percentage' => 0,
        ]);

        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'base_price' => 25000,
        ]);

        QrOrderItem::create([
            'qr_order_id' => $this->qrOrder->id,
            'product_id' => $product->id,
            'item_name' => $product->name,
            'item_sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => 25000,
            'modifiers_total' => 0,
            'subtotal' => 50000,
        ]);
    }

    public function test_can_view_qr_orders_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('qr-orders.index'));

        $response->assertOk();
        $response->assertViewIs('qr-orders.index');
        $response->assertViewHas('activeOrders');
        $response->assertViewHas('completedOrders');
    }

    public function test_can_view_qr_order_detail(): void
    {
        $response = $this->actingAs($this->user)->get(route('qr-orders.show', $this->qrOrder));

        $response->assertOk();
        $response->assertViewIs('qr-orders.show');
        $response->assertViewHas('order');
    }

    public function test_can_cancel_qr_order(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('qr-orders.cancel', $this->qrOrder));

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->qrOrder->refresh();
        $this->assertEquals(QrOrder::STATUS_CANCELLED, $this->qrOrder->status);
    }

    public function test_cannot_cancel_completed_order(): void
    {
        $this->qrOrder->markAsCompleted();

        $response = $this->actingAs($this->user)->postJson(route('qr-orders.cancel', $this->qrOrder));

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_can_complete_pay_at_counter_order(): void
    {
        // Create an active POS session and payment method
        PosSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'open',
        ]);

        PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('qr-orders.complete', $this->qrOrder));

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_cannot_complete_non_pay_at_counter_order(): void
    {
        $this->qrOrder->update(['status' => QrOrder::STATUS_WAITING_PAYMENT]);

        $response = $this->actingAs($this->user)->postJson(route('qr-orders.complete', $this->qrOrder));

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_can_poll_pending_orders(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('qr-orders.poll-pending'));

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'count']);
    }

    public function test_can_generate_qr_for_table(): void
    {
        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $newTable = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('qr-orders.generate-qr', $newTable));

        $response->assertOk()
            ->assertJsonStructure(['success', 'qr_token', 'qr_url']);

        $newTable->refresh();
        $this->assertTrue($newTable->hasQrCode());
    }

    public function test_can_revoke_qr_for_table(): void
    {
        $response = $this->actingAs($this->user)->deleteJson(route('qr-orders.revoke-qr', $this->table));

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->table->refresh();
        $this->assertFalse($this->table->hasQrCode());
    }

    public function test_can_download_qr_code(): void
    {
        $response = $this->actingAs($this->user)->get(route('qr-orders.download-qr', $this->table));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_can_view_print_qr_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('qr-orders.print-qr', $this->table));

        $response->assertOk();
        $response->assertViewIs('qr-orders.print-qr');
    }

    public function test_unauthorized_user_cannot_access_other_tenant_order(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

        // Without QR Order feature, the user gets redirected by feature middleware
        $response = $this->actingAs($otherUser)->get(route('qr-orders.show', $this->qrOrder));

        // User without qr_order feature gets redirected to subscription plans
        $response->assertRedirect(route('subscription.plans'));
    }
}
