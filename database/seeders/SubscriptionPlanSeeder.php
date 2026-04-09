<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Pricing strategy based on docs/analisis/pricing.md (SSOT)
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Untuk warung, gerobak, dan usaha rumahan',
                'price_monthly' => 99000,
                'price_yearly' => 950400, // 99000 * 12 * 0.8 (20% discount)
                'max_outlets' => 1,
                'max_users' => 3,
                'max_products' => 100,
                'features' => [
                    'pos_core' => true,           // POS Core (order, payment, receipt)
                    'product_management' => true,  // Single product only
                    'basic_reports' => true,       // Daily sales report
                    'customer_management' => true, // Customer database
                    'multi_payment' => false,      // Only Cash + 1 method
                    'product_variant' => false,
                    'product_combo' => false,
                    'discount_promo' => false,
                    'inventory_basic' => false,
                    'inventory_advanced' => false,
                    'table_management' => false,
                    'recipe_bom' => false,
                    'stock_transfer' => false,
                    'waiter_app' => false,
                    'qr_order' => false,
                    'kds' => false,
                    'manager_authorization' => false,
                    'export_excel_pdf' => false,
                    'loyalty_points' => false,
                    'api_access' => false,
                    'custom_branding' => false,
                ],
                'sort_order' => 1,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'Untuk cafe, resto kecil, dan toko retail',
                'price_monthly' => 299000,
                'price_yearly' => 2870400, // 299000 * 12 * 0.8
                'max_outlets' => 2,
                'max_users' => 10,
                'max_products' => 500,
                'features' => [
                    'pos_core' => true,
                    'product_management' => true,
                    'basic_reports' => true,
                    'customer_management' => true,
                    'multi_payment' => true,
                    'product_variant' => true,
                    'product_combo' => true,
                    'discount_promo' => true,
                    'inventory_basic' => true,      // Stock tracking, min/max levels
                    'inventory_advanced' => false,
                    'table_management' => true,
                    'recipe_bom' => false,
                    'stock_transfer' => false,
                    'waiter_app' => false,
                    'qr_order' => false,
                    'kds' => false,
                    'manager_authorization' => false,
                    'export_excel_pdf' => true,
                    'loyalty_points' => true,
                    'api_access' => false,
                    'custom_branding' => false,
                ],
                'sort_order' => 2,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Untuk resto menengah dan multi-outlet',
                'price_monthly' => 599000,
                'price_yearly' => 5750400, // 599000 * 12 * 0.8
                'max_outlets' => 5,
                'max_users' => 25,
                'max_products' => -1, // Unlimited
                'features' => [
                    'pos_core' => true,
                    'product_management' => true,
                    'basic_reports' => true,
                    'customer_management' => true,
                    'multi_payment' => true,
                    'product_variant' => true,
                    'product_combo' => true,
                    'discount_promo' => true,
                    'inventory_basic' => true,
                    'inventory_advanced' => true,   // PO, receiving, adjustment, expiry
                    'table_management' => true,
                    'recipe_bom' => true,           // Auto stock deduction
                    'stock_transfer' => true,       // Between outlets
                    'waiter_app' => true,           // 1 device included
                    'qr_order' => true,
                    'kds' => false,                 // Only multi-station KDS in Enterprise
                    'manager_authorization' => true,
                    'export_excel_pdf' => true,
                    'loyalty_points' => true,
                    'api_access' => false,
                    'custom_branding' => false,
                ],
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Untuk jaringan resto, franchise, dan white-label',
                'price_monthly' => 1499000,
                'price_yearly' => 14390400, // 1499000 * 12 * 0.8
                'max_outlets' => -1, // Unlimited
                'max_users' => -1,   // Unlimited
                'max_products' => -1, // Unlimited
                'features' => [
                    'pos_core' => true,
                    'product_management' => true,
                    'basic_reports' => true,
                    'customer_management' => true,
                    'multi_payment' => true,
                    'product_variant' => true,
                    'product_combo' => true,
                    'discount_promo' => true,
                    'inventory_basic' => true,
                    'inventory_advanced' => true,
                    'table_management' => true,
                    'recipe_bom' => true,
                    'stock_transfer' => true,
                    'waiter_app' => true,           // Unlimited devices
                    'qr_order' => true,
                    'kds' => true,                  // Multi-station KDS
                    'manager_authorization' => true,
                    'export_excel_pdf' => true,
                    'loyalty_points' => true,
                    'api_access' => true,
                    'custom_branding' => true,
                    'dedicated_support' => true,
                    'sla_uptime' => true,
                ],
                'sort_order' => 4,
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
