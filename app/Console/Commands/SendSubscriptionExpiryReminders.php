<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SubscriptionExpiryReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionExpiryReminders extends Command
{
    /**
     * Reminder intervals in days before expiry.
     */
    protected const REMINDER_DAYS = [7, 3, 1];

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscription:send-expiry-reminders
                            {--dry-run : Run without sending emails}';

    /**
     * The console command description.
     */
    protected $description = 'Send subscription expiry reminder emails (H-7, H-3, H-1)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Checking for subscriptions expiring soon...');

        $stats = [
            'h7' => 0,
            'h3' => 0,
            'h1' => 0,
            'skipped' => 0,
        ];

        foreach (self::REMINDER_DAYS as $days) {
            $this->processRemindersForDay($days, $dryRun, $stats);
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Reminder', 'Sent'],
            [
                ['H-7 (7 days before)', $stats['h7']],
                ['H-3 (3 days before)', $stats['h3']],
                ['H-1 (1 day before)', $stats['h1']],
                ['Skipped (already sent)', $stats['skipped']],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Process reminders for a specific day interval.
     */
    protected function processRemindersForDay(int $days, bool $dryRun, array &$stats): void
    {
        $targetDate = now()->addDays($days)->startOfDay();
        $nextDay = $targetDate->copy()->addDay();

        // Find active subscriptions expiring on the target date
        $subscriptions = Subscription::active()
            ->with('tenant')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [$targetDate, $nextDay])
            ->get();

        $this->line("Found {$subscriptions->count()} subscriptions expiring in {$days} days");

        foreach ($subscriptions as $subscription) {
            $tenant = $subscription->tenant;

            if (! $tenant) {
                $stats['skipped']++;

                continue;
            }

            // Check if reminder was already sent
            if ($this->hasReminderBeenSent($tenant, $days)) {
                $this->line("  - {$tenant->name}: already notified for H-{$days}");
                $stats['skipped']++;

                continue;
            }

            $this->sendReminder($tenant, $subscription, $days, $dryRun, $stats);
        }
    }

    /**
     * Check if a reminder has already been sent for this interval.
     */
    protected function hasReminderBeenSent($tenant, int $days): bool
    {
        $column = match ($days) {
            7 => 'subscription_reminder_h7_at',
            3 => 'subscription_reminder_h3_at',
            1 => 'subscription_reminder_h1_at',
            default => null,
        };

        if (! $column) {
            return false;
        }

        return $tenant->$column !== null;
    }

    /**
     * Send reminder notification.
     */
    protected function sendReminder($tenant, Subscription $subscription, int $days, bool $dryRun, array &$stats): void
    {
        $statKey = "h{$days}";

        $this->info("  Sending H-{$days} reminder to {$tenant->email}");

        if ($dryRun) {
            $this->warn("  [DRY RUN] Would send H-{$days} reminder");
            $stats[$statKey]++;

            return;
        }

        try {
            $tenant->notify(new SubscriptionExpiryReminderNotification($subscription, $days));

            // Mark reminder as sent
            $column = match ($days) {
                7 => 'subscription_reminder_h7_at',
                3 => 'subscription_reminder_h3_at',
                1 => 'subscription_reminder_h1_at',
                default => null,
            };

            if ($column) {
                $tenant->update([$column => now()]);
            }

            $stats[$statKey]++;

            Log::info('Subscription expiry reminder sent', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'days_until_expiry' => $days,
            ]);
        } catch (\Exception $e) {
            $this->error("  Failed to send reminder: {$e->getMessage()}");
            Log::error('Failed to send subscription expiry reminder', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
