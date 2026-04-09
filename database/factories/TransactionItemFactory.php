<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 10000, 100000);
        $discountAmount = fake()->boolean(20) ? fake()->randomFloat(2, 1000, 10000) : 0;
        $modifiersTotal = fake()->boolean(30) ? fake()->randomFloat(2, 2000, 15000) : 0;
        $subtotal = ($unitPrice * $quantity) + $modifiersTotal - $discountAmount;

        $itemName = fake()->words(3, true);

        return [
            'transaction_id' => Transaction::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'inventory_item_id' => null,
            'item_name' => $itemName,
            'item_sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'unit_name' => fake()->randomElement(['pcs', 'box', 'kg', 'ltr', 'pack']),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'base_price' => $unitPrice,
            'variant_price_adjustment' => 0,
            'cost_price' => $unitPrice * 0.6,
            'modifiers' => [],
            'modifiers_total' => $modifiersTotal,
            'discount_amount' => $discountAmount,
            'subtotal' => $subtotal,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function withVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_variant_id' => ProductVariant::factory(),
        ]);
    }

    public function withModifiers(array $modifiers): static
    {
        $modifiersTotal = collect($modifiers)->sum('price');

        return $this->state(fn (array $attributes) => [
            'modifiers' => $modifiers,
            'modifiers_total' => $modifiersTotal,
        ]);
    }

    public function withQuantity(int $qty): static
    {
        return $this->state(function (array $attributes) use ($qty) {
            $subtotal = ($attributes['unit_price'] * $qty) +
                        $attributes['modifiers_total'] -
                        $attributes['discount_amount'];

            return [
                'quantity' => $qty,
                'subtotal' => $subtotal,
            ];
        });
    }
}
