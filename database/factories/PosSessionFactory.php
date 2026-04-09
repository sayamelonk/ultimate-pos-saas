<?php

namespace Database\Factories;

use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PosSession>
 */
class PosSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'outlet_id' => Outlet::factory(),
            'user_id' => User::factory(),
            'session_number' => 'SES-'.fake()->unique()->numerify('######'),
            'opening_cash' => fake()->randomFloat(2, 100000, 500000),
            'closing_cash' => null,
            'expected_cash' => null,
            'cash_difference' => null,
            'opening_notes' => fake()->optional()->sentence(),
            'closing_notes' => null,
            'opened_at' => now(),
            'closed_at' => null,
            'closed_by' => null,
            'status' => PosSession::STATUS_OPEN,
        ];
    }

    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            $openingCash = $attributes['opening_cash'] ?? 200000;
            $expectedCash = $openingCash + fake()->randomFloat(2, 50000, 300000);
            $closingCash = $expectedCash + fake()->randomFloat(2, -10000, 10000);

            return [
                'closing_cash' => $closingCash,
                'expected_cash' => $expectedCash,
                'cash_difference' => $closingCash - $expectedCash,
                'closing_notes' => fake()->optional()->sentence(),
                'closed_at' => now(),
                'closed_by' => $attributes['user_id'] ?? User::factory(),
                'status' => PosSession::STATUS_CLOSED,
            ];
        });
    }

    public function withOpeningCash(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'opening_cash' => $amount,
        ]);
    }
}
