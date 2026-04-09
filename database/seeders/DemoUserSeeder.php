<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Demo users configuration for testing feature gating.
     */
    private array $demoUsers = [
        [
            'plan_slug' => 'starter',
            'tenant_name' => 'Warung Kopi Starter',
            'user' => [
                'name' => 'Demo Starter',
                'email' => 'starter@demo.com',
            ],
            'outlet_name' => 'Warung Kopi Starter - Pusat',
        ],
        [
            'plan_slug' => 'growth',
            'tenant_name' => 'Cafe Growth',
            'user' => [
                'name' => 'Demo Growth',
                'email' => 'growth@demo.com',
            ],
            'outlet_name' => 'Cafe Growth - Cabang 1',
        ],
        [
            'plan_slug' => 'professional',
            'tenant_name' => 'Restaurant Professional',
            'user' => [
                'name' => 'Demo Professional',
                'email' => 'professional@demo.com',
            ],
            'outlet_name' => 'Restaurant Professional - Main',
        ],
        [
            'plan_slug' => 'enterprise',
            'tenant_name' => 'Enterprise Food Chain',
            'user' => [
                'name' => 'Demo Enterprise',
                'email' => 'enterprise@demo.com',
            ],
            'outlet_name' => 'Enterprise Food Chain - HQ',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->demoUsers as $demoData) {
            $this->createDemoUser($demoData);
        }

        $this->command->info('Demo users created successfully!');
        $this->command->table(
            ['Email', 'Password', 'Plan'],
            collect($this->demoUsers)->map(fn ($data) => [
                $data['user']['email'],
                'password',
                ucfirst($data['plan_slug']),
            ])->toArray()
        );
    }

    private function createDemoUser(array $data): void
    {
        // Find the subscription plan
        $plan = SubscriptionPlan::where('slug', $data['plan_slug'])->first();

        if (! $plan) {
            $this->command->warn("Plan {$data['plan_slug']} not found. Skipping...");

            return;
        }

        // Check if user already exists
        $existingUser = User::where('email', $data['user']['email'])->first();
        if ($existingUser) {
            $this->command->info("User {$data['user']['email']} already exists. Skipping...");

            return;
        }

        // Create tenant
        $tenantCode = strtoupper(substr($data['plan_slug'], 0, 3)).rand(100, 999);
        $tenant = Tenant::create([
            'code' => $tenantCode,
            'name' => $data['tenant_name'],
            'email' => $data['user']['email'],
            'phone' => fake()->phoneNumber(),
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'tax_percentage' => 11.00,
            'service_charge_percentage' => 5.00,
            'max_outlets' => $plan->max_outlets,
            'is_active' => true,
        ]);

        // Create subscription
        Subscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addYear(), // 1 year subscription for demo
        ]);

        // Create user
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['user']['name'],
            'email' => $data['user']['email'],
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create tenant-owner role and assign to user
        $ownerRole = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'tenant-owner'],
            ['name' => 'Tenant Owner', 'description' => 'Full access to tenant']
        );
        $user->roles()->attach($ownerRole->id);

        // Create outlet
        $outlet = Outlet::create([
            'tenant_id' => $tenant->id,
            'name' => $data['outlet_name'],
            'code' => strtoupper(substr($data['plan_slug'], 0, 3)),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ]);

        // Assign user to outlet
        $user->outlets()->attach($outlet->id, ['is_default' => true]);

        $this->command->info("Created demo user: {$data['user']['email']} with {$data['plan_slug']} plan");
    }
}
