<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        $sampleCustomers = [
            [
                'code' => 'CUST001',
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'phone' => '081234567890',
                'address' => 'Jl. Sudirman No. 1, Jakarta',
                'birth_date' => '1990-01-15',
                'gender' => 'male',
                'membership_level' => Customer::LEVEL_GOLD,
                'total_points' => 1500,
                'total_spent' => 15000000,
                'total_visits' => 25,
            ],
            [
                'code' => 'CUST002',
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'phone' => '081234567891',
                'address' => 'Jl. Thamrin No. 10, Jakarta',
                'birth_date' => '1988-05-20',
                'gender' => 'female',
                'membership_level' => Customer::LEVEL_SILVER,
                'total_points' => 800,
                'total_spent' => 8000000,
                'total_visits' => 15,
            ],
            [
                'code' => 'CUST003',
                'name' => 'Ahmad Yusuf',
                'email' => 'ahmad.yusuf@example.com',
                'phone' => '081234567892',
                'address' => 'Jl. Gatot Subroto No. 5, Jakarta',
                'birth_date' => '1995-08-10',
                'gender' => 'male',
                'membership_level' => Customer::LEVEL_REGULAR,
                'total_points' => 200,
                'total_spent' => 2000000,
                'total_visits' => 5,
            ],
            [
                'code' => 'CUST004',
                'name' => 'Siti Rahayu',
                'email' => 'siti.rahayu@example.com',
                'phone' => '081234567893',
                'address' => 'Jl. HR Rasuna Said No. 15, Jakarta',
                'birth_date' => '1992-12-25',
                'gender' => 'female',
                'membership_level' => Customer::LEVEL_PLATINUM,
                'total_points' => 5000,
                'total_spent' => 50000000,
                'total_visits' => 100,
            ],
            [
                'code' => 'CUST005',
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@example.com',
                'phone' => '081234567894',
                'address' => 'Jl. Kuningan No. 20, Jakarta',
                'birth_date' => '1985-03-08',
                'gender' => 'male',
                'membership_level' => Customer::LEVEL_REGULAR,
                'total_points' => 50,
                'total_spent' => 500000,
                'total_visits' => 2,
            ],
        ];

        foreach ($tenants as $tenant) {
            foreach ($sampleCustomers as $customerData) {
                Customer::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'code' => $customerData['code'],
                    ],
                    array_merge($customerData, [
                        'tenant_id' => $tenant->id,
                        'joined_at' => now()->subMonths(rand(1, 24)),
                        'is_active' => true,
                    ])
                );
            }
        }
    }
}
