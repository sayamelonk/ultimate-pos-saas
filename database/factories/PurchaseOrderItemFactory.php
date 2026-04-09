<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(4, 5, 100);
        $unitPrice = fake()->randomFloat(2, 10000, 500000);
        $discountPercent = fake()->randomFloat(2, 0, 10);
        $subtotal = $quantity * $unitPrice;
        $discountAmount = $subtotal * ($discountPercent / 100);
        $afterDiscount = $subtotal - $discountAmount;
        $taxPercent = 11;
        $taxAmount = $afterDiscount * ($taxPercent / 100);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'unit_id' => Unit::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'total' => $afterDiscount + $taxAmount,
            'received_qty' => 0,
            'unit_conversion' => 1,
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

    public function partiallyReceived(float $pct = 0.5): static
    {
        return $this->state(function (array $attributes) use ($pct) {
            $qty = $attributes['quantity'] ?? 50;

            return [
                'received_qty' => $qty * $pct,
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
