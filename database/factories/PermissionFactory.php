<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $module = fake()->randomElement([
            'dashboard', 'pos', 'orders', 'products', 'categories',
            'inventory', 'tables', 'kitchen', 'reports', 'outlets',
            'users', 'roles', 'settings',
        ]);

        $action = fake()->randomElement(['View', 'Create', 'Update', 'Delete', 'Manage']);

        return [
            'name' => "{$action} {$module}",
            'slug' => strtolower(str_replace(' ', '-', "{$action}-{$module}")) . '-' . fake()->unique()->randomNumber(4),
            'module' => $module,
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Create a permission for specific module.
     */
    public function forModule(string $module): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => $module,
        ]);
    }
}
