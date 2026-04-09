<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionPayment>
 */
class TransactionPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 50000, 500000);

        return [
            'transaction_id' => Transaction::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'amount' => $amount,
            'charge_amount' => 0,
            'reference_number' => null,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method_id' => PaymentMethod::factory()->cash(),
            'charge_amount' => 0,
            'reference_number' => null,
        ]);
    }

    public function withCharge(float $chargePercentage): static
    {
        return $this->state(function (array $attributes) use ($chargePercentage) {
            $amount = $attributes['amount'] ?? 100000;
            $chargeAmount = $amount * ($chargePercentage / 100);

            return [
                'charge_amount' => $chargeAmount,
            ];
        });
    }

    public function withReference(?string $reference = null): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_number' => $reference ?? fake()->numerify('REF-########'),
        ]);
    }

    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }
}
