<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustmentItem>
 */
class StockAdjustmentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $systemQty = fake()->randomFloat(4, 10, 100);
        $actualQty = $systemQty + fake()->randomFloat(4, -10, 10);
        $difference = $actualQty - $systemQty;
        $costPrice = fake()->randomFloat(2, 1000, 50000);

        return [
            'stock_adjustment_id' => StockAdjustment::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'batch_id' => null,
            'system_qty' => $systemQty,
            'actual_qty' => $actualQty,
            'difference' => $difference,
            'cost_price' => $costPrice,
            'value_difference' => $difference * $costPrice,
        ];
    }

    public function increase(float $amount = 10): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            $systemQty = $attributes['system_qty'] ?? 50;
            $actualQty = $systemQty + $amount;
            $costPrice = $attributes['cost_price'] ?? 10000;

            return [
                'system_qty' => $systemQty,
                'actual_qty' => $actualQty,
                'difference' => $amount,
                'value_difference' => $amount * $costPrice,
            ];
        });
    }

    public function decrease(float $amount = 10): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            $systemQty = $attributes['system_qty'] ?? 50;
            $actualQty = $systemQty - $amount;
            $costPrice = $attributes['cost_price'] ?? 10000;

            return [
                'system_qty' => $systemQty,
                'actual_qty' => $actualQty,
                'difference' => -$amount,
                'value_difference' => -$amount * $costPrice,
            ];
        });
    }

    public function noVariance(): static
    {
        return $this->state(function (array $attributes) {
            $qty = $attributes['system_qty'] ?? 50;

            return [
                'system_qty' => $qty,
                'actual_qty' => $qty,
                'difference' => 0,
                'value_difference' => 0,
            ];
        });
    }
}
