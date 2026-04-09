<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $suffix = fake()->unique()->randomNumber(5);

        return [
            'name' => 'Plan '.$suffix,
            'slug' => 'plan-'.$suffix,
            'description' => 'Basic plan for small businesses',
            'price_monthly' => 99000,
            'price_yearly' => 950400,
            'max_outlets' => 1,
            'max_users' => 3,
            'max_products' => 100,
            'features' => [
                'pos_core' => true,
                'product_management' => true,
                'basic_reports' => true,
                'customer_management' => true,
                'multi_payment' => false,
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
            'is_active' => true,
            'sort_order' => 1,
        ];
    }

    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Starter',
            'slug' => 'starter',
            'price_monthly' => 99000,
            'max_outlets' => 1,
            'max_users' => 3,
            'max_products' => 100,
            'features' => [
                'pos_core' => true,
                'product_management' => true,
                'basic_reports' => true,
                'customer_management' => true,
                'multi_payment' => false,
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
        ]);
    }

    public function growth(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Growth',
            'slug' => 'growth',
            'price_monthly' => 299000,
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
                'inventory_basic' => true,
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
        ]);
    }

    public function professional(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Professional',
            'slug' => 'professional',
            'price_monthly' => 599000,
            'max_outlets' => 5,
            'max_users' => 25,
            'max_products' => -1,
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
                'waiter_app' => true,
                'qr_order' => true,
                'kds' => false,
                'manager_authorization' => true,
                'export_excel_pdf' => true,
                'loyalty_points' => true,
                'api_access' => false,
                'custom_branding' => false,
            ],
            'sort_order' => 3,
        ]);
    }

    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price_monthly' => 1499000,
            'max_outlets' => -1,
            'max_users' => -1,
            'max_products' => -1,
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
                'waiter_app' => true,
                'qr_order' => true,
                'kds' => true,
                'manager_authorization' => true,
                'export_excel_pdf' => true,
                'loyalty_points' => true,
                'api_access' => true,
                'custom_branding' => true,
            ],
            'sort_order' => 4,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withSortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_outlets' => -1,
            'max_users' => -1,
            'max_products' => -1,
        ]);
    }

    public function withLimits(int $outlets, int $users, int $products): static
    {
        return $this->state(fn (array $attributes) => [
            'max_outlets' => $outlets,
            'max_users' => $users,
            'max_products' => $products,
        ]);
    }

    public function withPricing(float $monthly, float $yearly): static
    {
        return $this->state(fn (array $attributes) => [
            'price_monthly' => $monthly,
            'price_yearly' => $yearly,
        ]);
    }
}
