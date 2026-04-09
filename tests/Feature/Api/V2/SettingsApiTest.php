<?php

namespace Tests\Feature\Api\V2;

use App\Models\Outlet;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->outlet = Outlet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'tax_percentage' => 10,
            'service_charge_percentage' => 5,
        ]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // ==========================================
    // GET OUTLET SETTINGS
    // ==========================================

    /** @test */
    public function can_get_outlet_settings(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/outlet', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'outlet_id',
                    'outlet_name',
                    'outlet_code',
                    'address',
                    'city',
                    'phone',
                    'email',
                    'tax_percentage',
                    'service_charge_percentage',
                    'opening_time',
                    'closing_time',
                    'receipt_header',
                    'receipt_footer',
                    'receipt_show_logo',
                    'currency',
                    'timezone',
                ],
            ]);

        $this->assertEquals($this->outlet->id, $response->json('data.outlet_id'));
        $this->assertEquals(10, $response->json('data.tax_percentage'));
        $this->assertEquals(5, $response->json('data.service_charge_percentage'));
    }

    /** @test */
    public function guest_cannot_get_outlet_settings(): void
    {
        $response = $this->getJson('/api/v2/settings/outlet');

        $response->assertUnauthorized();
    }

    /** @test */
    public function cannot_get_other_tenant_outlet_settings(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherOutlet = Outlet::factory()->create(['tenant_id' => $otherTenant->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/outlet', [
            'X-Outlet-Id' => $otherOutlet->id,
        ]);

        $response->assertNotFound();
    }

    // ==========================================
    // GET POS SETTINGS
    // ==========================================

    /** @test */
    public function can_get_pos_settings(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/pos', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'require_customer',
                    'allow_negative_stock',
                    'auto_print_receipt',
                    'default_order_type',
                    'enable_table_management',
                    'enable_kitchen_display',
                    'enable_customer_display',
                    'receipt_printer_type',
                    'receipt_paper_size',
                    'enable_cash_drawer',
                    'enable_barcode_scanner',
                    'session_required',
                    'allow_held_orders',
                    'held_order_expiry_hours',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_pos_settings(): void
    {
        $response = $this->getJson('/api/v2/settings/pos');

        $response->assertUnauthorized();
    }

    // ==========================================
    // GET AUTHORIZATION SETTINGS
    // ==========================================

    /** @test */
    public function can_get_authorization_settings(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/authorization', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'require_auth_for_void',
                    'require_auth_for_refund',
                    'require_auth_for_discount',
                    'require_auth_for_price_change',
                    'require_auth_for_cash_out',
                    'max_discount_without_auth',
                    'manager_pin_required',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_authorization_settings(): void
    {
        $response = $this->getJson('/api/v2/settings/authorization');

        $response->assertUnauthorized();
    }

    // ==========================================
    // GET RECEIPT SETTINGS
    // ==========================================

    /** @test */
    public function can_get_receipt_settings(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/receipt', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'header',
                    'footer',
                    'show_logo',
                    'logo_url',
                    'paper_size',
                    'show_cashier_name',
                    'show_outlet_address',
                    'show_outlet_phone',
                    'show_tax_breakdown',
                    'show_payment_method',
                    'show_transaction_number',
                    'show_qr_code',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_receipt_settings(): void
    {
        $response = $this->getJson('/api/v2/settings/receipt');

        $response->assertUnauthorized();
    }

    // ==========================================
    // GET PRINTER SETTINGS
    // ==========================================

    /** @test */
    public function can_get_printer_settings(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/printer', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'receipt_printer' => [
                        'enabled',
                        'type',
                        'ip_address',
                        'port',
                        'paper_size',
                    ],
                    'kitchen_printer' => [
                        'enabled',
                        'type',
                        'ip_address',
                        'port',
                    ],
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_printer_settings(): void
    {
        $response = $this->getJson('/api/v2/settings/printer');

        $response->assertUnauthorized();
    }

    // ==========================================
    // GET SUBSCRIPTION INFO
    // ==========================================

    /** @test */
    public function can_get_subscription_info(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/subscription');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'plan_name',
                    'plan_slug',
                    'status',
                    'is_trial',
                    'trial_ends_at',
                    'current_period_start',
                    'current_period_end',
                    'outlet_limit',
                    'user_limit',
                    'product_limit',
                    'outlets_used',
                    'users_used',
                    'products_used',
                    'features',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_subscription_info(): void
    {
        $response = $this->getJson('/api/v2/settings/subscription');

        $response->assertUnauthorized();
    }

    // ==========================================
    // GET ALL SETTINGS (BUNDLED)
    // ==========================================

    /** @test */
    public function can_get_all_settings(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings', [
            'X-Outlet-Id' => $this->outlet->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'outlet',
                    'pos',
                    'authorization',
                    'receipt',
                    'printer',
                    'subscription',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_all_settings(): void
    {
        $response = $this->getJson('/api/v2/settings');

        $response->assertUnauthorized();
    }

    // ==========================================
    // GET FEATURE FLAGS
    // ==========================================

    /** @test */
    public function can_get_feature_flags(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/features');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'pos_core',
                    'product_variants',
                    'product_combos',
                    'modifiers',
                    'table_management',
                    'inventory_basic',
                    'inventory_advanced',
                    'recipe_bom',
                    'stock_transfer',
                    'manager_authorization',
                    'waiter_app',
                    'qr_order',
                    'kds',
                    'api_access',
                    'custom_branding',
                    'export_excel',
                    'loyalty_points',
                    'discounts',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_get_feature_flags(): void
    {
        $response = $this->getJson('/api/v2/settings/features');

        $response->assertUnauthorized();
    }

    /** @test */
    public function can_check_specific_feature(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/features/pos_core');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'feature',
                    'enabled',
                    'required_plan',
                ],
            ]);
    }

    /** @test */
    public function returns_404_for_unknown_feature(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/settings/features/unknown_feature');

        $response->assertNotFound();
    }
}
