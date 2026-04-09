<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\DowngradeService;
use Illuminate\Console\Command;

class ProcessPlanDowngrades extends Command
{
    protected $signature = 'subscriptions:process-downgrades
                            {--tenant= : Process specific tenant ID}
                            {--dry-run : Show what would be archived without actually doing it}';

    protected $description = 'Process plan downgrades and archive excess resources';

    public function __construct(protected DowngradeService $downgradeService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Processing plan downgrades...');

        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');

        $query = Subscription::query()
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIAL])
            ->with(['tenant', 'plan']);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No active subscriptions to process.');

            return Command::SUCCESS;
        }

        foreach ($subscriptions as $subscription) {
            $tenant = $subscription->tenant;
            $plan = $subscription->plan;

            if (! $tenant || ! $plan) {
                continue;
            }

            $this->line("Processing: {$tenant->name} ({$plan->name})");

            $analysis = $this->downgradeService->canDowngrade($tenant, $plan);

            $hasExcess = $analysis['products']['to_archive'] > 0
                || $analysis['users']['to_deactivate'] > 0
                || $analysis['outlets']['to_archive'] > 0;

            if (! $hasExcess) {
                $this->line('  → No excess resources');

                continue;
            }

            $this->warn("  → Products to archive: {$analysis['products']['to_archive']}");
            $this->warn("  → Users to deactivate: {$analysis['users']['to_deactivate']}");
            $this->warn("  → Outlets to archive: {$analysis['outlets']['to_archive']}");

            if ($dryRun) {
                $this->line('  → [DRY RUN] Skipped');

                continue;
            }

            $results = $this->downgradeService->handleDowngrade($tenant, $plan);

            if (empty($results['errors'])) {
                $this->info("  → Completed: {$results['products_archived']} products, {$results['users_deactivated']} users, {$results['outlets_archived']} outlets");
            } else {
                $this->error('  → Errors: '.implode(', ', $results['errors']));
            }
        }

        $this->info('Done processing plan downgrades.');

        return Command::SUCCESS;
    }
}
