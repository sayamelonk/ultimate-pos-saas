<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(4, 1, 100);
        $stockBefore = fake()->randomFloat(4, 50, 500);

        return [
            'outlet_id' => Outlet::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'batch_id' => null,
            'type' => fake()->randomElement([
                StockMovement::TYPE_IN,
                StockMovement::TYPE_OUT,
                StockMovement::TYPE_ADJUSTMENT,
            ]),
            'reference_type' => null,
            'reference_id' => null,
            'quantity' => $quantity,
            'cost_price' => fake()->randomFloat(2, 1000, 50000),
            'stock_before' => $stockBefore,
            'stock_after' => $stockBefore + $quantity,
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function incoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovement::TYPE_IN,
            'quantity' => abs($attributes['quantity'] ?? fake()->randomFloat(4, 1, 100)),
        ]);
    }

    public function outgoing(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = abs($attributes['quantity'] ?? fake()->randomFloat(4, 1, 100));

            return [
                'type' => StockMovement::TYPE_OUT,
                'quantity' => -$quantity,
                'stock_after' => ($attributes['stock_before'] ?? 100) - $quantity,
            ];
        });
    }

    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovement::TYPE_ADJUSTMENT,
        ]);
    }

    public function transferIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovement::TYPE_TRANSFER_IN,
            'quantity' => abs($attributes['quantity'] ?? fake()->randomFloat(4, 1, 100)),
        ]);
    }

    public function transferOut(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = abs($attributes['quantity'] ?? fake()->randomFloat(4, 1, 100));

            return [
                'type' => StockMovement::TYPE_TRANSFER_OUT,
                'quantity' => -$quantity,
            ];
        });
    }

    public function waste(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = abs($attributes['quantity'] ?? fake()->randomFloat(4, 1, 100));

            return [
                'type' => StockMovement::TYPE_WASTE,
                'quantity' => -$quantity,
            ];
        });
    }
}
