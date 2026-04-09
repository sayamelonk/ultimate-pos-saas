<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\VariantGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VariantGroup>
 */
class VariantGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Size',
            'Color',
            'Temperature',
            'Sugar Level',
            'Ice Level',
            'Spice Level',
        ]);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'display_name' => $name,
            'description' => fake()->sentence(),
            'display_type' => VariantGroup::DISPLAY_BUTTON,
            'is_required' => true,
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

    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => false,
        ]);
    }

    public function displayDropdown(): static
    {
        return $this->state(fn (array $attributes) => [
            'display_type' => VariantGroup::DISPLAY_DROPDOWN,
        ]);
    }

    public function displayColor(): static
    {
        return $this->state(fn (array $attributes) => [
            'display_type' => VariantGroup::DISPLAY_COLOR,
        ]);
    }

    public function displayImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'display_type' => VariantGroup::DISPLAY_IMAGE,
        ]);
    }
}
