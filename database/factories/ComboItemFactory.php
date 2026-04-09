<?php

namespace Database\Factories;

use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComboItem>
 */
class ComboItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'combo_id' => Combo::factory(),
            'product_id' => Product::factory(),
            'category_id' => null,
            'group_name' => fake()->randomElement(['Main', 'Side', 'Drink', 'Dessert']),
            'quantity' => 1,
            'is_required' => true,
            'allow_variant_selection' => false,
            'price_adjustment' => 0,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => false,
        ]);
    }

    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    public function withCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => null,
            'category_id' => ProductCategory::factory(),
        ]);
    }

    public function allowVariantSelection(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_variant_selection' => true,
        ]);
    }

    public function withQuantity(int $qty): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $qty,
        ]);
    }

    public function withPriceAdjustment(float $adjustment): static
    {
        return $this->state(fn (array $attributes) => [
            'price_adjustment' => $adjustment,
        ]);
    }

    public function withGroupName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'group_name' => $name,
        ]);
    }
}
