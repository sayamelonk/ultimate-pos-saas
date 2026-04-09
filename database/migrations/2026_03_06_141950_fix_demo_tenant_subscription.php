<?php

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Fix: Demo tenant (DEMO001) missing subscription record.
     * Without a subscription, Tenant::hasFeature() returns false for all features.
     */
    public function up(): void
    {
        $tenant = Tenant::where('code', 'DEMO001')->first();
        $plan = SubscriptionPlan::where('slug', 'professional')->first();

        if ($tenant && $plan && ! $tenant->activeSubscription) {
            Subscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'is_trial' => false,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tenant = Tenant::where('code', 'DEMO001')->first();

        if ($tenant) {
            Subscription::where('tenant_id', $tenant->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->latest()
                ->first()
                ?->delete();
        }
    }
};
