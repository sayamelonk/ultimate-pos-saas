<?php

namespace Tests\Feature\Api\V2;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected PosSession $session;

    protected ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $this->category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ==========================================
    // SALES SUMMARY
    // ==========================================

    /** @test */
    public function can_get_sales_summary(): void
    {
        // Create some transactions
        Transaction::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'status' => Transaction::STATUS_COMPLETED,
            'grand_total' => 100000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/reports/sales/summary', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_sales',
                    'total_transactions',
                    'average_transaction',
                    'total_discount',
                    'total_tax',
                    'total_service_charge',
                    'net_sales',
                    'total_refunds',
                    'total_voids',
                ],
            ]);

        $this->assertEquals(500000, $response->json('data.total_sales'));
        $this->assertEquals(5, $response->json('data.total_transactions'));
    }

    /** @test */
    public function guest_cannot_get_sales_summary(): void
    {
        $response = $this->getJson('/api/v2/reports/sales/summary');

        $response->assertUnauthorized();
    }

    /** @test */
    public function sales_summary_filtered_by_date_range(): void
    {
        // Today's transactions
        Transaction::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'status' => Transaction::STATUS_COMPLETED,
            'grand_total' => 100000,
            'completed_at' => now(),
        ]);

        // Yesterday's transactions
        Transaction::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'status' => Transaction::STATUS_COMPLETED,
            'grand_total' => 100000,
            'completed_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($this->user);

        $today = now()->format('Y-m-d');
        $response = $this->getJson("/api/v2/reports/sales/summary?from={$today}&to={$today}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals(300000, $response->json('data.total_sales'));
        $this->assertEquals(3, $response->json('data.total_transactions'));
    }

    /** @test */
    public function sales_summary_excludes_voided_transactions(): void
    {
        Transaction::factory()->count(3)->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        Transaction::factory()->count(2)->voided()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/reports/sales/summary', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals(300000, $response->json('data.total_sales'));
        $this->assertEquals(3, $response->json('data.total_transactions'));
    }

    /** @test */
    public function sales_summary_includes_refund_info(): void
    {
        // Normal transactions
        Transaction::factory()->count(2)->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'type' => Transaction::TYPE_SALE,
            'grand_total' => 100000,
        ]);

        // Refund transaction
        Transaction::factory()->completed()->refund()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 50000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/reports/sales/summary', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals(50000, $response->json('data.total_refunds'));
    }

    // ==========================================
    // SALES BY PAYMENT METHOD
    // ==========================================

    /** @test */
    public function can_get_sales_by_payment_method(): void
    {
        $cashMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $qrisMethod = PaymentMethod::factory()->qris()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Cash transactions
        $transaction1 = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);
        TransactionPayment::factory()->create([
            'transaction_id' => $transaction1->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 100000,
        ]);

        $transaction2 = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 150000,
        ]);
        TransactionPayment::factory()->create([
            'transaction_id' => $transaction2->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 150000,
        ]);

        // QRIS transaction
        $transaction3 = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 80000,
        ]);
        TransactionPayment::factory()->create([
            'transaction_id' => $transaction3->id,
            'payment_method_id' => $qrisMethod->id,
            'amount' => 80000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/reports/sales/by-payment-method', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'payment_method_id',
                        'payment_method_name',
                        'payment_method_type',
                        'total_amount',
                        'transaction_count',
                        'percentage',
                    ],
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_sales_by_payment_method(): void
    {
        $response = $this->getJson('/api/v2/reports/sales/by-payment-method');

        $response->assertUnauthorized();
    }

    // ==========================================
    // SALES BY CATEGORY
    // ==========================================

    /** @test */
    public function can_get_sales_by_category(): void
    {
        $category1 = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Beverages']);
        $category2 = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Food']);

        $product1 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category1->id,
        ]);
        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category2->id,
        ]);

        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'unit_price' => 25000,
            'subtotal' => 50000,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => 35000,
            'subtotal' => 35000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/reports/sales/by-category', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'category_id',
                        'category_name',
                        'total_quantity',
                        'total_amount',
                        'item_count',
                        'percentage',
                    ],
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_sales_by_category(): void
    {
        $response = $this->getJson('/api/v2/reports/sales/by-category');

        $response->assertUnauthorized();
    }

    // ==========================================
    // SALES BY PRODUCT
    // ==========================================

    /** @test */
    public function can_get_sales_by_product(): void
    {
        $product1 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Kopi Susu',
        ]);

        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Es Teh',
        ]);

        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product1->id,
            'quantity' => 5,
            'unit_price' => 25000,
            'subtotal' => 125000,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'unit_price' => 15000,
            'subtotal' => 45000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/reports/sales/by-product', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'product_id',
                        'product_name',
                        'product_sku',
                        'category_name',
                        'total_quantity',
                        'total_amount',
                        'transaction_count',
                    ],
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_sales_by_product(): void
    {
        $response = $this->getJson('/api/v2/reports/sales/by-product');

        $response->assertUnauthorized();
    }

    /** @test */
    public function sales_by_product_sorted_by_quantity(): void
    {
        $product1 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Product A',
        ]);

        $product2 = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'name' => 'Product B',
        ]);

        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product1->id,
            'quantity' => 10,
            'unit_price' => 10000,
            'subtotal' => 100000,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product2->id,
            'quantity' => 20,
            'unit_price' => 10000,
            'subtotal' => 200000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/reports/sales/by-product?sort=quantity&order=desc', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('Product B', $data[0]['product_name']);
        $this->assertEquals(20, $data[0]['total_quantity']);
    }

    // ==========================================
    // HOURLY SALES
    // ==========================================

    /** @test */
    public function can_get_hourly_sales(): void
    {
        // Create transactions at different hours
        Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
            'completed_at' => now()->setTime(10, 30),
        ]);

        Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 150000,
            'completed_at' => now()->setTime(10, 45),
        ]);

        Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 80000,
            'completed_at' => now()->setTime(14, 0),
        ]);

        Sanctum::actingAs($this->user);

        $today = now()->format('Y-m-d');
        $response = $this->getJson("/api/v2/reports/sales/hourly?date={$today}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'hour',
                        'total_sales',
                        'transaction_count',
                    ],
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_hourly_sales(): void
    {
        $response = $this->getJson('/api/v2/reports/sales/hourly');

        $response->assertUnauthorized();
    }

    // ==========================================
    // DAILY SALES
    // ==========================================

    /** @test */
    public function can_get_daily_sales(): void
    {
        // Transactions for different days
        Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
            'completed_at' => now(),
        ]);

        Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 150000,
            'completed_at' => now()->subDay(),
        ]);

        Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 200000,
            'completed_at' => now()->subDays(2),
        ]);

        Sanctum::actingAs($this->user);

        $from = now()->subDays(7)->format('Y-m-d');
        $to = now()->format('Y-m-d');
        $response = $this->getJson("/api/v2/reports/sales/daily?from={$from}&to={$to}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'date',
                        'total_sales',
                        'transaction_count',
                        'average_transaction',
                    ],
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_daily_sales(): void
    {
        $response = $this->getJson('/api/v2/reports/sales/daily');

        $response->assertUnauthorized();
    }

    // ==========================================
    // SESSION REPORT
    // ==========================================

    /** @test */
    public function can_get_session_report(): void
    {
        $cashMethod = PaymentMethod::factory()->cash()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $transaction = Transaction::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        TransactionPayment::factory()->create([
            'transaction_id' => $transaction->id,
            'payment_method_id' => $cashMethod->id,
            'amount' => 100000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v2/reports/sessions/{$this->session->id}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'session_id',
                    'session_number',
                    'cashier_name',
                    'opened_at',
                    'closed_at',
                    'status',
                    'opening_cash',
                    'closing_cash',
                    'expected_cash',
                    'cash_difference',
                    'total_sales',
                    'total_transactions',
                    'total_refunds',
                    'total_voids',
                    'payment_breakdown' => [
                        '*' => [
                            'payment_method',
                            'amount',
                            'count',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_session_report(): void
    {
        $response = $this->getJson("/api/v2/reports/sessions/{$this->session->id}");

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

        $response = $this->getJson("/api/v2/reports/sessions/{$otherSession->id}", [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertNotFound();
    }

    // ==========================================
    // TENANT ISOLATION
    // ==========================================

    /** @test */
    public function reports_only_include_tenant_data(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherOutlet = Outlet::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherSession = PosSession::factory()->create([
            'outlet_id' => $otherOutlet->id,
            'user_id' => $otherUser->id,
        ]);

        // This tenant's transactions
        Transaction::factory()->count(3)->completed()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'grand_total' => 100000,
        ]);

        // Other tenant's transactions
        Transaction::factory()->count(2)->completed()->create([
            'tenant_id' => $otherTenant->id,
            'outlet_id' => $otherOutlet->id,
            'pos_session_id' => $otherSession->id,
            'user_id' => $otherUser->id,
            'grand_total' => 200000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/reports/sales/summary', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        // Only this tenant's transactions (3 x 100000)
        $this->assertEquals(300000, $response->json('data.total_sales'));
        $this->assertEquals(3, $response->json('data.total_transactions'));
    }
}
