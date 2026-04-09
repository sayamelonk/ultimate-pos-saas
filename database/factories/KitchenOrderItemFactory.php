<?php

namespace Database\Factories;

use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class KitchenOrderItemFactory extends Factory
{
    protected $model = KitchenOrderItem::class;

    public function definition(): array
    {
        return [
            'kitchen_order_id' => KitchenOrder::factory(),
            'transaction_item_id' => TransactionItem::factory(),
            'station_id' => null,
            'item_name' => $this->faker->randomElement(['Nasi Goreng', 'Mie Goreng', 'Ayam Bakar', 'Sate Ayam']),
            'quantity' => $this->faker->numberBetween(1, 5),
            'modifiers' => null,
            'notes' => $this->faker->optional()->sentence(),
            'status' => KitchenOrderItem::STATUS_PENDING,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KitchenOrderItem::STATUS_PENDING,
        ]);
    }

    public function preparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KitchenOrderItem::STATUS_PREPARING,
            'started_at' => now(),
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KitchenOrderItem::STATUS_READY,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KitchenOrderItem::STATUS_CANCELLED,
        ]);
    }

    public function withModifiers(): static
    {
        return $this->state(fn (array $attributes) => [
            'modifiers' => [
                ['name' => 'Extra Spicy', 'price' => 2000],
                ['name' => 'No Onion', 'price' => 0],
            ],
        ]);
    }
}
