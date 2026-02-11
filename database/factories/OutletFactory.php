<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Outlet>
 */
class OutletFactory extends Factory
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
            'code' => strtoupper(fake()->unique()->bothify('OUT-##')),
            'name' => fake()->company().' '.fake()->randomElement(['Branch', 'Outlet', 'Store']),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'opening_time' => '08:00',
            'closing_time' => '22:00',
            'tax_percentage' => null,
            'service_charge_percentage' => null,
            'receipt_header' => null,
            'receipt_footer' => 'Terima kasih atas kunjungan Anda!',
            'receipt_show_logo' => true,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withCustomTax(float $tax = 10, float $serviceCharge = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_percentage' => $tax,
            'service_charge_percentage' => $serviceCharge,
        ]);
    }
}
