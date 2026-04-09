<?php

namespace Database\Factories;

use App\Models\KitchenStation;
use App\Models\Outlet;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class KitchenStationFactory extends Factory
{
    protected $model = KitchenStation::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'outlet_id' => Outlet::factory(),
            'name' => $this->faker->randomElement(['Grill', 'Fry', 'Cold', 'Beverage', 'Dessert']).' Station',
            'code' => strtoupper($this->faker->lexify('???')),
            'color' => $this->faker->hexColor(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
