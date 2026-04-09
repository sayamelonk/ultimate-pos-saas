<?php

namespace Database\Factories;

use App\Models\CashDrawerLog;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashDrawerLog>
 */
class CashDrawerLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $balanceBefore = fake()->randomFloat(2, 100000, 500000);
        $amount = fake()->randomFloat(2, 10000, 100000);
        $type = fake()->randomElement([
            CashDrawerLog::TYPE_CASH_IN,
            CashDrawerLog::TYPE_CASH_OUT,
            CashDrawerLog::TYPE_SALE,
        ]);

        $balanceAfter = in_array($type, [CashDrawerLog::TYPE_CASH_IN, CashDrawerLog::TYPE_SALE])
            ? $balanceBefore + $amount
            : $balanceBefore - $amount;

        return [
            'tenant_id' => Tenant::factory(),
            'outlet_id' => Outlet::factory(),
            'pos_session_id' => PosSession::factory(),
            'user_id' => User::factory(),
            'transaction_id' => null,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference' => fake()->optional()->numerify('REF-######'),
            'reason' => fake()->optional()->sentence(),
        ];
    }

    public function cashIn(): static
    {
        return $this->state(function (array $attributes) {
            $balanceBefore = $attributes['balance_before'] ?? 200000;
            $amount = $attributes['amount'] ?? fake()->randomFloat(2, 10000, 50000);

            return [
                'type' => CashDrawerLog::TYPE_CASH_IN,
                'balance_after' => $balanceBefore + $amount,
                'reason' => fake()->randomElement(['Petty cash', 'Change refill', 'Initial float']),
            ];
        });
    }

    public function cashOut(): static
    {
        return $this->state(function (array $attributes) {
            $balanceBefore = $attributes['balance_before'] ?? 200000;
            $amount = $attributes['amount'] ?? fake()->randomFloat(2, 10000, 50000);

            return [
                'type' => CashDrawerLog::TYPE_CASH_OUT,
                'balance_after' => $balanceBefore - $amount,
                'reason' => fake()->randomElement(['Bank deposit', 'Petty cash withdrawal', 'Supplier payment']),
            ];
        });
    }

    public function sale(): static
    {
        return $this->state(function (array $attributes) {
            $balanceBefore = $attributes['balance_before'] ?? 200000;
            $amount = $attributes['amount'] ?? fake()->randomFloat(2, 10000, 100000);

            return [
                'type' => CashDrawerLog::TYPE_SALE,
                'balance_after' => $balanceBefore + $amount,
            ];
        });
    }

    public function opening(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'] ?? fake()->randomFloat(2, 100000, 300000);

            return [
                'type' => CashDrawerLog::TYPE_OPENING,
                'balance_before' => 0,
                'balance_after' => $amount,
            ];
        });
    }

    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    public function withBalanceBefore(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_before' => $balance,
        ]);
    }
}
