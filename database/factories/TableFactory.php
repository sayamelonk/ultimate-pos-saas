<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Outlet;
use App\Models\Table;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Table>
 */
class TableFactory extends Factory
{
    protected $model = Table::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = $this->faker->unique()->numberBetween(1, 100);

        return [
            'tenant_id' => Tenant::factory(),
            'outlet_id' => Outlet::factory(),
            'floor_id' => Floor::factory(),
            'number' => $number,
            'name' => 'Meja '.$number,
            'capacity' => $this->faker->randomElement([2, 4, 6, 8]),
            'position_x' => $this->faker->numberBetween(0, 500),
            'position_y' => $this->faker->numberBetween(0, 500),
            'width' => $this->faker->numberBetween(60, 120),
            'height' => $this->faker->numberBetween(60, 120),
            'shape' => $this->faker->randomElement([Table::SHAPE_RECTANGLE, Table::SHAPE_CIRCLE, Table::SHAPE_SQUARE]),
            'status' => Table::STATUS_AVAILABLE,
            'is_active' => true,
        ];
    }

    /**
     * Occupied table
     */
    public function occupied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Table::STATUS_OCCUPIED,
        ]);
    }

    /**
     * Reserved table
     */
    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Table::STATUS_RESERVED,
        ]);
    }

    /**
     * Dirty table
     */
    public function dirty(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Table::STATUS_DIRTY,
        ]);
    }

    /**
     * Inactive table
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Available table
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Table::STATUS_AVAILABLE,
        ]);
    }

    /**
     * Rectangle shape
     */
    public function rectangle(): static
    {
        return $this->state(fn (array $attributes) => [
            'shape' => Table::SHAPE_RECTANGLE,
            'width' => 120,
            'height' => 80,
        ]);
    }

    /**
     * Circle shape
     */
    public function circle(): static
    {
        return $this->state(fn (array $attributes) => [
            'shape' => Table::SHAPE_CIRCLE,
            'width' => 80,
            'height' => 80,
        ]);
    }

    /**
     * Square shape
     */
    public function square(): static
    {
        return $this->state(fn (array $attributes) => [
            'shape' => Table::SHAPE_SQUARE,
            'width' => 80,
            'height' => 80,
        ]);
    }

    /**
     * With specific capacity
     */
    public function withCapacity(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => $capacity,
        ]);
    }

    /**
     * With specific number
     */
    public function withNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'number' => $number,
            'name' => 'Meja '.$number,
        ]);
    }

    /**
     * With specific position
     */
    public function withPosition(int $x, int $y): static
    {
        return $this->state(fn (array $attributes) => [
            'position_x' => $x,
            'position_y' => $y,
        ]);
    }
}
