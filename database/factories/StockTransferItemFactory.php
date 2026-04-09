<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransferItem>
 */
class StockTransferItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(4, 5, 100);

        return [
            'stock_transfer_id' => StockTransfer::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'unit_id' => Unit::factory(),
            'batch_id' => null,
            'quantity' => $quantity,
            'received_qty' => 0,
            'cost_price' => fake()->randomFloat(2, 1000, 50000),
        ];
    }

    public function fullyReceived(): static
    {
        return $this->state(function (array $attributes) {
            $qty = $attributes['quantity'] ?? 50;

            return [
                'received_qty' => $qty,
            ];
        });
    }

    public function partiallyReceived(float $receivedPct = 0.5): static
    {
        return $this->state(function (array $attributes) use ($receivedPct) {
            $qty = $attributes['quantity'] ?? 50;

            return [
                'received_qty' => $qty * $receivedPct,
            ];
        });
    }

    public function notReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'received_qty' => 0,
        ]);
    }
}
