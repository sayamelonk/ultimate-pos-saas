<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Outlet;
use App\Models\StockBatch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockBatch>
 */
class StockBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $initialQty = fake()->randomFloat(4, 10, 500);
        $currentQty = fake()->randomFloat(4, 0, $initialQty);

        return [
            'tenant_id' => Tenant::factory(),
            'outlet_id' => Outlet::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'goods_receive_item_id' => null,
            'batch_number' => 'BTH-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(100, 999),
            'production_date' => fake()->optional(0.7)->dateTimeBetween('-60 days', '-1 day'),
            'expiry_date' => fake()->optional(0.8)->dateTimeBetween('now', '+365 days'),
            'initial_quantity' => $initialQty,
            'current_quantity' => $currentQty,
            'reserved_quantity' => 0,
            'unit_cost' => fake()->randomFloat(4, 1000, 50000),
            'status' => StockBatch::STATUS_ACTIVE,
            'supplier_batch_number' => fake()->optional()->bothify('SUP-BTH-####'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockBatch::STATUS_ACTIVE,
            'current_quantity' => $attributes['initial_quantity'] ?? fake()->randomFloat(4, 10, 500),
        ]);
    }

    public function depleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockBatch::STATUS_DEPLETED,
            'current_quantity' => 0,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockBatch::STATUS_EXPIRED,
            'expiry_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function disposed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockBatch::STATUS_DISPOSED,
        ]);
    }

    public function expiringSoon(int $days = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockBatch::STATUS_ACTIVE,
            'expiry_date' => now()->addDays($days),
        ]);
    }

    public function noExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => null,
        ]);
    }

    public function withReservation(float $qty = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'reserved_quantity' => $qty,
        ]);
    }
}
