<?php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([
            Discount::TYPE_PERCENTAGE,
            Discount::TYPE_FIXED_AMOUNT,
        ]);

        $value = $type === Discount::TYPE_PERCENTAGE
            ? fake()->randomFloat(2, 5, 30)
            : fake()->randomFloat(2, 5000, 50000);

        return [
            'tenant_id' => Tenant::factory(),
            'code' => strtoupper(fake()->unique()->lexify('DISC????')),
            'name' => fake()->randomElement(['Member Discount', 'Promo Akhir Tahun', 'Happy Hour', 'Flash Sale', 'Birthday Discount']),
            'description' => fake()->optional()->sentence(),
            'type' => $type,
            'scope' => fake()->randomElement([Discount::SCOPE_ORDER, Discount::SCOPE_ITEM]),
            'value' => $value,
            'max_discount' => $type === Discount::TYPE_PERCENTAGE ? fake()->optional()->randomFloat(2, 50000, 100000) : null,
            'min_purchase' => fake()->optional()->randomFloat(2, 50000, 200000),
            'min_qty' => null,
            'member_only' => false,
            'membership_levels' => null,
            'applicable_outlets' => null,
            'applicable_items' => null,
            'valid_from' => now()->subDays(7),
            'valid_until' => now()->addDays(30),
            'usage_limit' => null,
            'usage_count' => 0,
            'is_auto_apply' => false,
            'is_active' => true,
        ];
    }

    public function percentage(float $percent = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => $percent,
            'name' => "Diskon {$percent}%",
        ]);
    }

    public function fixedAmount(float $amount = 10000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Discount::TYPE_FIXED_AMOUNT,
            'value' => $amount,
            'name' => 'Potongan Harga',
        ]);
    }

    public function orderLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => Discount::SCOPE_ORDER,
        ]);
    }

    public function itemLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => Discount::SCOPE_ITEM,
        ]);
    }

    public function memberOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'member_only' => true,
        ]);
    }

    public function autoApply(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_auto_apply' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->subDays(30),
            'valid_until' => now()->subDays(1),
        ]);
    }

    public function notYetValid(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->addDays(1),
            'valid_until' => now()->addDays(30),
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => null,
            'valid_from' => null,
            'valid_until' => null,
        ]);
    }

    public function withUsageLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => $limit,
            'usage_count' => 0,
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => 10,
            'usage_count' => 10,
        ]);
    }

    public function withMinPurchase(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'min_purchase' => $amount,
        ]);
    }

    public function withMaxDiscount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'max_discount' => $amount,
        ]);
    }
}
