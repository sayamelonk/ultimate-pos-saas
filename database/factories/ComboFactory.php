<?php

namespace Database\Factories;

use App\Models\Combo;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Combo>
 */
class ComboFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory()->combo(),
            'pricing_type' => Combo::PRICING_FIXED,
            'discount_value' => 0,
            'allow_substitutions' => false,
            'min_items' => 1,
            'max_items' => null,
        ];
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => Combo::PRICING_FIXED,
            'discount_value' => 0,
        ]);
    }

    public function sumPricing(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => Combo::PRICING_SUM,
            'discount_value' => 0,
        ]);
    }

    public function discountPercent(float $percent = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => Combo::PRICING_DISCOUNT_PERCENT,
            'discount_value' => $percent,
        ]);
    }

    public function discountAmount(float $amount = 10000): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => Combo::PRICING_DISCOUNT_AMOUNT,
            'discount_value' => $amount,
        ]);
    }

    public function allowSubstitutions(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_substitutions' => true,
        ]);
    }

    public function withItemLimits(int $min, int $max): static
    {
        return $this->state(fn (array $attributes) => [
            'min_items' => $min,
            'max_items' => $max,
        ]);
    }
}
