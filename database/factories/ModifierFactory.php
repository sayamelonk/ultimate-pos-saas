<?php

namespace Database\Factories;

use App\Models\Modifier;
use App\Models\ModifierGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Modifier>
 */
class ModifierFactory extends Factory
{
    protected $model = Modifier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'modifier_group_id' => ModifierGroup::factory(),
            'name' => fake()->word(),
            'display_name' => fake()->words(2, true),
            'price' => fake()->randomFloat(2, 0, 50000),
            'cost_price' => fake()->randomFloat(2, 0, 25000),
            'quantity_used' => 1,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 0,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
