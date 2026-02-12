<?php

namespace Database\Factories;

use App\Models\InventoryCategory;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $costPrice = fake()->randomFloat(2, 1000, 100000);
        $minStock = fake()->randomFloat(2, 5, 20);

        return [
            'tenant_id' => Tenant::factory(),
            'category_id' => null,
            'unit_id' => Unit::factory(),
            'purchase_unit_id' => null,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-????-####')),
            'barcode' => fake()->optional(0.7)->ean13(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'image' => null,
            'purchase_unit_conversion' => 1,
            'cost_price' => $costPrice,
            'min_stock' => $minStock,
            'max_stock' => $minStock * fake()->numberBetween(5, 10),
            'reorder_point' => $minStock * 2,
            'reorder_qty' => $minStock * 3,
            'shelf_life_days' => fake()->optional(0.5)->numberBetween(7, 365),
            'track_batches' => fake()->boolean(20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withCategory(InventoryCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $category->tenant_id,
            'category_id' => $category->id,
        ]);
    }

    public function trackBatches(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_batches' => true,
        ]);
    }
}
