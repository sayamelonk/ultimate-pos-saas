<?php

namespace Database\Factories;

use App\Models\VariantGroup;
use App\Models\VariantOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VariantOption>
 */
class VariantOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Small',
            'Medium',
            'Large',
            'Extra Large',
            'Hot',
            'Cold',
            'Iced',
            'Regular',
            'Less Sugar',
            'Extra Sugar',
            'No Ice',
            'Less Ice',
            'Mild',
            'Spicy',
            'Extra Spicy',
        ]);

        return [
            'variant_group_id' => VariantGroup::factory(),
            'name' => $name,
            'display_name' => $name,
            'value' => strtolower(str_replace(' ', '_', $name)),
            'price_adjustment' => fake()->randomElement([0, 0, 0, 2000, 3000, 5000]),
            'is_default' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function withPriceAdjustment(float $adjustment): static
    {
        return $this->state(fn (array $attributes) => [
            'price_adjustment' => $adjustment,
        ]);
    }

    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'display_name' => $name,
            'value' => strtolower(str_replace(' ', '_', $name)),
        ]);
    }
}
