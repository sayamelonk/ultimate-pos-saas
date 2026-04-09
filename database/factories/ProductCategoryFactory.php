<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Makanan',
            'Minuman',
            'Snack',
            'Dessert',
            'Coffee',
            'Tea',
            'Juice',
            'Smoothie',
            'Appetizer',
            'Main Course',
            'Side Dish',
            'Bakery',
            'Ice Cream',
            'Pasta',
            'Pizza',
        ]);

        return [
            'tenant_id' => Tenant::factory(),
            'parent_id' => null,
            'code' => strtoupper(fake()->unique()->bothify('CAT-###')),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'description' => fake()->sentence(),
            'image' => null,
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['utensils', 'coffee', 'glass-water', 'cake', 'bowl-food']),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
            'show_in_pos' => true,
            'show_in_menu' => true,
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

    public function withParent(ProductCategory $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'tenant_id' => $parent->tenant_id,
        ]);
    }
}
