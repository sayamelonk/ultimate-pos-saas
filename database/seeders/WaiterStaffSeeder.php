<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WaiterStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates Waiter staff users with PINs for testing Waiter App functionality.
     *
     * PIN Reference for Testing:
     * - Waiter Andi (waiter): PIN 1111
     * - Waiter Bella (waiter): PIN 2222
     * - Waiter Chandra (waiter): PIN 3333
     * - Waiter Dewi (waiter): PIN 4444
     * - Senior Waiter Eka (waiter): PIN 5555
     * - Waiter Supervisor (outlet-manager): PIN 8888
     */
    public function run(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            $this->command->error('No tenant found. Please run TenantSeeder first.');

            return;
        }

        $outlet = Outlet::where('tenant_id', $tenant->id)->first();
        if (! $outlet) {
            $this->command->error('No outlet found. Please run TenantSeeder first.');

            return;
        }

        $waiterRole = Role::where('slug', 'waiter')->first();
        $managerRole = Role::where('slug', 'outlet-manager')->first();

        if (! $waiterRole) {
            $this->command->error('Waiter role not found. Please run RoleSeeder first.');

            return;
        }

        // Waiter Staff members with their PINs
        $waiterStaff = [
            [
                'name' => 'Waiter Andi',
                'email' => 'waiter.andi@demo.com',
                'role' => $waiterRole,
                'pin' => '1111',
                'description' => 'Waiter - Lantai 1',
            ],
            [
                'name' => 'Waiter Bella',
                'email' => 'waiter.bella@demo.com',
                'role' => $waiterRole,
                'pin' => '2222',
                'description' => 'Waiter - Lantai 1',
            ],
            [
                'name' => 'Waiter Chandra',
                'email' => 'waiter.chandra@demo.com',
                'role' => $waiterRole,
                'pin' => '3333',
                'description' => 'Waiter - Lantai 2',
            ],
            [
                'name' => 'Waiter Dewi',
                'email' => 'waiter.dewi@demo.com',
                'role' => $waiterRole,
                'pin' => '4444',
                'description' => 'Waiter - Lantai 2 / VIP',
            ],
            [
                'name' => 'Senior Waiter Eka',
                'email' => 'waiter.eka@demo.com',
                'role' => $waiterRole,
                'pin' => '5555',
                'description' => 'Senior Waiter - All Floors',
            ],
            [
                'name' => 'Waiter Supervisor',
                'email' => 'waiter.supervisor@demo.com',
                'role' => $managerRole,
                'pin' => '8888',
                'description' => 'Waiter Supervisor / Manager',
            ],
        ];

        $createdCount = 0;

        foreach ($waiterStaff as $staff) {
            // Check if user already exists
            $existingUser = User::where('email', $staff['email'])->first();

            if ($existingUser) {
                // Just ensure they have a PIN
                $this->ensureUserHasPin($existingUser, $staff['pin']);
                $this->command->info("Updated PIN for existing user: {$staff['name']}");

                continue;
            }

            // Create new user
            $user = User::create([
                'tenant_id' => $tenant->id,
                'email' => $staff['email'],
                'password' => Hash::make('password'),
                'name' => $staff['name'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Assign role
            $user->roles()->attach($staff['role']->id);

            // Attach to outlet
            $user->outlets()->attach($outlet->id, ['is_default' => true]);

            // Create PIN for Waiter App login
            UserPin::create([
                'user_id' => $user->id,
                'pin_hash' => Hash::make($staff['pin']),
                'is_active' => true,
            ]);

            $createdCount++;
            $this->command->info("Created Waiter staff: {$staff['name']} (PIN: {$staff['pin']})");
        }

        $this->command->newLine();
        $this->command->info("Created {$createdCount} Waiter staff users.");
        $this->command->newLine();

        // Print PIN reference table
        $this->command->table(
            ['Name', 'Email', 'Role', 'PIN'],
            collect($waiterStaff)->map(fn ($s) => [
                $s['name'],
                $s['email'],
                $s['role']->name,
                $s['pin'],
            ])->toArray()
        );

        $this->command->newLine();
        $this->command->info('Use these PINs to login to Waiter App at /api/v2/waiter/auth/login');
    }

    private function ensureUserHasPin(User $user, string $pin): void
    {
        $userPin = UserPin::where('user_id', $user->id)->first();

        if ($userPin) {
            $userPin->update([
                'pin_hash' => Hash::make($pin),
                'is_active' => true,
            ]);
        } else {
            UserPin::create([
                'user_id' => $user->id,
                'pin_hash' => Hash::make($pin),
                'is_active' => true,
            ]);
        }
    }
}
