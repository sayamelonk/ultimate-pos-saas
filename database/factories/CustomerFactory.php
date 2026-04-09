<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
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
            'code' => strtoupper(fake()->unique()->bothify('CUST-####')),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'birth_date' => fake()->optional()->dateTimeBetween('-60 years', '-18 years'),
            'gender' => fake()->optional()->randomElement(['male', 'female']),
            'membership_level' => Customer::LEVEL_REGULAR,
            'total_points' => 0,
            'total_spent' => 0,
            'total_visits' => 0,
            'joined_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'membership_expires_at' => null,
            'notes' => fake()->optional(0.2)->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function silver(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_level' => Customer::LEVEL_SILVER,
            'membership_expires_at' => fake()->dateTimeBetween('+1 month', '+1 year'),
        ]);
    }

    public function gold(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_level' => Customer::LEVEL_GOLD,
            'membership_expires_at' => fake()->dateTimeBetween('+1 month', '+1 year'),
        ]);
    }

    public function platinum(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_level' => Customer::LEVEL_PLATINUM,
            'membership_expires_at' => fake()->dateTimeBetween('+1 month', '+1 year'),
        ]);
    }

    public function withActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_points' => fake()->numberBetween(100, 5000),
            'total_spent' => fake()->numberBetween(100000, 10000000),
            'total_visits' => fake()->numberBetween(5, 100),
        ]);
    }

    public function withPoints(float $points): static
    {
        return $this->state(fn (array $attributes) => [
            'total_points' => $points,
        ]);
    }

    public function withSpent(float $spent): static
    {
        return $this->state(fn (array $attributes) => [
            'total_spent' => $spent,
        ]);
    }

    public function withVisits(int $visits): static
    {
        return $this->state(fn (array $attributes) => [
            'total_visits' => $visits,
        ]);
    }

    public function expiredMembership(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_level' => Customer::LEVEL_SILVER,
            'membership_expires_at' => now()->subDays(30),
        ]);
    }

    public function memberWithNoExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_level' => Customer::LEVEL_GOLD,
            'membership_expires_at' => null,
        ]);
    }

    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'male',
        ]);
    }

    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'female',
        ]);
    }
}
