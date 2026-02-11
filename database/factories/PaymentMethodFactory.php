<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            PaymentMethod::TYPE_CASH => ['name' => 'Cash', 'code' => 'CASH', 'opens_drawer' => true],
            PaymentMethod::TYPE_CARD => ['name' => 'Debit Card', 'code' => 'DEBIT', 'opens_drawer' => false],
            PaymentMethod::TYPE_DIGITAL_WALLET => ['name' => 'GoPay', 'code' => 'GOPAY', 'opens_drawer' => false],
            PaymentMethod::TYPE_TRANSFER => ['name' => 'Bank Transfer', 'code' => 'TRANSFER', 'opens_drawer' => false],
        ];

        $type = fake()->randomElement(array_keys($types));
        $config = $types[$type];

        return [
            'tenant_id' => Tenant::factory(),
            'code' => $config['code'],
            'name' => $config['name'],
            'type' => $type,
            'provider' => null,
            'icon' => null,
            'charge_percentage' => $type === PaymentMethod::TYPE_CASH ? 0 : fake()->randomElement([0, 1.5, 2, 2.5]),
            'charge_fixed' => 0,
            'requires_reference' => $type !== PaymentMethod::TYPE_CASH,
            'opens_cash_drawer' => $config['opens_drawer'],
            'sort_order' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'CASH',
            'name' => 'Cash',
            'type' => PaymentMethod::TYPE_CASH,
            'charge_percentage' => 0,
            'charge_fixed' => 0,
            'requires_reference' => false,
            'opens_cash_drawer' => true,
        ]);
    }

    public function qris(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'QRIS',
            'name' => 'QRIS',
            'type' => PaymentMethod::TYPE_DIGITAL_WALLET,
            'charge_percentage' => 0.7,
            'requires_reference' => true,
            'opens_cash_drawer' => false,
        ]);
    }
}
