<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

class ProrationService
{
    /**
     * Calculate proration amount for upgrade mid-cycle.
     *
     * Formula:
     * 1. Calculate remaining value of current plan (unused days)
     * 2. Calculate cost of new plan for remaining days
     * 3. Proration = New plan cost - Credit from old plan
     */
    public function calculateUpgradeProration(
        Subscription $currentSubscription,
        SubscriptionPlan $newPlan,
        string $billingCycle = 'monthly'
    ): array {
        $currentPlan = $currentSubscription->plan;

        // If trial, no proration needed
        if ($currentSubscription->isTrial()) {
            return [
                'is_trial' => true,
                'proration_amount' => 0,
                'new_plan_amount' => $newPlan->getPrice($billingCycle),
                'credit_amount' => 0,
                'days_remaining' => $currentSubscription->daysRemaining(),
                'total_days' => $this->getTotalDays($billingCycle),
                'total_to_pay' => $newPlan->getPrice($billingCycle),
                'message' => 'Trial upgrade - bayar harga penuh paket baru',
            ];
        }

        // Get period info
        $endsAt = $currentSubscription->ends_at;
        $startsAt = $currentSubscription->starts_at;

        if (! $endsAt || ! $startsAt) {
            // No active period, charge full price
            return [
                'is_trial' => false,
                'proration_amount' => 0,
                'new_plan_amount' => $newPlan->getPrice($billingCycle),
                'credit_amount' => 0,
                'days_remaining' => 0,
                'total_days' => $this->getTotalDays($billingCycle),
                'total_to_pay' => $newPlan->getPrice($billingCycle),
                'message' => 'Tidak ada periode aktif - bayar harga penuh',
            ];
        }

        $now = Carbon::now();
        $totalDays = $startsAt->diffInDays($endsAt);
        $daysUsed = $startsAt->diffInDays($now);
        $daysRemaining = max(0, $now->diffInDays($endsAt));

        if ($daysRemaining <= 0) {
            // Period already ended, no credit
            return [
                'is_trial' => false,
                'proration_amount' => 0,
                'new_plan_amount' => $newPlan->getPrice($billingCycle),
                'credit_amount' => 0,
                'days_remaining' => 0,
                'total_days' => $this->getTotalDays($billingCycle),
                'total_to_pay' => $newPlan->getPrice($billingCycle),
                'message' => 'Periode habis - bayar harga penuh paket baru',
            ];
        }

        // Calculate daily rates
        $currentBillingCycle = $currentSubscription->billing_cycle ?? 'monthly';
        $currentPlanPrice = $currentPlan->getPrice($currentBillingCycle);
        $currentDailyRate = $currentPlanPrice / $totalDays;

        // Credit from unused days on current plan
        $creditAmount = $daysRemaining * $currentDailyRate;

        // New plan cost for remaining days
        $newPlanTotalPrice = $newPlan->getPrice($billingCycle);
        $newPlanTotalDays = $this->getTotalDays($billingCycle);
        $newPlanDailyRate = $newPlanTotalPrice / $newPlanTotalDays;
        $newPlanCostForRemaining = $daysRemaining * $newPlanDailyRate;

        // Proration = cost of new plan for remaining - credit
        $prorationAmount = max(0, $newPlanCostForRemaining - $creditAmount);

        // Round to nearest 100
        $prorationAmount = ceil($prorationAmount / 100) * 100;
        $creditAmount = floor($creditAmount / 100) * 100;

        // If upgrading, pay proration now, then full price on renewal
        // If somehow downgrading (shouldn't happen via this flow), proration might be 0 or negative

        return [
            'is_trial' => false,
            'current_plan' => [
                'id' => $currentPlan->id,
                'name' => $currentPlan->name,
                'price' => $currentPlanPrice,
                'billing_cycle' => $currentBillingCycle,
            ],
            'new_plan' => [
                'id' => $newPlan->id,
                'name' => $newPlan->name,
                'price' => $newPlanTotalPrice,
                'billing_cycle' => $billingCycle,
            ],
            'days_used' => $daysUsed,
            'days_remaining' => $daysRemaining,
            'total_days' => $totalDays,
            'new_plan_total_days' => $newPlanTotalDays,
            'current_daily_rate' => round($currentDailyRate, 2),
            'new_daily_rate' => round($newPlanDailyRate, 2),
            'credit_amount' => $creditAmount,
            'new_plan_cost_for_remaining' => round($newPlanCostForRemaining, 2),
            'proration_amount' => $prorationAmount,
            'new_plan_amount' => $newPlanTotalPrice,
            'total_to_pay' => $prorationAmount,
            'next_billing_date' => $endsAt->toDateString(),
            'next_billing_amount' => $newPlanTotalPrice,
            'message' => $this->getProrationMessage($prorationAmount, $creditAmount, $currentPlan, $newPlan),
        ];
    }

    /**
     * Check if this is an upgrade (new plan is higher tier).
     */
    public function isUpgrade(SubscriptionPlan $currentPlan, SubscriptionPlan $newPlan): bool
    {
        return $newPlan->sort_order > $currentPlan->sort_order;
    }

    /**
     * Check if this is a downgrade (new plan is lower tier).
     */
    public function isDowngrade(SubscriptionPlan $currentPlan, SubscriptionPlan $newPlan): bool
    {
        return $newPlan->sort_order < $currentPlan->sort_order;
    }

    /**
     * Get total days in billing cycle.
     */
    protected function getTotalDays(string $billingCycle): int
    {
        return $billingCycle === 'yearly' ? 365 : 30;
    }

    /**
     * Generate human-readable proration message.
     */
    protected function getProrationMessage(
        float $prorationAmount,
        float $creditAmount,
        SubscriptionPlan $currentPlan,
        SubscriptionPlan $newPlan
    ): string {
        $formattedProration = 'Rp '.number_format($prorationAmount, 0, ',', '.');
        $formattedCredit = 'Rp '.number_format($creditAmount, 0, ',', '.');

        if ($prorationAmount === 0.0) {
            return "Upgrade gratis dari {$currentPlan->name} ke {$newPlan->name} dengan kredit {$formattedCredit}";
        }

        return "Upgrade ke {$newPlan->name}: Bayar {$formattedProration} (kredit sisa paket: {$formattedCredit})";
    }

    /**
     * Format proration details for display.
     */
    public function formatForDisplay(array $proration): array
    {
        return [
            'credit' => 'Rp '.number_format($proration['credit_amount'] ?? 0, 0, ',', '.'),
            'proration' => 'Rp '.number_format($proration['proration_amount'] ?? 0, 0, ',', '.'),
            'total' => 'Rp '.number_format($proration['total_to_pay'] ?? 0, 0, ',', '.'),
            'next_billing' => 'Rp '.number_format($proration['next_billing_amount'] ?? 0, 0, ',', '.'),
            'days_remaining' => $proration['days_remaining'] ?? 0,
            'message' => $proration['message'] ?? '',
        ];
    }
}
