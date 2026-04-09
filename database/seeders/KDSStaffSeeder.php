<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KDSStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates KDS staff users with PINs for testing KDS functionality.
     *
     * PIN Reference for Testing:
     * - Head Chef (kitchen-staff): PIN 1234
     * - Grill Chef (kitchen-staff): PIN 2345
     * - Cold Station Chef (kitchen-staff): PIN 3456
     * - Beverage Staff (kitchen-staff): PIN 4567
     * - Kitchen Manager (outlet-manager): PIN 9999
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

        $kitchenRole = Role::where('slug', 'kitchen-staff')->first();
        $managerRole = Role::where('slug', 'outlet-manager')->first();

        if (! $kitchenRole) {
            $this->command->error('Kitchen staff role not found. Please run RoleSeeder first.');

            return;
        }

        // KDS Staff members with their PINs
        $kdsStaff = [
            [
                'name' => 'Chef Ahmad',
                'email' => 'chef.ahmad@demo.com',
                'role' => $kitchenRole,
                'pin' => '1234',
                'description' => 'Head Chef - Main Kitchen',
            ],
            [
                'name' => 'Chef Budi',
                'email' => 'chef.budi@demo.com',
                'role' => $kitchenRole,
                'pin' => '2345',
                'description' => 'Grill Station Chef',
            ],
            [
                'name' => 'Chef Citra',
                'email' => 'chef.citra@demo.com',
                'role' => $kitchenRole,
                'pin' => '3456',
                'description' => 'Cold Station / Dessert Chef',
            ],
            [
                'name' => 'Dani Barista',
                'email' => 'dani.barista@demo.com',
                'role' => $kitchenRole,
                'pin' => '4567',
                'description' => 'Beverage Station Staff',
            ],
            [
                'name' => 'Eko Supervisor',
                'email' => 'eko.supervisor@demo.com',
                'role' => $managerRole,
                'pin' => '9999',
                'description' => 'Kitchen Manager / Supervisor',
            ],
        ];

        $createdCount = 0;

        foreach ($kdsStaff as $staff) {
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

            // Create PIN for KDS login
            UserPin::create([
                'user_id' => $user->id,
                'pin_hash' => Hash::make($staff['pin']),
                'is_active' => true,
            ]);

            $createdCount++;
            $this->command->info("Created KDS staff: {$staff['name']} (PIN: {$staff['pin']})");
        }

        $this->command->newLine();
        $this->command->info("Created {$createdCount} KDS staff users.");
        $this->command->newLine();

        // Print PIN reference table
        $this->command->table(
            ['Name', 'Email', 'Role', 'PIN'],
            collect($kdsStaff)->map(fn ($s) => [
                $s['name'],
                $s['email'],
                $s['role']->name,
                $s['pin'],
            ])->toArray()
        );

        $this->command->newLine();
        $this->command->info('Use these PINs to login to KDS at /api/v2/kds/auth/login');
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
