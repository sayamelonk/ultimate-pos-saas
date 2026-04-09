<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Recipe;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'product_id' => InventoryItem::factory(),
            'name' => fake()->words(3, true).' Recipe',
            'description' => fake()->optional()->paragraph(),
            'instructions' => fake()->optional()->paragraphs(3, true),
            'yield_qty' => fake()->randomFloat(4, 1, 10),
            'yield_unit_id' => Unit::factory(),
            'estimated_cost' => fake()->randomFloat(2, 10000, 500000),
            'prep_time_minutes' => fake()->numberBetween(5, 30),
            'cook_time_minutes' => fake()->numberBetween(10, 60),
            'version' => 1,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withVersion(int $version): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => $version,
        ]);
    }

    public function quickRecipe(): static
    {
        return $this->state(fn (array $attributes) => [
            'prep_time_minutes' => fake()->numberBetween(1, 5),
            'cook_time_minutes' => fake()->numberBetween(1, 10),
        ]);
    }

    public function complexRecipe(): static
    {
        return $this->state(fn (array $attributes) => [
            'prep_time_minutes' => fake()->numberBetween(30, 60),
            'cook_time_minutes' => fake()->numberBetween(60, 180),
        ]);
    }
}
