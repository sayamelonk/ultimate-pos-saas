<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupplierItem>
 */
class SupplierItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'supplier_sku' => fake()->optional(0.7)->bothify('???-####'),
            'unit_id' => Unit::factory(),
            'unit_conversion' => fake()->randomElement([1, 6, 12, 24]),
            'price' => fake()->randomFloat(2, 5000, 500000),
            'lead_time_days' => fake()->numberBetween(1, 14),
            'min_order_qty' => fake()->randomElement([1, 5, 10, 12, 24]),
            'is_preferred' => false,
            'is_active' => true,
        ];
    }

    public function preferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_preferred' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
