<?php

namespace Database\Factories;

use App\Models\KitchenOrder;
use App\Models\Outlet;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class KitchenOrderFactory extends Factory
{
    protected $model = KitchenOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'outlet_id' => Outlet::factory(),
            'transaction_id' => Transaction::factory(),
            'station_id' => null,
            'table_id' => null,
            'order_number' => 'ORD-'.$this->faker->unique()->numerify('######'),
            'order_type' => $this->faker->randomElement(['dine_in', 'takeaway', 'delivery']),
            'table_name' => $this->faker->randomElement(['Table 1', 'Table 2', 'Table 3', null]),
            'customer_name' => $this->faker->optional()->name(),
            'status' => KitchenOrder::STATUS_PENDING,
            'priority' => KitchenOrder::PRIORITY_NORMAL,
            'notes' => $this->faker->optional()->sentence(),
            'cancel_reason' => null,
            'started_at' => null,
            'completed_at' => null,
            'served_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KitchenOrder::STATUS_PENDING,
        ]);
    }

    public function preparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KitchenOrder::STATUS_PREPARING,
            'started_at' => now(),
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KitchenOrder::STATUS_READY,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);
    }

    public function served(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KitchenOrder::STATUS_SERVED,
            'started_at' => now()->subMinutes(15),
            'completed_at' => now()->subMinutes(5),
            'served_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KitchenOrder::STATUS_CANCELLED,
            'cancel_reason' => 'Customer cancelled',
        ]);
    }

    public function rush(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => KitchenOrder::PRIORITY_RUSH,
        ]);
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => KitchenOrder::PRIORITY_VIP,
        ]);
    }
}
