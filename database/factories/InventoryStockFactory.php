<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Outlet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryStock>
 */
class InventoryStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $avgCost = fake()->randomFloat(2, 1000, 50000);
        $quantity = fake()->randomFloat(2, 10, 500);

        return [
            'outlet_id' => Outlet::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => $quantity,
            'reserved_qty' => fake()->randomFloat(2, 0, $quantity * 0.1),
            'avg_cost' => $avgCost,
            'last_cost' => $avgCost * fake()->randomFloat(2, 0.95, 1.05),
            'last_received_at' => fake()->optional(0.8)->dateTimeBetween('-30 days', 'now'),
            'last_issued_at' => fake()->optional(0.6)->dateTimeBetween('-7 days', 'now'),
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->randomFloat(2, 1, 10),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'reserved_qty' => 0,
        ]);
    }
}
