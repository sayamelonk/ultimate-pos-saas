<?php

namespace Tests\Feature\Api\V2;

use App\Models\Customer;
use App\Models\Floor;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOutlet;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SyncApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Outlet $outlet;

    private PosSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->outlet = Outlet::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->session = PosSession::factory()->create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);
    }

    // ==================== GET /sync/master ====================

    /** @test */
    public function can_get_master_sync_data(): void
    {
        // Create test data
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $products = Product::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        // Assign products to outlet
        foreach ($products as $product) {
            ProductOutlet::create([
                'product_id' => $product->id,
                'outlet_id' => $this->outlet->id,
                'is_available' => true,
            ]);
        }

        PaymentMethod::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $floor = Floor::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
        ]);

        Table::factory()->count(4)->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'floor_id' => $floor->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'categories',
                    'products',
                    'payment_methods',
                    'floors',
                    'outlet',
                    'sync_timestamp',
                    'counts' => [
                        'categories',
                        'products',
                        'payment_methods',
                        'floors',
                        'tables',
                    ],
                ],
            ]);

        $this->assertEquals(1, $response->json('data.counts.categories'));
        $this->assertEquals(3, $response->json('data.counts.products'));
        $this->assertEquals(2, $response->json('data.counts.payment_methods'));
        $this->assertEquals(1, $response->json('data.counts.floors'));
        $this->assertEquals(4, $response->json('data.counts.tables'));
    }

    /** @test */
    public function guest_cannot_get_master_sync(): void
    {
        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function master_sync_requires_outlet(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master');

        $response->assertStatus(400);
    }

    /** @test */
    public function master_sync_excludes_inactive_products(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $activeProduct = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        // Assign active product to outlet
        ProductOutlet::create([
            'product_id' => $activeProduct->id,
            'outlet_id' => $this->outlet->id,
            'is_available' => true,
        ]);

        $inactiveProduct = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'is_active' => false,
            'show_in_pos' => true,
        ]);

        // Also assign inactive product to outlet
        ProductOutlet::create([
            'product_id' => $inactiveProduct->id,
            'outlet_id' => $this->outlet->id,
            'is_available' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.counts.products'));
    }

    /** @test */
    public function master_sync_includes_product_variants(): void
    {
        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'is_active' => true,
            'show_in_pos' => true,
            'product_type' => 'variant',
        ]);

        // Assign product to outlet
        ProductOutlet::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'is_available' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        // Product structure should include variants array
        $this->assertArrayHasKey('products', $response->json('data'));
    }

    /** @test */
    public function master_sync_only_returns_tenant_data(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCategory = ProductCategory::factory()->create([
            'tenant_id' => $otherTenant->id,
            'is_active' => true,
        ]);

        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/master', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.counts.categories'));
    }

    // ==================== GET /sync/delta ====================

    /** @test */
    public function can_get_delta_sync_data(): void
    {
        $since = now()->subHour();

        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'updated_at' => now(),
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/delta?since='.$since->format('Y-m-d H:i:s'), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'categories',
                    'products',
                    'tables',
                    'payment_methods',
                    'sync_timestamp',
                    'counts',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_delta_sync(): void
    {
        $response = $this->getJson('/api/v2/sync/delta?since='.now()->subHour()->format('Y-m-d H:i:s'), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function delta_sync_requires_since_parameter(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/delta', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['since']);
    }

    /** @test */
    public function delta_sync_marks_deleted_items(): void
    {
        $since = now()->subHour();

        $category = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false,
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/delta?since='.$since->format('Y-m-d H:i:s'), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();

        $categories = $response->json('data.categories');
        $this->assertNotEmpty($categories);
        $this->assertTrue($categories[0]['_deleted']);
    }

    /** @test */
    public function delta_sync_only_returns_updated_records(): void
    {
        $since = now()->subMinute();

        // Old record - should not be included
        ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'updated_at' => now()->subDays(2),
        ]);

        // New record - should be included
        ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/delta?since='.$since->format('Y-m-d H:i:s'), [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.counts.categories'));
    }

    // ==================== POST /sync/transactions ====================

    /** @test */
    public function can_upload_offline_transactions(): void
    {
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
        ]);
        $paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sync/transactions', [
            'session_id' => $this->session->id,
            'transactions' => [
                [
                    'local_id' => 'offline-txn-001',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 2,
                            'unit_price' => 50000,
                        ],
                    ],
                    'order_type' => 'dine_in',
                    'subtotal' => 100000,
                    'grand_total' => 100000,
                    'payments' => [
                        [
                            'payment_method_id' => $paymentMethod->id,
                            'amount' => 100000,
                        ],
                    ],
                    'created_at' => now()->toIso8601String(),
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'results' => [
                        '*' => [
                            'local_id',
                            'status',
                            'server_id',
                            'transaction_number',
                        ],
                    ],
                    'summary' => [
                        'total',
                        'success',
                        'duplicate',
                        'error',
                    ],
                ],
            ]);

        $this->assertEquals(1, $response->json('data.summary.success'));
    }

    /** @test */
    public function guest_cannot_upload_transactions(): void
    {
        $response = $this->postJson('/api/v2/sync/transactions', [
            'session_id' => $this->session->id,
            'transactions' => [],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function upload_detects_duplicate_transactions(): void
    {
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
        ]);
        $paymentMethod = PaymentMethod::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create existing transaction with local_id
        Transaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'outlet_id' => $this->outlet->id,
            'pos_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'notes' => 'local_id:offline-txn-duplicate',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sync/transactions', [
            'session_id' => $this->session->id,
            'transactions' => [
                [
                    'local_id' => 'offline-txn-duplicate',
                    'items' => [
                        ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50000],
                    ],
                    'order_type' => 'dine_in',
                    'subtotal' => 50000,
                    'grand_total' => 50000,
                    'payments' => [
                        ['payment_method_id' => $paymentMethod->id, 'amount' => 50000],
                    ],
                    'created_at' => now()->toIso8601String(),
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.summary.duplicate'));
    }

    /** @test */
    public function upload_requires_valid_session(): void
    {
        Sanctum::actingAs($this->user);

        // Use a valid UUID format but non-existent session
        $fakeSessionId = '00000000-0000-0000-0000-000000000000';

        $response = $this->postJson('/api/v2/sync/transactions', [
            'session_id' => $fakeSessionId,
            'transactions' => [
                [
                    'local_id' => 'test-001',
                    'items' => [
                        ['product_id' => '00000000-0000-0000-0000-000000000001', 'quantity' => 1, 'unit_price' => 10000],
                    ],
                    'order_type' => 'dine_in',
                    'subtotal' => 10000,
                    'grand_total' => 10000,
                    'payments' => [
                        ['payment_method_id' => '00000000-0000-0000-0000-000000000002', 'amount' => 10000],
                    ],
                    'created_at' => now()->toIso8601String(),
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function upload_validates_transaction_structure(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sync/transactions', [
            'session_id' => $this->session->id,
            'transactions' => [
                [
                    // Missing required fields
                ],
            ],
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnprocessable();
    }

    // ==================== POST /sync/session ====================

    /** @test */
    public function can_sync_open_session(): void
    {
        // Close existing session first
        $this->session->update(['status' => PosSession::STATUS_CLOSED]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sync/sessions', [
            'action' => 'open',
            'local_id' => 'offline-session-001',
            'opening_cash' => 500000,
            'opened_at' => now()->toIso8601String(),
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'session_id',
                    'session_number',
                    'status',
                ],
            ]);

        $this->assertEquals('opened', $response->json('data.status'));
    }

    /** @test */
    public function guest_cannot_sync_session(): void
    {
        $response = $this->postJson('/api/v2/sync/sessions', [
            'action' => 'open',
            'local_id' => 'test',
            'opening_cash' => 500000,
            'opened_at' => now()->toIso8601String(),
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function sync_open_returns_existing_session(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sync/sessions', [
            'action' => 'open',
            'local_id' => 'offline-session-002',
            'opening_cash' => 500000,
            'opened_at' => now()->toIso8601String(),
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals('existing', $response->json('data.status'));
        $this->assertEquals($this->session->id, $response->json('data.session_id'));
    }

    /** @test */
    public function can_sync_close_session(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sync/sessions', [
            'action' => 'close',
            'local_id' => 'offline-close-001',
            'session_id' => $this->session->id,
            'closing_cash' => 750000,
            'closed_at' => now()->toIso8601String(),
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'session_id',
                    'status',
                    'expected_cash',
                    'cash_difference',
                ],
            ]);

        $this->assertEquals('closed', $response->json('data.status'));

        $this->assertDatabaseHas('pos_sessions', [
            'id' => $this->session->id,
            'status' => PosSession::STATUS_CLOSED,
        ]);
    }

    /** @test */
    public function sync_close_returns_already_closed(): void
    {
        $this->session->update(['status' => PosSession::STATUS_CLOSED]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/sync/sessions', [
            'action' => 'close',
            'local_id' => 'offline-close-002',
            'session_id' => $this->session->id,
            'closing_cash' => 750000,
            'closed_at' => now()->toIso8601String(),
        ], [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk();
        $this->assertEquals('already_closed', $response->json('data.status'));
    }

    // ==================== GET /sync/customers/search ====================

    /** @test */
    public function can_search_customers(): void
    {
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Jane Smith',
            'phone' => '089876543210',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/customers/search?q=john');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'phone',
                        'email',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function guest_cannot_search_customers(): void
    {
        $response = $this->getJson('/api/v2/sync/customers/search?q=test');

        $response->assertUnauthorized();
    }

    /** @test */
    public function customer_search_requires_min_2_chars(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/customers/search?q=a');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }

    /** @test */
    public function customer_search_by_phone(): void
    {
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Customer',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/customers/search?q=08123');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function customer_search_excludes_inactive(): void
    {
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Active Customer',
            'is_active' => true,
        ]);

        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Inactive Customer',
            'is_active' => false,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/customers/search?q=Customer');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function customer_search_only_returns_tenant_customers(): void
    {
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'My Customer',
            'is_active' => true,
        ]);

        $otherTenant = Tenant::factory()->create();
        Customer::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Customer',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/customers/search?q=Customer');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function customer_search_limits_results(): void
    {
        Customer::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Customer',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/sync/customers/search?q=Test');

        $response->assertOk();
        $this->assertLessThanOrEqual(20, count($response->json('data')));
    }
}
