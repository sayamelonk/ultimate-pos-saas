<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'trial_ends_at' => null,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'grace_period_ends_at' => null,
            'cancelled_at' => null,
            'frozen_at' => null,
            'cancellation_reason' => null,
        ];
    }

    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trial' => true,
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(14),
            'ends_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trial' => false,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function frozen(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_FROZEN,
            'frozen_at' => now(),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_EXPIRED,
            'ends_at' => now()->subWeek(),
        ]);
    }

    public function withPlan(SubscriptionPlan $plan): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan_id' => $plan->id,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function cancelled(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?? 'Requested by user',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_PENDING,
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_PAST_DUE,
            'ends_at' => now()->subDays(5),
        ]);
    }

    public function inGracePeriod(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->subDay(),
            'grace_period_ends_at' => now()->addDay(),
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'yearly',
            'ends_at' => now()->addYear(),
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'monthly',
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function trialExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trial' => true,
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => now()->subDay(),
            'ends_at' => null,
        ]);
    }

    public function trialEndingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trial' => true,
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
            'ends_at' => null,
        ]);
    }

    public function withPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }

    public function expiringInDays(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'ends_at' => now()->addDays($days),
        ]);
    }
}
