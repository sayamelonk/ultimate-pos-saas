<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\TrialExpiredNotification;
use App\Notifications\TrialExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTrialReminders extends Command
{
    protected $signature = 'subscriptions:send-trial-reminders';

    protected $description = 'Send trial reminder emails (H-7, H-3, H-1, H+1)';

    public function handle(): int
    {
        $this->info('Sending trial reminder emails...');

        $this->sendExpiringReminders(7);  // H-7
        $this->sendExpiringReminders(3);  // H-3
        $this->sendExpiringReminders(1);  // H-1
        $this->sendExpiredReminders();    // H+1

        $this->info('Done sending trial reminder emails.');

        return Command::SUCCESS;
    }

    /**
     * Send reminders for trials expiring in X days
     */
    protected function sendExpiringReminders(int $daysRemaining): void
    {
        $targetDate = now()->addDays($daysRemaining)->startOfDay();
        $targetDateEnd = now()->addDays($daysRemaining)->endOfDay();

        $subscriptions = Subscription::query()
            ->where('status', Subscription::STATUS_TRIAL)
            ->whereBetween('trial_ends_at', [$targetDate, $targetDateEnd])
            ->with(['tenant', 'tenant.users' => function ($q) {
                $q->whereHas('roles', function ($q) {
                    $q->where('slug', 'tenant-owner');
                });
            }])
            ->get();

        foreach ($subscriptions as $subscription) {
            $owner = $subscription->tenant->users->first();

            if (! $owner) {
                continue;
            }

            $owner->notify(new TrialExpiringNotification(
                daysRemaining: $daysRemaining,
                tenantName: $subscription->tenant->name,
                trialEndsAt: $subscription->trial_ends_at->format('d M Y H:i')
            ));

            Log::info('Trial expiring reminder sent', [
                'tenant_id' => $subscription->tenant_id,
                'user_id' => $owner->id,
                'days_remaining' => $daysRemaining,
                'trial_ends_at' => $subscription->trial_ends_at,
            ]);

            $this->line("Sent H-{$daysRemaining} reminder to {$owner->email} ({$subscription->tenant->name})");
        }

        $this->info("Processed {$subscriptions->count()} H-{$daysRemaining} reminders.");
    }

    /**
     * Send reminders for expired trials (H+1) - just frozen
     */
    protected function sendExpiredReminders(): void
    {
        $yesterday = now()->subDay()->startOfDay();
        $yesterdayEnd = now()->subDay()->endOfDay();

        // Get subscriptions that were frozen yesterday (trial ended + grace period ended)
        $subscriptions = Subscription::query()
            ->where('status', Subscription::STATUS_FROZEN)
            ->whereBetween('frozen_at', [$yesterday, $yesterdayEnd])
            ->whereNotNull('trial_ends_at') // Was a trial
            ->with(['tenant', 'tenant.users' => function ($q) {
                $q->whereHas('roles', function ($q) {
                    $q->where('slug', 'tenant-owner');
                });
            }])
            ->get();

        foreach ($subscriptions as $subscription) {
            $owner = $subscription->tenant->users->first();

            if (! $owner) {
                continue;
            }

            $owner->notify(new TrialExpiredNotification(
                tenantName: $subscription->tenant->name
            ));

            Log::info('Trial expired notification sent', [
                'tenant_id' => $subscription->tenant_id,
                'user_id' => $owner->id,
                'frozen_at' => $subscription->frozen_at,
            ]);

            $this->line("Sent expired notification to {$owner->email} ({$subscription->tenant->name})");
        }

        $this->info("Processed {$subscriptions->count()} expired trial notifications.");
    }
}
