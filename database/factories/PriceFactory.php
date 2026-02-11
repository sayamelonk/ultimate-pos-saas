<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Price>
 */
class PriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sellingPrice = fake()->randomFloat(2, 5000, 200000);
        $memberPrice = $sellingPrice * 0.9;
        $minSellingPrice = $sellingPrice * 0.75;

        return [
            'tenant_id' => Tenant::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'outlet_id' => Outlet::factory(),
            'selling_price' => $sellingPrice,
            'member_price' => fake()->optional(0.5)->passthrough($memberPrice),
            'min_selling_price' => fake()->optional(0.3)->passthrough($minSellingPrice),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
