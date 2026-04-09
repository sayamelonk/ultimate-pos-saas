<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeItem>
 */
class RecipeItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'unit_id' => Unit::factory(),
            'quantity' => fake()->randomFloat(4, 0.1, 10),
            'waste_percentage' => fake()->randomFloat(2, 0, 15),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    public function noWaste(): static
    {
        return $this->state(fn (array $attributes) => [
            'waste_percentage' => 0,
        ]);
    }

    public function highWaste(): static
    {
        return $this->state(fn (array $attributes) => [
            'waste_percentage' => fake()->randomFloat(2, 10, 25),
        ]);
    }
}
