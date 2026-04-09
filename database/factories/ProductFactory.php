<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Nasi Goreng',
            'Mie Goreng',
            'Ayam Bakar',
            'Sate Ayam',
            'Es Teh Manis',
            'Kopi Susu',
            'Jus Alpukat',
            'Cappuccino',
            'Latte',
            'Americano',
            'Burger Classic',
            'French Fries',
            'Chicken Wings',
            'Pasta Carbonara',
            'Pizza Margherita',
        ]);

        $basePrice = fake()->randomElement([15000, 20000, 25000, 30000, 35000, 40000, 45000, 50000]);

        return [
            'tenant_id' => Tenant::factory(),
            'category_id' => ProductCategory::factory(),
            'recipe_id' => null,
            'sku' => strtoupper(fake()->unique()->bothify('PRD-####')),
            'barcode' => fake()->unique()->ean13(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'description' => fake()->sentence(),
            'image' => null,
            'base_price' => $basePrice,
            'cost_price' => $basePrice * 0.4,
            'product_type' => Product::TYPE_SINGLE,
            'track_stock' => false,
            'inventory_item_id' => null,
            'is_active' => true,
            'is_featured' => fake()->boolean(20),
            'show_in_pos' => true,
            'show_in_menu' => true,
            'allow_notes' => true,
            'prep_time_minutes' => fake()->randomElement([5, 10, 15, 20]),
            'sort_order' => fake()->numberBetween(1, 100),
            'tags' => [],
            'allergens' => [],
            'nutritional_info' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function hiddenFromPos(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_in_pos' => false,
        ]);
    }

    public function hiddenFromMenu(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_in_menu' => false,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function single(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => Product::TYPE_SINGLE,
        ]);
    }

    public function variant(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => Product::TYPE_VARIANT,
        ]);
    }

    public function combo(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => Product::TYPE_COMBO,
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
            'base_price' => $price,
            'cost_price' => $price * 0.4,
        ]);
    }
}
