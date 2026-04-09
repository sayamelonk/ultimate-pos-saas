<?php

namespace Database\Factories;

use App\Models\ModifierGroup;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModifierGroup>
 */
class ModifierGroupFactory extends Factory
{
    protected $model = ModifierGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->word(),
            'display_name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'selection_type' => fake()->randomElement([ModifierGroup::SELECTION_SINGLE, ModifierGroup::SELECTION_MULTIPLE]),
            'min_selections' => 0,
            'max_selections' => fake()->numberBetween(1, 5),
            'is_required' => fake()->boolean(30),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function singleSelect(): static
    {
        return $this->state(fn (array $attributes) => [
            'selection_type' => ModifierGroup::SELECTION_SINGLE,
            'min_selections' => 1,
            'max_selections' => 1,
        ]);
    }

    public function multipleSelect(): static
    {
        return $this->state(fn (array $attributes) => [
            'selection_type' => ModifierGroup::SELECTION_MULTIPLE,
        ]);
    }

    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
