<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Notifications\DataDeletedNotification;
use App\Notifications\DataDeletionWarningNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessFrozenAccounts extends Command
{
    /**
     * Days frozen before first warning (H-30).
     */
    protected const FIRST_WARNING_DAYS = 335;

    /**
     * Days frozen before second warning (H-7).
     */
    protected const SECOND_WARNING_DAYS = 358;

    /**
     * Days frozen before deletion.
     */
    protected const DELETION_DAYS = 365;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscription:process-frozen
                            {--dry-run : Run without making changes}
                            {--delete : Actually delete data (requires confirmation)}';

    /**
     * The console command description.
     */
    protected $description = 'Process frozen accounts: send warnings and delete after 1 year';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $shouldDelete = $this->option('delete');

        $this->info('Processing frozen accounts...');

        // Get all frozen subscriptions
        $frozenSubscriptions = Subscription::frozen()
            ->with('tenant')
            ->whereNotNull('frozen_at')
            ->get();

        $this->info("Found {$frozenSubscriptions->count()} frozen accounts");

        $stats = [
            'first_warning' => 0,
            'second_warning' => 0,
            'deleted' => 0,
            'skipped' => 0,
        ];

        foreach ($frozenSubscriptions as $subscription) {
            $tenant = $subscription->tenant;

            if (! $tenant) {
                $stats['skipped']++;

                continue;
            }

            $daysFrozen = $subscription->frozen_at->diffInDays(now());

            $this->line("  - {$tenant->name}: frozen {$daysFrozen} days");

            // Check for deletion (365+ days)
            if ($daysFrozen >= self::DELETION_DAYS) {
                if ($shouldDelete) {
                    $this->processDataDeletion($tenant, $subscription, $dryRun);
                    $stats['deleted']++;
                } else {
                    $this->warn('    Would delete (use --delete flag to confirm)');
                    $stats['skipped']++;
                }

                continue;
            }

            // Check for second warning (H-7)
            if ($daysFrozen >= self::SECOND_WARNING_DAYS) {
                $daysUntilDeletion = self::DELETION_DAYS - $daysFrozen;
                if (! $this->hasRecentWarning($tenant, 'second_warning')) {
                    $this->sendSecondWarning($tenant, $daysUntilDeletion, $dryRun);
                    $stats['second_warning']++;
                }

                continue;
            }

            // Check for first warning (H-30)
            if ($daysFrozen >= self::FIRST_WARNING_DAYS) {
                $daysUntilDeletion = self::DELETION_DAYS - $daysFrozen;
                if (! $this->hasRecentWarning($tenant, 'first_warning')) {
                    $this->sendFirstWarning($tenant, $daysUntilDeletion, $dryRun);
                    $stats['first_warning']++;
                }
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['First Warning (H-30)', $stats['first_warning']],
                ['Second Warning (H-7)', $stats['second_warning']],
                ['Data Deleted', $stats['deleted']],
                ['Skipped', $stats['skipped']],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Check if tenant has received a warning recently.
     */
    protected function hasRecentWarning(Tenant $tenant, string $type): bool
    {
        $column = $type === 'first_warning' ? 'data_deletion_warning_1_at' : 'data_deletion_warning_2_at';

        return $tenant->$column !== null;
    }

    /**
     * Send first warning email (H-30).
     */
    protected function sendFirstWarning(Tenant $tenant, int $daysUntilDeletion, bool $dryRun): void
    {
        $this->info("    Sending first warning to {$tenant->email} ({$daysUntilDeletion} days until deletion)");

        if ($dryRun) {
            $this->warn('    [DRY RUN] Would send first warning email');

            return;
        }

        try {
            $tenant->notify(new DataDeletionWarningNotification($daysUntilDeletion, 'first'));
            $tenant->update(['data_deletion_warning_1_at' => now()]);

            Log::info('Data deletion first warning sent', [
                'tenant_id' => $tenant->id,
                'days_until_deletion' => $daysUntilDeletion,
            ]);
        } catch (\Exception $e) {
            $this->error("    Failed to send warning: {$e->getMessage()}");
            Log::error('Failed to send data deletion warning', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send second warning email (H-7).
     */
    protected function sendSecondWarning(Tenant $tenant, int $daysUntilDeletion, bool $dryRun): void
    {
        $this->info("    Sending second warning to {$tenant->email} ({$daysUntilDeletion} days until deletion)");

        if ($dryRun) {
            $this->warn('    [DRY RUN] Would send second warning email');

            return;
        }

        try {
            $tenant->notify(new DataDeletionWarningNotification($daysUntilDeletion, 'second'));
            $tenant->update(['data_deletion_warning_2_at' => now()]);

            Log::info('Data deletion second warning sent', [
                'tenant_id' => $tenant->id,
                'days_until_deletion' => $daysUntilDeletion,
            ]);
        } catch (\Exception $e) {
            $this->error("    Failed to send warning: {$e->getMessage()}");
            Log::error('Failed to send data deletion warning', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process data deletion for a tenant.
     */
    protected function processDataDeletion(Tenant $tenant, Subscription $subscription, bool $dryRun): void
    {
        $this->warn("    DELETING data for {$tenant->name}");

        if ($dryRun) {
            $this->warn('    [DRY RUN] Would delete tenant data');

            return;
        }

        DB::beginTransaction();

        try {
            // Send notification before deletion
            $tenant->notify(new DataDeletedNotification);

            // Soft delete or anonymize tenant data
            // Note: This is a soft approach - we mark as deleted but keep structure
            $tenant->update([
                'is_deleted' => true,
                'deleted_at' => now(),
                'name' => 'Deleted Tenant',
                'email' => "deleted_{$tenant->id}@deleted.local",
                'phone' => null,
            ]);

            // Cancel subscription permanently
            $subscription->update([
                'status' => 'deleted',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Data deleted after 1 year frozen',
            ]);

            // Delete user accounts associated with this tenant
            $tenant->users()->update([
                'email' => DB::raw("CONCAT('deleted_', id, '@deleted.local')"),
                'password' => bcrypt(str()->random(32)), // Invalidate password
                'remember_token' => null,
            ]);

            DB::commit();

            Log::warning('Tenant data deleted after 1 year frozen', [
                'tenant_id' => $tenant->id,
                'frozen_at' => $subscription->frozen_at,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("    Failed to delete data: {$e->getMessage()}");
            Log::error('Failed to delete tenant data', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
