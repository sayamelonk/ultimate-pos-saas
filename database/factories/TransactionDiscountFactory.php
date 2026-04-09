<?php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\Transaction;
use App\Models\TransactionDiscount;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionDiscount>
 */
class TransactionDiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([
            TransactionDiscount::TYPE_PERCENTAGE,
            TransactionDiscount::TYPE_FIXED_AMOUNT,
        ]);

        $value = $type === TransactionDiscount::TYPE_PERCENTAGE
            ? fake()->randomFloat(2, 5, 25)
            : fake()->randomFloat(2, 5000, 50000);

        $amount = $type === TransactionDiscount::TYPE_PERCENTAGE
            ? fake()->randomFloat(2, 5000, 25000)
            : $value;

        return [
            'transaction_id' => Transaction::factory(),
            'transaction_item_id' => null,
            'discount_id' => null,
            'discount_name' => fake()->randomElement(['Member Discount', 'Promo Akhir Tahun', 'Happy Hour', 'Flash Sale', 'Birthday Discount']),
            'type' => $type,
            'value' => $value,
            'amount' => $amount,
        ];
    }

    public function percentage(float $percent = 10): static
    {
        return $this->state(function (array $attributes) use ($percent) {
            return [
                'type' => TransactionDiscount::TYPE_PERCENTAGE,
                'value' => $percent,
                'discount_name' => "Diskon {$percent}%",
            ];
        });
    }

    public function fixedAmount(float $amount = 10000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionDiscount::TYPE_FIXED_AMOUNT,
            'value' => $amount,
            'amount' => $amount,
            'discount_name' => 'Potongan Harga',
        ]);
    }

    public function orderLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_item_id' => null,
        ]);
    }

    public function itemLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_item_id' => TransactionItem::factory(),
        ]);
    }

    public function withDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_id' => Discount::factory(),
        ]);
    }

    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }
}
