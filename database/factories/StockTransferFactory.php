<?php

namespace Database\Factories;

use App\Models\Outlet;
use App\Models\StockTransfer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
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
            'from_outlet_id' => Outlet::factory(),
            'to_outlet_id' => Outlet::factory(),
            'transfer_number' => 'TRF-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(100, 999),
            'transfer_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'status' => StockTransfer::STATUS_DRAFT,
            'notes' => fake()->optional()->paragraph(),
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
            'received_by' => null,
            'received_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockTransfer::STATUS_DRAFT,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockTransfer::STATUS_PENDING,
        ]);
    }

    public function inTransit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockTransfer::STATUS_IN_TRANSIT,
            'approved_by' => User::factory(),
            'approved_at' => now()->subHours(2),
        ]);
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockTransfer::STATUS_RECEIVED,
            'approved_by' => User::factory(),
            'approved_at' => now()->subHours(4),
            'received_by' => User::factory(),
            'received_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockTransfer::STATUS_CANCELLED,
        ]);
    }
}
