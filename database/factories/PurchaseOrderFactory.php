<?php

namespace Database\Factories;

use App\Models\Outlet;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100000, 10000000);
        $taxAmount = $subtotal * 0.11; // PPN 11%
        $discountAmount = fake()->randomFloat(2, 0, $subtotal * 0.1);

        return [
            'tenant_id' => Tenant::factory(),
            'outlet_id' => Outlet::factory(),
            'supplier_id' => Supplier::factory(),
            'po_number' => 'PO-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(100, 999),
            'order_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'expected_date' => fake()->dateTimeBetween('now', '+30 days'),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $subtotal + $taxAmount - $discountAmount,
            'notes' => fake()->optional()->paragraph(),
            'terms' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrder::STATUS_DRAFT,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrder::STATUS_SUBMITTED,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrder::STATUS_APPROVED,
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrder::STATUS_PARTIAL,
            'approved_by' => User::factory(),
            'approved_at' => now()->subDays(3),
        ]);
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'approved_by' => User::factory(),
            'approved_at' => now()->subDays(5),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrder::STATUS_CANCELLED,
        ]);
    }
}
