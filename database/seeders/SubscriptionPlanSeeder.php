<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Cocok untuk bisnis kecil yang baru memulai',
                'price_monthly' => 99000,
                'price_yearly' => 950400, // 99000 * 12 * 0.8 (20% discount)
                'max_outlets' => 1,
                'max_users' => 2,
                'features' => [
                    'POS dasar',
                    'Manajemen produk',
                    'Laporan penjualan',
                    'Manajemen pelanggan',
                ],
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Untuk bisnis yang berkembang dengan banyak cabang',
                'price_monthly' => 399000,
                'price_yearly' => 3830400, // 399000 * 12 * 0.8
                'max_outlets' => 2,
                'max_users' => 5,
                'features' => [
                    'Semua fitur Starter',
                    'Multi outlet',
                    'Manajemen inventori',
                    'Transfer stok antar outlet',
                    'Laporan lanjutan',
                    'Integrasi resep',
                ],
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Solusi lengkap untuk bisnis besar',
                'price_monthly' => 999000,
                'price_yearly' => 9590400, // 999000 * 12 * 0.8
                'max_outlets' => -1, // Unlimited
                'max_users' => -1, // Unlimited
                'features' => [
                    'Semua fitur Professional',
                    'Outlet unlimited',
                    'User unlimited',
                    'API access',
                    'Priority support',
                    'Custom branding',
                    'Data export',
                ],
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
