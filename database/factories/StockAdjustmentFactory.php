<?php

namespace Database\Factories;

use App\Models\Outlet;
use App\Models\StockAdjustment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
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
            'adjustment_number' => 'ADJ-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(100, 999),
            'adjustment_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'type' => fake()->randomElement([
                StockAdjustment::TYPE_STOCK_TAKE,
                StockAdjustment::TYPE_CORRECTION,
                StockAdjustment::TYPE_OPENING_BALANCE,
            ]),
            'status' => StockAdjustment::STATUS_DRAFT,
            'reason' => fake()->sentence(),
            'notes' => fake()->optional()->paragraph(),
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockAdjustment::STATUS_DRAFT,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockAdjustment::STATUS_APPROVED,
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockAdjustment::STATUS_CANCELLED,
        ]);
    }

    public function stockTake(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockAdjustment::TYPE_STOCK_TAKE,
        ]);
    }

    public function correction(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockAdjustment::TYPE_CORRECTION,
        ]);
    }

    public function openingBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockAdjustment::TYPE_OPENING_BALANCE,
        ]);
    }
}
