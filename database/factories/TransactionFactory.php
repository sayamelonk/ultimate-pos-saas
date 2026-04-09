<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50000, 500000);
        $discountAmount = fake()->boolean(30) ? fake()->randomFloat(2, 5000, 50000) : 0;
        $taxPercentage = 10;
        $serviceChargePercentage = fake()->boolean(50) ? 5 : 0;
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = $taxableAmount * ($taxPercentage / 100);
        $serviceChargeAmount = $taxableAmount * ($serviceChargePercentage / 100);
        $grandTotal = $subtotal - $discountAmount + $taxAmount + $serviceChargeAmount;
        $paymentAmount = ceil($grandTotal / 1000) * 1000;
        $changeAmount = $paymentAmount - $grandTotal;

        return [
            'tenant_id' => Tenant::factory(),
            'outlet_id' => Outlet::factory(),
            'pos_session_id' => PosSession::factory(),
            'table_id' => null,
            'table_session_id' => null,
            'order_type' => fake()->randomElement([
                Transaction::ORDER_TYPE_DINE_IN,
                Transaction::ORDER_TYPE_TAKEAWAY,
                Transaction::ORDER_TYPE_DELIVERY,
            ]),
            'customer_id' => fake()->boolean(30) ? Customer::factory() : null,
            'user_id' => User::factory(),
            'transaction_number' => 'TRX-'.fake()->unique()->numerify('########'),
            'type' => Transaction::TYPE_SALE,
            'original_transaction_id' => null,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'service_charge_amount' => $serviceChargeAmount,
            'rounding' => 0,
            'grand_total' => $grandTotal,
            'payment_amount' => $paymentAmount,
            'change_amount' => $changeAmount,
            'tax_percentage' => $taxPercentage,
            'service_charge_percentage' => $serviceChargePercentage,
            'points_earned' => 0,
            'points_redeemed' => 0,
            'notes' => null,
            'status' => Transaction::STATUS_COMPLETED,
            'completed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_PENDING,
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_VOIDED,
        ]);
    }

    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_REFUND,
        ]);
    }

    public function dineIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_type' => Transaction::ORDER_TYPE_DINE_IN,
        ]);
    }

    public function takeaway(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_type' => Transaction::ORDER_TYPE_TAKEAWAY,
        ]);
    }

    public function delivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_type' => Transaction::ORDER_TYPE_DELIVERY,
        ]);
    }

    public function withGrandTotal(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'grand_total' => $amount,
            'subtotal' => $amount,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
        ]);
    }

    public function withCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => Customer::factory(),
        ]);
    }

    public function completedAt(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => $date,
            'created_at' => $date,
        ]);
    }
}
