<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('TNT-####')),
            'name' => fake()->company(),
            'logo' => null,
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'tax_percentage' => 11.00,
            'service_charge_percentage' => fake()->randomElement([0, 5, 10]),
            'subscription_plan' => fake()->randomElement(['free', 'basic', 'premium']),
            'subscription_expires_at' => fake()->dateTimeBetween('+1 month', '+1 year'),
            'max_outlets' => fake()->randomElement([1, 3, 5, 10]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan' => 'free',
            'max_outlets' => 1,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan' => 'premium',
            'max_outlets' => 10,
        ]);
    }
}
