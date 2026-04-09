<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<UserPin>
 */
class UserPinFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
            'last_used_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withPin(string $pin): static
    {
        return $this->state(fn (array $attributes) => [
            'pin_hash' => Hash::make($pin),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => now(),
        ]);
    }

    public function usedAt(\DateTimeInterface $dateTime): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => $dateTime,
        ]);
    }
}
