<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Outlet;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Floor>
 */
class FloorFactory extends Factory
{
    protected $model = Floor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'outlet_id' => Outlet::factory(),
            'name' => $this->faker->unique()->randomElement(['Lantai 1', 'Lantai 2', 'Lantai 3', 'Rooftop', 'Basement', 'Main Floor', 'VIP Floor', 'Ground Floor', 'Upper Deck', 'Garden Area', 'Private Room', 'Outdoor']),
            'description' => $this->faker->optional()->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    /**
     * Inactive floor
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * With specific sort order
     */
    public function withSortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }

    /**
     * Main floor
     */
    public function mainFloor(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Main Floor',
            'sort_order' => 1,
        ]);
    }

    /**
     * VIP floor
     */
    public function vipFloor(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'VIP Floor',
            'sort_order' => 2,
        ]);
    }

    /**
     * Rooftop
     */
    public function rooftop(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Rooftop',
            'sort_order' => 3,
        ]);
    }
}
