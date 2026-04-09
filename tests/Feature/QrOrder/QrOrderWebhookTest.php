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

class QrOrderWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Outlet $outlet;

    private QrOrder $qrOrder;

    protected function setUp(): void
    {
        parent::setUp();

        SubscriptionPlan::factory()->professional()->create();
        $proPlan = SubscriptionPlan::where('slug', 'professional')->first();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        Subscription::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $proPlan->id,
        ]);

        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
        ]);

        $table = Table::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
        ]);

        KitchenStation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
        ]);

        // Create POS session and payment method for transaction creation
        PosSession::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);

        PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'base_price' => 25000,
        ]);

        $this->qrOrder = QrOrder::create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'table_id' => $table->id,
            'order_number' => 'QR-TEST-20260303-0001',
            'status' => QrOrder::STATUS_WAITING_PAYMENT,
            'payment_method' => QrOrder::PAYMENT_QRIS,
            'xendit_invoice_id' => 'inv_test_123',
            'subtotal' => 25000,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'grand_total' => 25000,
            'tax_mode' => 'exclusive',
            'tax_percentage' => 0,
            'service_charge_percentage' => 0,
        ]);

        QrOrderItem::create([
            'qr_order_id' => $this->qrOrder->id,
            'product_id' => $product->id,
            'item_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 25000,
            'modifiers_total' => 0,
            'subtotal' => 25000,
        ]);
    }

    public function test_xendit_webhook_handles_qr_order_paid(): void
    {
        // Set webhook token to empty to bypass signature check
        config(['xendit.webhook_token' => '']);

        $response = $this->postJson(route('webhook.xendit.invoice'), [
            'id' => 'inv_test_123',
            'status' => 'PAID',
            'metadata' => [
                'type' => 'qr_order',
                'qr_order_id' => $this->qrOrder->id,
                'tenant_id' => $this->tenant->id,
            ],
            'payment_method' => 'QRIS',
            'paid_amount' => 25000,
            'paid_at' => now()->toISOString(),
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->qrOrder->refresh();
        // After payment success, status should be processing (or paid if no POS session)
        $this->assertContains($this->qrOrder->status, [
            QrOrder::STATUS_PAID,
            QrOrder::STATUS_PROCESSING,
        ]);
    }

    public function test_xendit_webhook_handles_qr_order_expired(): void
    {
        config(['xendit.webhook_token' => '']);

        $response = $this->postJson(route('webhook.xendit.invoice'), [
            'id' => 'inv_test_123',
            'status' => 'EXPIRED',
            'metadata' => [
                'type' => 'qr_order',
                'qr_order_id' => $this->qrOrder->id,
            ],
        ]);

        $response->assertOk();

        $this->qrOrder->refresh();
        $this->assertEquals(QrOrder::STATUS_EXPIRED, $this->qrOrder->status);
    }

    public function test_xendit_webhook_handles_qr_order_failed(): void
    {
        config(['xendit.webhook_token' => '']);

        $response = $this->postJson(route('webhook.xendit.invoice'), [
            'id' => 'inv_test_123',
            'status' => 'FAILED',
            'metadata' => [
                'type' => 'qr_order',
                'qr_order_id' => $this->qrOrder->id,
            ],
        ]);

        $response->assertOk();

        $this->qrOrder->refresh();
        $this->assertEquals(QrOrder::STATUS_CANCELLED, $this->qrOrder->status);
    }

    public function test_xendit_webhook_returns_404_for_unknown_qr_order(): void
    {
        config(['xendit.webhook_token' => '']);

        $response = $this->postJson(route('webhook.xendit.invoice'), [
            'id' => 'inv_test_unknown',
            'status' => 'PAID',
            'metadata' => [
                'type' => 'qr_order',
                'qr_order_id' => '00000000-0000-0000-0000-000000000000',
            ],
        ]);

        $response->assertNotFound();
    }
}
