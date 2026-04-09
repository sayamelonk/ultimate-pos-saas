<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionStatuses extends Command
{
    protected $signature = 'subscriptions:process-statuses';

    protected $description = 'Process subscription status transitions (trial expiry, grace period, freeze)';

    public function handle(): int
    {
        $this->info('Processing subscription statuses...');

        $this->processExpiredTrials();
        $this->processExpiredSubscriptions();
        $this->processGracePeriodExpired();

        $this->info('Done processing subscription statuses.');

        return Command::SUCCESS;
    }

    /**
     * Process expired trials -> start grace period
     */
    protected function processExpiredTrials(): void
    {
        $expiredTrials = Subscription::query()
            ->where('status', Subscription::STATUS_TRIAL)
            ->where('trial_ends_at', '<=', now())
            ->whereNull('grace_period_ends_at')
            ->get();

        foreach ($expiredTrials as $subscription) {
            $subscription->startGracePeriod();

            Log::info('Trial expired, grace period started', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'trial_ends_at' => $subscription->trial_ends_at,
                'grace_period_ends_at' => $subscription->grace_period_ends_at,
            ]);

            $this->line("Trial expired for tenant {$subscription->tenant_id}, grace period started.");
        }

        $this->info("Processed {$expiredTrials->count()} expired trials.");
    }

    /**
     * Process expired active subscriptions -> start grace period
     */
    protected function processExpiredSubscriptions(): void
    {
        $expiredSubscriptions = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '<=', now())
            ->whereNull('grace_period_ends_at')
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->startGracePeriod();

            Log::info('Subscription expired, grace period started', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'ends_at' => $subscription->ends_at,
                'grace_period_ends_at' => $subscription->grace_period_ends_at,
            ]);

            $this->line("Subscription expired for tenant {$subscription->tenant_id}, grace period started.");
        }

        $this->info("Processed {$expiredSubscriptions->count()} expired subscriptions.");
    }

    /**
     * Process grace period expired -> freeze account
     */
    protected function processGracePeriodExpired(): void
    {
        $gracePeriodExpired = Subscription::query()
            ->whereIn('status', [Subscription::STATUS_TRIAL, Subscription::STATUS_ACTIVE])
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<=', now())
            ->get();

        foreach ($gracePeriodExpired as $subscription) {
            $subscription->freeze();

            Log::info('Grace period expired, account frozen', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'grace_period_ends_at' => $subscription->grace_period_ends_at,
                'frozen_at' => $subscription->frozen_at,
            ]);

            $this->line("Account frozen for tenant {$subscription->tenant_id}.");
        }

        $this->info("Processed {$gracePeriodExpired->count()} accounts, now frozen.");
    }
}
