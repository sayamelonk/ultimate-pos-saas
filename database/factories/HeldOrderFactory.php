<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\HeldOrder;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeldOrder>
 */
class HeldOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50000, 300000);
        $discountAmount = fake()->boolean(30) ? fake()->randomFloat(2, 5000, 30000) : 0;
        $taxAmount = ($subtotal - $discountAmount) * 0.1;
        $serviceChargeAmount = fake()->boolean(50) ? ($subtotal - $discountAmount) * 0.05 : 0;
        $grandTotal = $subtotal - $discountAmount + $taxAmount + $serviceChargeAmount;

        return [
            'tenant_id' => Tenant::factory(),
            'outlet_id' => Outlet::factory(),
            'pos_session_id' => PosSession::factory(),
            'user_id' => User::factory(),
            'customer_id' => null,
            'hold_number' => 'HLD-'.fake()->unique()->numerify('########'),
            'reference' => null,
            'table_number' => null,
            'items' => $this->generateItems(),
            'discounts' => [],
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'service_charge_amount' => $serviceChargeAmount,
            'grand_total' => $grandTotal,
            'notes' => fake()->optional()->sentence(),
            'expires_at' => now()->addHours(4),
        ];
    }

    protected function generateItems(): array
    {
        $items = [];
        $itemCount = fake()->numberBetween(1, 5);

        for ($i = 0; $i < $itemCount; $i++) {
            $quantity = fake()->numberBetween(1, 3);
            $unitPrice = fake()->randomFloat(2, 15000, 75000);

            $items[] = [
                'product_id' => fake()->uuid(),
                'product_name' => fake()->words(2, true),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $quantity * $unitPrice,
                'modifiers' => [],
                'notes' => null,
            ];
        }

        return $items;
    }

    public function withCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => Customer::factory(),
        ]);
    }

    public function withReference(string $reference): static
    {
        return $this->state(fn (array $attributes) => [
            'reference' => $reference,
        ]);
    }

    public function withTableNumber(string $tableNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'table_number' => $tableNumber,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function notExpiring(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }

    public function expiresIn(int $hours): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours($hours),
        ]);
    }

    public function withItems(array $items): static
    {
        return $this->state(fn (array $attributes) => [
            'items' => $items,
        ]);
    }

    public function withDiscounts(array $discounts): static
    {
        return $this->state(fn (array $attributes) => [
            'discounts' => $discounts,
        ]);
    }

    public function withTotals(float $subtotal, float $discountAmount = 0, float $taxAmount = 0, float $serviceCharge = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'service_charge_amount' => $serviceCharge,
            'grand_total' => $subtotal - $discountAmount + $taxAmount + $serviceCharge,
        ]);
    }
}
