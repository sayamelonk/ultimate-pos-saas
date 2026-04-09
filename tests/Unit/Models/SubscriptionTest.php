<?php

namespace Tests\Unit\Models;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    // ==================== CONSTANTS TESTS ====================

    public function test_status_constants_are_defined(): void
    {
        $this->assertEquals('trial', Subscription::STATUS_TRIAL);
        $this->assertEquals('active', Subscription::STATUS_ACTIVE);
        $this->assertEquals('frozen', Subscription::STATUS_FROZEN);
        $this->assertEquals('expired', Subscription::STATUS_EXPIRED);
        $this->assertEquals('cancelled', Subscription::STATUS_CANCELLED);
        $this->assertEquals('pending', Subscription::STATUS_PENDING);
        $this->assertEquals('past_due', Subscription::STATUS_PAST_DUE);
    }

    public function test_trial_days_constant(): void
    {
        $this->assertEquals(14, Subscription::TRIAL_DAYS);
    }

    public function test_grace_period_days_constant(): void
    {
        $this->assertEquals(1, Subscription::GRACE_PERIOD_DAYS);
    }

    // ==================== CREATION TESTS ====================

    public function test_can_create_subscription(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
    }

    public function test_subscription_has_required_attributes(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertNotNull($subscription->id);
        $this->assertNotNull($subscription->tenant_id);
        $this->assertNotNull($subscription->subscription_plan_id);
        $this->assertNotNull($subscription->billing_cycle);
        $this->assertNotNull($subscription->status);
    }

    // ==================== RELATIONSHIP TESTS ====================

    public function test_subscription_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $subscription->tenant);
        $this->assertEquals($tenant->id, $subscription->tenant->id);
    }

    public function test_subscription_belongs_to_plan(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $subscription = Subscription::factory()->create([
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertInstanceOf(SubscriptionPlan::class, $subscription->plan);
        $this->assertEquals($plan->id, $subscription->plan->id);
    }

    public function test_subscription_has_many_invoices(): void
    {
        $subscription = Subscription::factory()->create();
        SubscriptionInvoice::factory()->count(3)->create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);

        $this->assertCount(3, $subscription->invoices);
    }

    // ==================== SCOPE TESTS ====================

    public function test_scope_active(): void
    {
        Subscription::factory()->active()->count(2)->create();
        Subscription::factory()->trial()->create();
        Subscription::factory()->frozen()->create();

        $activeSubscriptions = Subscription::active()->get();

        $this->assertCount(2, $activeSubscriptions);
    }

    public function test_scope_trial(): void
    {
        Subscription::factory()->trial()->count(2)->create();
        Subscription::factory()->active()->create();

        $trialSubscriptions = Subscription::trial()->get();

        $this->assertCount(2, $trialSubscriptions);
    }

    public function test_scope_frozen(): void
    {
        Subscription::factory()->frozen()->count(2)->create();
        Subscription::factory()->active()->create();

        $frozenSubscriptions = Subscription::frozen()->get();

        $this->assertCount(2, $frozenSubscriptions);
    }

    public function test_scope_pending(): void
    {
        Subscription::factory()->pending()->count(2)->create();
        Subscription::factory()->active()->create();

        $pendingSubscriptions = Subscription::pending()->get();

        $this->assertCount(2, $pendingSubscriptions);
    }

    public function test_scope_active_or_trial(): void
    {
        Subscription::factory()->active()->count(2)->create();
        Subscription::factory()->trial()->count(3)->create();
        Subscription::factory()->frozen()->create();
        Subscription::factory()->expired()->create();

        $activeOrTrialSubscriptions = Subscription::activeOrTrial()->get();

        $this->assertCount(5, $activeOrTrialSubscriptions);
    }

    // ==================== STATUS CHECK TESTS ====================

    public function test_is_active_returns_true_when_active_with_future_end_date(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addMonth(),
        ]);

        $this->assertTrue($subscription->isActive());
    }

    public function test_is_active_returns_false_when_active_but_past_end_date(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->subDay(),
        ]);

        $this->assertFalse($subscription->isActive());
    }

    public function test_is_active_returns_false_when_not_active_status(): void
    {
        $subscription = Subscription::factory()->trial()->create();

        $this->assertFalse($subscription->isActive());
    }

    public function test_is_trial_returns_true_when_trial_with_future_trial_end_date(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(7),
        ]);

        $this->assertTrue($subscription->isTrial());
    }

    public function test_is_trial_returns_false_when_trial_but_past_trial_end_date(): void
    {
        $subscription = Subscription::factory()->trialExpired()->create();

        $this->assertFalse($subscription->isTrial());
    }

    public function test_is_frozen_returns_true_when_frozen(): void
    {
        $subscription = Subscription::factory()->frozen()->create();

        $this->assertTrue($subscription->isFrozen());
    }

    public function test_is_frozen_returns_false_when_not_frozen(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $this->assertFalse($subscription->isFrozen());
    }

    public function test_is_pending_returns_true_when_pending(): void
    {
        $subscription = Subscription::factory()->pending()->create();

        $this->assertTrue($subscription->isPending());
    }

    public function test_is_pending_returns_false_when_not_pending(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $this->assertFalse($subscription->isPending());
    }

    public function test_is_expired_returns_true_when_expired_status(): void
    {
        $subscription = Subscription::factory()->expired()->create();

        $this->assertTrue($subscription->isExpired());
    }

    public function test_is_expired_returns_true_when_past_end_date_and_not_frozen(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->subWeek(),
        ]);

        $this->assertTrue($subscription->isExpired());
    }

    public function test_is_expired_returns_false_when_frozen_even_with_past_end_date(): void
    {
        $subscription = Subscription::factory()->frozen()->create();

        $this->assertFalse($subscription->isExpired());
    }

    public function test_is_cancelled_returns_true_when_cancelled(): void
    {
        $subscription = Subscription::factory()->cancelled()->create();

        $this->assertTrue($subscription->isCancelled());
    }

    public function test_is_cancelled_returns_false_when_not_cancelled(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $this->assertFalse($subscription->isCancelled());
    }

    public function test_is_in_grace_period_returns_true_when_grace_period_future(): void
    {
        $subscription = Subscription::factory()->inGracePeriod()->create();

        $this->assertTrue($subscription->isInGracePeriod());
    }

    public function test_is_in_grace_period_returns_false_when_no_grace_period(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $this->assertFalse($subscription->isInGracePeriod());
    }

    public function test_is_in_grace_period_returns_false_when_grace_period_past(): void
    {
        $subscription = Subscription::factory()->create([
            'grace_period_ends_at' => now()->subDay(),
        ]);

        $this->assertFalse($subscription->isInGracePeriod());
    }

    // ==================== CAN USE SYSTEM TESTS ====================

    public function test_can_use_system_returns_true_for_trial(): void
    {
        $subscription = Subscription::factory()->trial()->create();

        $this->assertTrue($subscription->canUseSystem());
    }

    public function test_can_use_system_returns_true_for_active(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $this->assertTrue($subscription->canUseSystem());
    }

    public function test_can_use_system_returns_true_for_frozen(): void
    {
        $subscription = Subscription::factory()->frozen()->create();

        $this->assertTrue($subscription->canUseSystem());
    }

    public function test_can_use_system_returns_true_for_grace_period(): void
    {
        $subscription = Subscription::factory()->inGracePeriod()->create();

        $this->assertTrue($subscription->canUseSystem());
    }

    public function test_can_use_system_returns_false_for_expired(): void
    {
        $subscription = Subscription::factory()->expired()->create();

        $this->assertFalse($subscription->canUseSystem());
    }

    public function test_can_use_system_returns_false_for_cancelled(): void
    {
        $subscription = Subscription::factory()->cancelled()->create();

        $this->assertFalse($subscription->canUseSystem());
    }

    // ==================== CAN CREATE TRANSACTIONS TESTS ====================

    public function test_can_create_transactions_returns_true_for_active(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $this->assertTrue($subscription->canCreateTransactions());
    }

    public function test_can_create_transactions_returns_true_for_trial(): void
    {
        $subscription = Subscription::factory()->trial()->create();

        $this->assertTrue($subscription->canCreateTransactions());
    }

    public function test_can_create_transactions_returns_false_for_frozen(): void
    {
        $subscription = Subscription::factory()->frozen()->create();

        $this->assertFalse($subscription->canCreateTransactions());
    }

    public function test_can_create_transactions_returns_true_for_grace_period(): void
    {
        $subscription = Subscription::factory()->inGracePeriod()->create();

        $this->assertTrue($subscription->canCreateTransactions());
    }

    public function test_can_create_transactions_returns_false_for_expired(): void
    {
        $subscription = Subscription::factory()->expired()->create();

        $this->assertFalse($subscription->canCreateTransactions());
    }

    // ==================== DAYS REMAINING TESTS ====================

    public function test_days_remaining_for_trial(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(7)->startOfDay(),
        ]);

        $daysRemaining = $subscription->daysRemaining();
        $this->assertGreaterThanOrEqual(6, $daysRemaining);
        $this->assertLessThanOrEqual(7, $daysRemaining);
    }

    public function test_days_remaining_for_active(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(30)->startOfDay(),
            'is_trial' => false,
        ]);

        $daysRemaining = $subscription->daysRemaining();
        $this->assertGreaterThanOrEqual(29, $daysRemaining);
        $this->assertLessThanOrEqual(30, $daysRemaining);
    }

    public function test_days_remaining_returns_zero_when_no_end_date(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'is_trial' => false,
            'ends_at' => null,
        ]);

        $this->assertEquals(0, $subscription->daysRemaining());
    }

    public function test_days_remaining_returns_zero_when_past_end_date(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'is_trial' => false,
            'ends_at' => now()->subDays(5),
        ]);

        $this->assertEquals(0, $subscription->daysRemaining());
    }

    // ==================== GET EFFECTIVE END DATE TESTS ====================

    public function test_get_effective_end_date_returns_trial_ends_at_for_trial(): void
    {
        $trialEndsAt = now()->addDays(14);
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_TRIAL,
            'is_trial' => true,
            'trial_ends_at' => $trialEndsAt,
            'ends_at' => now()->addMonth(),
        ]);

        $this->assertEquals($trialEndsAt->toDateTimeString(), $subscription->getEffectiveEndDate()->toDateTimeString());
    }

    public function test_get_effective_end_date_returns_ends_at_for_active(): void
    {
        $endsAt = now()->addMonth();
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'is_trial' => false,
            'trial_ends_at' => null,
            'ends_at' => $endsAt,
        ]);

        $this->assertEquals($endsAt->toDateTimeString(), $subscription->getEffectiveEndDate()->toDateTimeString());
    }

    public function test_get_effective_end_date_returns_null_when_no_end_date(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'is_trial' => false,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $this->assertNull($subscription->getEffectiveEndDate());
    }

    // ==================== STATE TRANSITION TESTS ====================

    public function test_start_trial(): void
    {
        $tenant = Tenant::factory()->create(['trial_used' => false]);
        $plan = SubscriptionPlan::factory()->professional()->create();
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => Subscription::STATUS_PENDING,
        ]);

        $subscription->startTrial();
        $subscription->refresh();
        $tenant->refresh();

        $this->assertEquals(Subscription::STATUS_TRIAL, $subscription->status);
        $this->assertTrue($subscription->is_trial);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertTrue($tenant->trial_used);
        $this->assertEquals('professional', $tenant->subscription_plan);
    }

    public function test_activate(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->growth()->create();
        $subscription = Subscription::factory()->trial()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $subscription->activate();
        $subscription->refresh();
        $tenant->refresh();

        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertFalse($subscription->is_trial);
        $this->assertEquals('growth', $tenant->subscription_plan);
    }

    public function test_freeze(): void
    {
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
        ]);

        $subscription->freeze();
        $subscription->refresh();
        $tenant->refresh();

        $this->assertEquals(Subscription::STATUS_FROZEN, $subscription->status);
        $this->assertNotNull($subscription->frozen_at);
        $this->assertEquals('frozen', $tenant->subscription_plan);
    }

    public function test_expire(): void
    {
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
        ]);

        $subscription->expire();
        $subscription->refresh();
        $tenant->refresh();

        $this->assertEquals(Subscription::STATUS_EXPIRED, $subscription->status);
        $this->assertEquals('expired', $tenant->subscription_plan);
    }

    public function test_cancel(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $subscription->cancel('User requested');
        $subscription->refresh();

        $this->assertEquals(Subscription::STATUS_CANCELLED, $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
        $this->assertEquals('User requested', $subscription->cancellation_reason);
    }

    public function test_cancel_without_reason(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $subscription->cancel();
        $subscription->refresh();

        $this->assertEquals(Subscription::STATUS_CANCELLED, $subscription->status);
        $this->assertNull($subscription->cancellation_reason);
    }

    public function test_start_grace_period(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $subscription->startGracePeriod();
        $subscription->refresh();

        $this->assertNotNull($subscription->grace_period_ends_at);
        $this->assertTrue($subscription->grace_period_ends_at->isFuture());
    }

    public function test_sync_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'slug' => 'enterprise',
            'max_outlets' => -1, // Unlimited
        ]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'ends_at' => now()->addYear(),
        ]);

        $subscription->syncToTenant();
        $tenant->refresh();

        $this->assertEquals('enterprise', $tenant->subscription_plan);
        $this->assertEquals(999, $tenant->max_outlets); // -1 becomes 999
    }

    public function test_sync_to_tenant_with_limited_outlets(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'slug' => 'starter',
            'max_outlets' => 1,
        ]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'ends_at' => now()->addMonth(),
        ]);

        $subscription->syncToTenant();
        $tenant->refresh();

        $this->assertEquals('starter', $tenant->subscription_plan);
        $this->assertEquals(1, $tenant->max_outlets);
    }

    // ==================== CREATE TRIAL STATIC METHOD TESTS ====================

    public function test_create_trial(): void
    {
        $tenant = Tenant::factory()->create(['trial_used' => false]);
        $professionalPlan = SubscriptionPlan::factory()->professional()->create();

        $subscription = Subscription::createTrial($tenant);

        $this->assertEquals($tenant->id, $subscription->tenant_id);
        $this->assertEquals($professionalPlan->id, $subscription->subscription_plan_id);
        $this->assertEquals(Subscription::STATUS_TRIAL, $subscription->status);
        $this->assertTrue($subscription->is_trial);
        $this->assertNotNull($subscription->trial_ends_at);
    }

    // ==================== CASTING TESTS ====================

    public function test_price_is_cast_to_decimal(): void
    {
        $subscription = Subscription::factory()->create([
            'price' => 299000.50,
        ]);

        $this->assertEquals(299000.50, $subscription->price);
    }

    public function test_is_trial_is_cast_to_boolean(): void
    {
        $subscription = Subscription::factory()->create([
            'is_trial' => 1,
        ]);

        $this->assertIsBool($subscription->is_trial);
        $this->assertTrue($subscription->is_trial);
    }

    public function test_trial_ends_at_is_cast_to_datetime(): void
    {
        $subscription = Subscription::factory()->trial()->create();

        $this->assertInstanceOf(Carbon::class, $subscription->trial_ends_at);
    }

    public function test_starts_at_is_cast_to_datetime(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $this->assertInstanceOf(Carbon::class, $subscription->starts_at);
    }

    public function test_ends_at_is_cast_to_datetime(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $this->assertInstanceOf(Carbon::class, $subscription->ends_at);
    }

    public function test_grace_period_ends_at_is_cast_to_datetime(): void
    {
        $subscription = Subscription::factory()->inGracePeriod()->create();

        $this->assertInstanceOf(Carbon::class, $subscription->grace_period_ends_at);
    }

    public function test_cancelled_at_is_cast_to_datetime(): void
    {
        $subscription = Subscription::factory()->cancelled()->create();

        $this->assertInstanceOf(Carbon::class, $subscription->cancelled_at);
    }

    public function test_frozen_at_is_cast_to_datetime(): void
    {
        $subscription = Subscription::factory()->frozen()->create();

        $this->assertInstanceOf(Carbon::class, $subscription->frozen_at);
    }

    // ==================== FACTORY STATE TESTS ====================

    public function test_factory_trial_state(): void
    {
        $subscription = Subscription::factory()->trial()->create();

        $this->assertEquals(Subscription::STATUS_TRIAL, $subscription->status);
        $this->assertTrue($subscription->is_trial);
        $this->assertNotNull($subscription->trial_ends_at);
    }

    public function test_factory_active_state(): void
    {
        $subscription = Subscription::factory()->active()->create();

        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertFalse($subscription->is_trial);
        $this->assertNotNull($subscription->ends_at);
    }

    public function test_factory_frozen_state(): void
    {
        $subscription = Subscription::factory()->frozen()->create();

        $this->assertEquals(Subscription::STATUS_FROZEN, $subscription->status);
        $this->assertNotNull($subscription->frozen_at);
    }

    public function test_factory_expired_state(): void
    {
        $subscription = Subscription::factory()->expired()->create();

        $this->assertEquals(Subscription::STATUS_EXPIRED, $subscription->status);
        $this->assertTrue($subscription->ends_at->isPast());
    }

    public function test_factory_cancelled_state(): void
    {
        $subscription = Subscription::factory()->cancelled()->create();

        $this->assertEquals(Subscription::STATUS_CANCELLED, $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
    }

    public function test_factory_pending_state(): void
    {
        $subscription = Subscription::factory()->pending()->create();

        $this->assertEquals(Subscription::STATUS_PENDING, $subscription->status);
    }

    public function test_factory_past_due_state(): void
    {
        $subscription = Subscription::factory()->pastDue()->create();

        $this->assertEquals(Subscription::STATUS_PAST_DUE, $subscription->status);
    }

    public function test_factory_in_grace_period_state(): void
    {
        $subscription = Subscription::factory()->inGracePeriod()->create();

        $this->assertTrue($subscription->grace_period_ends_at->isFuture());
    }

    public function test_factory_yearly_state(): void
    {
        $subscription = Subscription::factory()->yearly()->create();

        $this->assertEquals('yearly', $subscription->billing_cycle);
    }

    public function test_factory_monthly_state(): void
    {
        $subscription = Subscription::factory()->monthly()->create();

        $this->assertEquals('monthly', $subscription->billing_cycle);
    }

    public function test_factory_trial_expired_state(): void
    {
        $subscription = Subscription::factory()->trialExpired()->create();

        $this->assertTrue($subscription->is_trial);
        $this->assertTrue($subscription->trial_ends_at->isPast());
    }

    public function test_factory_trial_ending_soon_state(): void
    {
        $subscription = Subscription::factory()->trialEndingSoon()->create();

        $this->assertTrue($subscription->is_trial);
        $this->assertTrue($subscription->trial_ends_at->isFuture());
        $this->assertTrue($subscription->trial_ends_at->diffInDays(now()) <= 3);
    }

    public function test_factory_with_plan_state(): void
    {
        $plan = SubscriptionPlan::factory()->enterprise()->create();
        $subscription = Subscription::factory()->withPlan($plan)->create();

        $this->assertEquals($plan->id, $subscription->subscription_plan_id);
    }

    public function test_factory_for_tenant_state(): void
    {
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->forTenant($tenant)->create();

        $this->assertEquals($tenant->id, $subscription->tenant_id);
    }

    public function test_factory_expiring_in_days_state(): void
    {
        $subscription = Subscription::factory()->expiringInDays(5)->create();

        $daysUntilExpiry = (int) now()->diffInDays($subscription->ends_at, false);
        $this->assertGreaterThanOrEqual(4, $daysUntilExpiry);
        $this->assertLessThanOrEqual(5, $daysUntilExpiry);
    }

    // ==================== EDGE CASE TESTS ====================

    public function test_subscription_with_null_ends_at(): void
    {
        $subscription = Subscription::factory()->create([
            'ends_at' => null,
        ]);

        $this->assertNull($subscription->ends_at);
        $this->assertEquals(0, $subscription->daysRemaining());
    }

    public function test_subscription_with_null_trial_ends_at(): void
    {
        $subscription = Subscription::factory()->create([
            'is_trial' => false,
            'trial_ends_at' => null,
        ]);

        $this->assertNull($subscription->trial_ends_at);
    }

    public function test_is_active_with_null_ends_at(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => null,
        ]);

        $this->assertFalse($subscription->isActive());
    }

    public function test_is_trial_with_null_trial_ends_at(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => null,
        ]);

        $this->assertFalse($subscription->isTrial());
    }

    public function test_multiple_subscriptions_for_same_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $sub1 = Subscription::factory()->cancelled()->create(['tenant_id' => $tenant->id]);
        $sub2 = Subscription::factory()->active()->create(['tenant_id' => $tenant->id]);

        $this->assertCount(2, Subscription::where('tenant_id', $tenant->id)->get());
    }

    public function test_tenant_can_have_subscription_with_different_plans(): void
    {
        $tenant = Tenant::factory()->create();
        $starterPlan = SubscriptionPlan::factory()->starter()->create();
        $growthPlan = SubscriptionPlan::factory()->growth()->create();

        $sub1 = Subscription::factory()->cancelled()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $starterPlan->id,
        ]);
        $sub2 = Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $growthPlan->id,
        ]);

        $this->assertEquals($starterPlan->id, $sub1->subscription_plan_id);
        $this->assertEquals($growthPlan->id, $sub2->subscription_plan_id);
    }
}
