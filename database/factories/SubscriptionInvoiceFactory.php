<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionInvoice>
 */
class SubscriptionInvoiceFactory extends Factory
{
    protected $model = SubscriptionInvoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomElement([99000, 299000, 599000, 1499000]);
        $taxAmount = $amount * 0.11; // 11% PPN

        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => Subscription::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'invoice_number' => SubscriptionInvoice::generateInvoiceNumber(),
            'xendit_invoice_id' => null,
            'xendit_invoice_url' => null,
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $amount + $taxAmount,
            'currency' => 'IDR',
            'billing_cycle' => 'monthly',
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'status' => 'pending',
            'payment_method' => null,
            'payment_channel' => null,
            'paid_at' => null,
            'expired_at' => now()->addDays(3),
            'xendit_response' => null,
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => 'BANK_TRANSFER',
            'payment_channel' => 'BCA',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expired_at' => now()->subDay(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'yearly',
            'period_end' => now()->addYear(),
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'monthly',
            'period_end' => now()->addMonth(),
        ]);
    }

    public function withAmount(float $amount): static
    {
        $taxAmount = $amount * 0.11;

        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $amount + $taxAmount,
        ]);
    }

    public function withXendit(string $invoiceId, string $invoiceUrl): static
    {
        return $this->state(fn (array $attributes) => [
            'xendit_invoice_id' => $invoiceId,
            'xendit_invoice_url' => $invoiceUrl,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);
    }

    public function forPlan(SubscriptionPlan $plan): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan_id' => $plan->id,
            'amount' => $plan->price_monthly,
            'tax_amount' => $plan->price_monthly * 0.11,
            'total_amount' => $plan->price_monthly * 1.11,
        ]);
    }

    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }
}
