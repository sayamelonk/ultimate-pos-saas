<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyDemoUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:verify-demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark all demo users as email verified';

    /**
     * Demo user emails that should be auto-verified.
     */
    private array $demoEmails = [
        'superadmin@ultimatepos.com',
        'owner@demo.com',
        'manager@demo.com',
        'cashier@demo.com',
        'waiter@demo.com',
        'kitchen@demo.com',
        'starter@demo.com',
        'growth@demo.com',
        'professional@demo.com',
        'enterprise@demo.com',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updated = User::whereIn('email', $this->demoEmails)
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        $this->info("Verified {$updated} demo users.");

        // Show current status
        $users = User::whereIn('email', $this->demoEmails)->get(['email', 'email_verified_at']);

        $this->table(
            ['Email', 'Verified At'],
            $users->map(fn ($u) => [
                $u->email,
                $u->email_verified_at?->format('Y-m-d H:i:s') ?? 'NOT VERIFIED',
            ])->toArray()
        );

        return Command::SUCCESS;
    }
}
