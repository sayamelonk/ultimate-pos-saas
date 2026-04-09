<?php

namespace Tests\Feature\Api\V1;

use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $owner;

    protected Tenant $tenant;

    protected SubscriptionPlan $starterPlan;

    protected SubscriptionPlan $growthPlan;

    protected SubscriptionPlan $professionalPlan;

    protected Role $ownerRole;

    protected Role $cashierRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->ownerRole = Role::create([
            'name' => 'Tenant Owner',
            'slug' => 'tenant-owner',
            'is_system' => true,
        ]);

        $this->cashierRole = Role::create([
            'name' => 'Cashier',
            'slug' => 'cashier',
            'is_system' => true,
        ]);

        // Create subscription plans
        $this->createSubscriptionPlans();

        // Create tenant and users
        $this->tenant = Tenant::factory()->create();

        $this->owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->owner->roles()->attach($this->ownerRole);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->user->roles()->attach($this->cashierRole);
    }

    protected function createSubscriptionPlans(): void
    {
        $this->starterPlan = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Perfect for small businesses',
            'price_monthly' => 99000,
            'price_yearly' => 990000,
            'max_outlets' => 1,
            'max_users' => 3,
            'max_products' => 100,
            'features' => [
                'pos_core' => true,
                'basic_reports' => true,
                'inventory_basic' => false,
                'inventory_advanced' => false,
            ],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->growthPlan = SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'description' => 'For growing businesses',
            'price_monthly' => 299000,
            'price_yearly' => 2990000,
            'max_outlets' => 3,
            'max_users' => 10,
            'max_products' => 500,
            'features' => [
                'pos_core' => true,
                'basic_reports' => true,
                'inventory_basic' => true,
                'inventory_advanced' => false,
                'table_management' => true,
            ],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->professionalPlan = SubscriptionPlan::create([
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'Full-featured for professionals',
            'price_monthly' => 599000,
            'price_yearly' => 5990000,
            'max_outlets' => 10,
            'max_users' => 50,
            'max_products' => -1,
            'features' => [
                'pos_core' => true,
                'basic_reports' => true,
                'inventory_basic' => true,
                'inventory_advanced' => true,
                'table_management' => true,
                'qr_order' => true,
                'api_access' => true,
            ],
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }

    // ==========================================
    // GET CURRENT SUBSCRIPTION STATUS
    // ==========================================

    /** @test */
    public function authenticated_user_can_get_current_subscription_status(): void
    {
        // Create an active subscription for the tenant
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 299000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/subscription');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'status_label',
                    'plan' => [
                        'id',
                        'name',
                        'slug',
                    ],
                    'billing_cycle',
                    'price',
                    'price_formatted',
                    'current_period_start',
                    'current_period_end',
                    'days_remaining',
                    'is_trial',
                    'is_active',
                    'can_use_system',
                    'can_create_transactions',
                ],
            ])
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.plan.slug', 'growth');
    }

    /** @test */
    public function returns_trial_subscription_status(): void
    {
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->professionalPlan->id,
            'status' => Subscription::STATUS_TRIAL,
            'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->addDays(14),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
            'price' => 0,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/subscription');

        $response->assertOk()
            ->assertJsonPath('data.status', 'trial')
            ->assertJsonPath('data.is_trial', true)
            ->assertJsonPath('data.can_use_system', true);
    }

    /** @test */
    public function returns_frozen_subscription_status(): void
    {
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->starterPlan->id,
            'status' => Subscription::STATUS_FROZEN,
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDay(),
            'frozen_at' => now(),
            'price' => 99000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/subscription');

        $response->assertOk()
            ->assertJsonPath('data.status', 'frozen')
            ->assertJsonPath('data.can_use_system', true)
            ->assertJsonPath('data.can_create_transactions', false);
    }

    /** @test */
    public function returns_null_when_no_subscription(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/subscription');

        $response->assertOk()
            ->assertJsonPath('data', null);
    }

    /** @test */
    public function guest_cannot_get_subscription_status(): void
    {
        $response = $this->getJson('/api/v1/subscription');

        $response->assertUnauthorized();
    }

    // ==========================================
    // SUBSCRIBE TO A PLAN
    // ==========================================

    /** @test */
    public function owner_can_subscribe_to_a_plan(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/subscribe', [
            'plan_id' => $this->starterPlan->id,
            'billing_cycle' => 'monthly',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'subscription' => [
                        'id',
                        'status',
                        'plan',
                    ],
                    'invoice' => [
                        'id',
                        'invoice_number',
                        'amount',
                        'payment_url',
                    ],
                ],
                'message',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->starterPlan->id,
            'status' => Subscription::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('subscription_invoices', [
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->starterPlan->id,
        ]);
    }

    /** @test */
    public function owner_can_subscribe_with_yearly_billing(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/subscribe', [
            'plan_id' => $this->growthPlan->id,
            'billing_cycle' => 'yearly',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subscription.billing_cycle', 'yearly');

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'billing_cycle' => 'yearly',
            'price' => 2990000,
        ]);
    }

    /** @test */
    public function non_owner_cannot_subscribe_to_a_plan(): void
    {
        Sanctum::actingAs($this->user); // cashier

        $response = $this->postJson('/api/v1/subscription/subscribe', [
            'plan_id' => $this->starterPlan->id,
            'billing_cycle' => 'monthly',
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function cannot_subscribe_with_invalid_plan(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/subscribe', [
            'plan_id' => 'invalid-uuid',
            'billing_cycle' => 'monthly',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_id']);
    }

    /** @test */
    public function cannot_subscribe_with_invalid_billing_cycle(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/subscribe', [
            'plan_id' => $this->starterPlan->id,
            'billing_cycle' => 'weekly',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['billing_cycle']);
    }

    /** @test */
    public function cannot_subscribe_when_already_has_active_subscription(): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->starterPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 99000,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/subscribe', [
            'plan_id' => $this->growthPlan->id,
            'billing_cycle' => 'monthly',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'You already have an active subscription. Please upgrade or cancel first.');
    }

    // ==========================================
    // START TRIAL
    // ==========================================

    /** @test */
    public function owner_can_start_trial(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/trial');

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'plan',
                    'trial_ends_at',
                    'days_remaining',
                ],
                'message',
            ])
            ->assertJsonPath('data.status', 'trial')
            ->assertJsonPath('data.plan.slug', 'professional');

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'status' => Subscription::STATUS_TRIAL,
        ]);
    }

    /** @test */
    public function cannot_start_trial_twice(): void
    {
        // Already had a trial (now expired)
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->professionalPlan->id,
            'status' => Subscription::STATUS_EXPIRED,
            'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->subDays(1),
            'current_period_start' => now()->subDays(15),
            'current_period_end' => now()->subDays(1),
            'price' => 0,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/trial');

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Trial period has already been used.');
    }

    // ==========================================
    // UPGRADE SUBSCRIPTION
    // ==========================================

    /** @test */
    public function owner_can_upgrade_subscription(): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->starterPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 99000,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/upgrade', [
            'plan_id' => $this->growthPlan->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'subscription',
                    'proration' => [
                        'remaining_days',
                        'credit_amount',
                        'new_plan_price',
                        'amount_due',
                    ],
                    'invoice',
                ],
                'message',
            ]);
    }

    /** @test */
    public function cannot_upgrade_to_lower_plan(): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 299000,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/upgrade', [
            'plan_id' => $this->starterPlan->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Cannot upgrade to a lower tier plan. Please use downgrade instead.');
    }

    /** @test */
    public function cannot_upgrade_to_same_plan(): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 299000,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/upgrade', [
            'plan_id' => $this->growthPlan->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'You are already subscribed to this plan.');
    }

    // ==========================================
    // CANCEL SUBSCRIPTION
    // ==========================================

    /** @test */
    public function owner_can_cancel_subscription(): void
    {
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 299000,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/cancel', [
            'reason' => 'Testing cancellation',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'cancelled_at',
                    'cancellation_reason',
                    'access_until',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => Subscription::STATUS_CANCELLED,
        ]);
    }

    /** @test */
    public function owner_can_cancel_trial(): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->professionalPlan->id,
            'status' => Subscription::STATUS_TRIAL,
            'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->addDays(10),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(10),
            'price' => 0,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/cancel', [
            'reason' => 'Changed my mind',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function cannot_cancel_already_cancelled_subscription(): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_CANCELLED,
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now(),
            'cancelled_at' => now()->subDays(5),
            'price' => 299000,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/cancel', [
            'reason' => 'Trying again',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Subscription is already cancelled.');
    }

    // ==========================================
    // REACTIVATE SUBSCRIPTION (from frozen)
    // ==========================================

    /** @test */
    public function owner_can_reactivate_frozen_subscription(): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_FROZEN,
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDay(),
            'frozen_at' => now(),
            'price' => 299000,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/reactivate');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'subscription',
                    'invoice',
                ],
                'message',
            ]);
    }

    /** @test */
    public function cannot_reactivate_active_subscription(): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 299000,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/subscription/reactivate');

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Subscription is not frozen or expired.');
    }

    // ==========================================
    // GET INVOICES
    // ==========================================

    /** @test */
    public function authenticated_user_can_get_invoice_history(): void
    {
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 299000,
        ]);

        // Create some invoices
        SubscriptionInvoice::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'invoice_number' => 'INV-2026-0001',
            'amount' => 299000,
            'tax_amount' => 32890,
            'total_amount' => 331890,
            'status' => 'paid',
            'billing_cycle' => 'monthly',
            'period_start' => now()->subMonth(),
            'period_end' => now(),
            'paid_at' => now()->subDays(25),
        ]);

        SubscriptionInvoice::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'invoice_number' => 'INV-2026-0002',
            'amount' => 299000,
            'tax_amount' => 32890,
            'total_amount' => 331890,
            'status' => 'pending',
            'billing_cycle' => 'monthly',
            'period_start' => now(),
            'period_end' => now()->addMonth(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/subscription/invoices');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'invoice_number',
                        'amount',
                        'tax_amount',
                        'total_amount',
                        'total_amount_formatted',
                        'status',
                        'status_label',
                        'billing_cycle',
                        'period_start',
                        'period_end',
                        'plan_name',
                        'paid_at',
                        'payment_url',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function can_filter_invoices_by_status(): void
    {
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 299000,
        ]);

        SubscriptionInvoice::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'invoice_number' => 'INV-2026-0001',
            'amount' => 299000,
            'tax_amount' => 32890,
            'total_amount' => 331890,
            'status' => 'paid',
            'billing_cycle' => 'monthly',
            'period_start' => now()->subMonth(),
            'period_end' => now(),
            'paid_at' => now(),
        ]);

        SubscriptionInvoice::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'invoice_number' => 'INV-2026-0002',
            'amount' => 299000,
            'tax_amount' => 32890,
            'total_amount' => 331890,
            'status' => 'pending',
            'billing_cycle' => 'monthly',
            'period_start' => now(),
            'period_end' => now()->addMonth(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/subscription/invoices?status=paid');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('paid', $response->json('data.0.status'));
    }

    /** @test */
    public function can_get_single_invoice(): void
    {
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 299000,
        ]);

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'invoice_number' => 'INV-2026-0001',
            'amount' => 299000,
            'tax_amount' => 32890,
            'total_amount' => 331890,
            'status' => 'paid',
            'billing_cycle' => 'monthly',
            'period_start' => now()->subMonth(),
            'period_end' => now(),
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/subscription/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'invoice_number',
                    'amount',
                    'tax_amount',
                    'total_amount',
                    'status',
                    'plan' => [
                        'id',
                        'name',
                        'slug',
                    ],
                ],
            ])
            ->assertJsonPath('data.invoice_number', 'INV-2026-0001');
    }

    /** @test */
    public function cannot_access_other_tenant_invoice(): void
    {
        $otherTenant = Tenant::factory()->create();

        $subscription = Subscription::create([
            'tenant_id' => $otherTenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 299000,
        ]);

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $otherTenant->id,
            'subscription_id' => $subscription->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'invoice_number' => 'INV-2026-0001',
            'amount' => 299000,
            'tax_amount' => 32890,
            'total_amount' => 331890,
            'status' => 'paid',
            'billing_cycle' => 'monthly',
            'period_start' => now()->subMonth(),
            'period_end' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/subscription/invoices/{$invoice->id}");

        $response->assertNotFound();
    }

    // ==========================================
    // CALCULATE UPGRADE PRORATION
    // ==========================================

    /** @test */
    public function can_calculate_upgrade_proration(): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->starterPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subDays(15),
            'current_period_end' => now()->addDays(15),
            'price' => 99000,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/subscription/upgrade/calculate?plan_id={$this->growthPlan->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'current_plan' => [
                        'name',
                        'price',
                    ],
                    'new_plan' => [
                        'name',
                        'price',
                    ],
                    'proration' => [
                        'remaining_days',
                        'total_days',
                        'credit_amount',
                        'amount_due',
                    ],
                ],
            ]);
    }

    // ==========================================
    // FEATURE ACCESS CHECK
    // ==========================================

    /** @test */
    public function can_check_feature_access(): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 299000,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/subscription/features');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'plan_name',
                    'features' => [
                        'pos_core',
                        'basic_reports',
                        'inventory_basic',
                        'inventory_advanced',
                    ],
                    'limits' => [
                        'max_outlets',
                        'max_users',
                        'max_products',
                        'current_outlets',
                        'current_users',
                        'current_products',
                    ],
                ],
            ])
            ->assertJsonPath('data.features.inventory_basic', true)
            ->assertJsonPath('data.features.inventory_advanced', false);
    }

    /** @test */
    public function can_check_specific_feature(): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $this->growthPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'price' => 299000,
        ]);

        Sanctum::actingAs($this->user);

        // Check a feature that is available
        $response = $this->getJson('/api/v1/subscription/features/inventory_basic');
        $response->assertOk()
            ->assertJsonPath('data.has_access', true);

        // Check a feature that is not available
        $response = $this->getJson('/api/v1/subscription/features/inventory_advanced');
        $response->assertOk()
            ->assertJsonPath('data.has_access', false);
    }
}
