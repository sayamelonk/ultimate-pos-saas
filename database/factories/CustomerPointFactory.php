<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerPoint;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerPoint>
 */
class CustomerPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $points = fake()->randomFloat(2, 10, 500);

        return [
            'customer_id' => Customer::factory(),
            'transaction_id' => null,
            'type' => CustomerPoint::TYPE_EARNED,
            'points' => $points,
            'balance_before' => 0,
            'balance_after' => $points,
            'description' => null,
            'created_by' => User::factory(),
        ];
    }

    public function earned(float $points = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CustomerPoint::TYPE_EARNED,
            'points' => $points,
        ]);
    }

    public function redeemed(float $points = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CustomerPoint::TYPE_REDEEMED,
            'points' => -$points,
        ]);
    }

    public function expired(float $points = 25): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CustomerPoint::TYPE_EXPIRED,
            'points' => -$points,
            'description' => 'Points expired',
        ]);
    }

    public function adjustment(float $points = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CustomerPoint::TYPE_ADJUSTMENT,
            'points' => $points,
            'description' => 'Manual adjustment',
        ]);
    }

    public function withTransaction(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_id' => Transaction::factory(),
        ]);
    }

    public function withDescription(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }

    public function withBalance(float $before, float $after): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_before' => $before,
            'balance_after' => $after,
        ]);
    }
}
