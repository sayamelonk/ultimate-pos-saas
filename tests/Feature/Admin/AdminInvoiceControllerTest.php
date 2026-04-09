<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::factory()->create([
            'slug' => 'super-admin',
            'name' => 'Super Admin',
            'is_system' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'tenant_id' => null,
            'email_verified_at' => now(),
        ]);

        $this->superAdmin->roles()->attach($this->superAdminRole);
    }

    private function createInvoiceWithRelations(array $invoiceAttributes = []): SubscriptionInvoice
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->forTenant($tenant)->withPlan($plan)->create();

        return SubscriptionInvoice::factory()
            ->forTenant($tenant)
            ->forSubscription($subscription)
            ->forPlan($plan)
            ->create($invoiceAttributes);
    }

    // ==================== INDEX TESTS ====================

    public function test_super_admin_can_access_invoices_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.invoices.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.invoices.index');
    }

    public function test_invoices_index_displays_all_invoices(): void
    {
        $invoice1 = $this->createInvoiceWithRelations();
        $invoice2 = $this->createInvoiceWithRelations();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.invoices.index'));

        $response->assertStatus(200);
        $response->assertSee($invoice1->invoice_number);
        $response->assertSee($invoice2->invoice_number);
    }

    public function test_invoices_can_be_filtered_by_status(): void
    {
        $paidInvoice = $this->createInvoiceWithRelations(['status' => 'paid']);
        $pendingInvoice = $this->createInvoiceWithRelations(['status' => 'pending']);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.invoices.index', ['status' => 'paid']));

        $response->assertStatus(200);
        $response->assertSee($paidInvoice->invoice_number);
        $response->assertDontSee($pendingInvoice->invoice_number);
    }

    public function test_invoices_can_be_searched_by_invoice_number(): void
    {
        $invoice1 = $this->createInvoiceWithRelations(['invoice_number' => 'INV-2024-001']);
        $invoice2 = $this->createInvoiceWithRelations(['invoice_number' => 'INV-2024-999']);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.invoices.index', ['search' => '001']));

        $response->assertStatus(200);
        $response->assertSee('INV-2024-001');
        $response->assertDontSee('INV-2024-999');
    }

    public function test_invoices_can_be_filtered_by_date_range(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->forTenant($tenant)->withPlan($plan)->create();

        $oldInvoice = SubscriptionInvoice::factory()
            ->forTenant($tenant)
            ->forSubscription($subscription)
            ->forPlan($plan)
            ->create(['created_at' => now()->subMonths(2)]);

        $recentInvoice = SubscriptionInvoice::factory()
            ->forTenant($tenant)
            ->forSubscription($subscription)
            ->forPlan($plan)
            ->create(['created_at' => now()]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.invoices.index', [
            'from' => now()->subWeek()->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertSee($recentInvoice->invoice_number);
        $response->assertDontSee($oldInvoice->invoice_number);
    }

    public function test_guest_cannot_access_invoices_index(): void
    {
        $response = $this->get(route('admin.invoices.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_super_admin_cannot_access_invoices_index(): void
    {
        $tenant = Tenant::factory()->create();
        $regularUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($regularUser)->get(route('admin.invoices.index'));

        $response->assertStatus(403);
    }

    // ==================== STATS TESTS ====================

    public function test_index_provides_invoice_statistics(): void
    {
        $this->createInvoiceWithRelations(['status' => 'paid', 'amount' => 100000]);
        $this->createInvoiceWithRelations(['status' => 'paid', 'amount' => 200000]);
        $this->createInvoiceWithRelations(['status' => 'pending', 'amount' => 150000]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.invoices.index'));

        $response->assertStatus(200);
        $response->assertViewHas('stats', function ($stats) {
            return $stats['count_paid'] === 2
                && $stats['count_pending'] === 1
                && $stats['total_paid'] == 300000
                && $stats['total_pending'] == 150000;
        });
    }

    // ==================== SHOW TESTS ====================

    public function test_super_admin_can_view_invoice_details(): void
    {
        $invoice = $this->createInvoiceWithRelations();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertViewIs('admin.invoices.show');
        $response->assertViewHas('invoice');
    }

    public function test_show_page_displays_invoice_details(): void
    {
        $invoice = $this->createInvoiceWithRelations([
            'invoice_number' => 'INV-TEST-123',
            'amount' => 299000,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertSee('INV-TEST-123');
        $response->assertSee('299.000');
    }

    public function test_show_page_loads_related_models(): void
    {
        $invoice = $this->createInvoiceWithRelations();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertViewHas('invoice', function ($inv) {
            return $inv->relationLoaded('tenant')
                && $inv->relationLoaded('plan')
                && $inv->relationLoaded('subscription');
        });
    }

    // ==================== UPDATE STATUS TESTS ====================

    public function test_super_admin_can_update_invoice_status_to_paid(): void
    {
        $invoice = $this->createInvoiceWithRelations(['status' => 'pending', 'paid_at' => null]);

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.invoices.update-status', $invoice),
            ['status' => 'paid']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_super_admin_can_update_invoice_status_to_cancelled(): void
    {
        $invoice = $this->createInvoiceWithRelations(['status' => 'pending']);

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.invoices.update-status', $invoice),
            ['status' => 'cancelled']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscription_invoices', [
            'id' => $invoice->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_super_admin_can_update_invoice_status_to_expired(): void
    {
        $invoice = $this->createInvoiceWithRelations(['status' => 'pending']);

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.invoices.update-status', $invoice),
            ['status' => 'expired']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscription_invoices', [
            'id' => $invoice->id,
            'status' => 'expired',
        ]);
    }

    public function test_update_status_validates_allowed_statuses(): void
    {
        $invoice = $this->createInvoiceWithRelations();

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.invoices.update-status', $invoice),
            ['status' => 'invalid_status']
        );

        $response->assertSessionHasErrors('status');
    }

    public function test_update_status_requires_status_field(): void
    {
        $invoice = $this->createInvoiceWithRelations();

        $response = $this->actingAs($this->superAdmin)->patch(
            route('admin.invoices.update-status', $invoice),
            []
        );

        $response->assertSessionHasErrors('status');
    }

    public function test_marking_invoice_paid_sets_paid_at_timestamp(): void
    {
        $invoice = $this->createInvoiceWithRelations(['status' => 'pending', 'paid_at' => null]);

        $this->actingAs($this->superAdmin)->patch(
            route('admin.invoices.update-status', $invoice),
            ['status' => 'paid']
        );

        $invoice->refresh();
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_marking_already_paid_invoice_does_not_change_paid_at(): void
    {
        $originalPaidAt = now()->subDays(5);
        $invoice = $this->createInvoiceWithRelations([
            'status' => 'paid',
            'paid_at' => $originalPaidAt,
        ]);

        $this->actingAs($this->superAdmin)->patch(
            route('admin.invoices.update-status', $invoice),
            ['status' => 'paid']
        );

        $invoice->refresh();
        $this->assertEquals($originalPaidAt->toDateTimeString(), $invoice->paid_at->toDateTimeString());
    }

    // ==================== PAGINATION TESTS ====================

    public function test_invoices_are_paginated(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->forTenant($tenant)->withPlan($plan)->create();

        for ($i = 0; $i < 25; $i++) {
            SubscriptionInvoice::factory()
                ->forTenant($tenant)
                ->forSubscription($subscription)
                ->forPlan($plan)
                ->create();
        }

        $response = $this->actingAs($this->superAdmin)->get(route('admin.invoices.index'));

        $response->assertStatus(200);
        $response->assertViewHas('invoices', function ($invoices) {
            return $invoices->count() === 20 && $invoices->total() === 25;
        });
    }

    // ==================== VIEW DATA TESTS ====================

    public function test_index_provides_tenants_for_filter_dropdown(): void
    {
        Tenant::factory()->count(5)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.invoices.index'));

        $response->assertStatus(200);
        $response->assertViewHas('tenants', function ($tenants) {
            return $tenants->count() === 5;
        });
    }
}
