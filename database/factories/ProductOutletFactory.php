<?php

namespace Database\Factories;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductOutlet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOutlet>
 */
class ProductOutletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'outlet_id' => Outlet::factory(),
            'is_available' => true,
            'custom_price' => null,
            'is_featured' => false,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function withCustomPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_price' => $price,
        ]);
    }

    public function withSortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }
}
