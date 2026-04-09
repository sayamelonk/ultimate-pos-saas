<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Small',
            'Medium',
            'Large',
            'Extra Large',
            'Hot',
            'Cold',
            'Ice',
            'Regular',
            'Spicy',
            'Original',
        ]);

        $price = fake()->randomElement([15000, 20000, 25000, 30000, 35000]);

        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('VAR-####')),
            'barcode' => fake()->unique()->ean13(),
            'name' => $name,
            'option_ids' => [],
            'price' => $price,
            'cost_price' => $price * 0.4,
            'inventory_item_id' => null,
            'recipe_id' => null,
            'image' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withBarcode(string $barcode): static
    {
        return $this->state(fn (array $attributes) => [
            'barcode' => $barcode,
        ]);
    }

    public function withPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
            'cost_price' => $price * 0.4,
        ]);
    }
}
