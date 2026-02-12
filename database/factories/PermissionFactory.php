<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $modules = ['dashboard', 'pos', 'orders', 'products', 'inventory', 'reports', 'settings'];
        $actions = ['view', 'create', 'update', 'delete', 'manage'];

        $module = fake()->randomElement($modules);
        $action = fake()->randomElement($actions);
        $name = ucfirst($action).' '.ucfirst($module);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'module' => $module,
            'description' => "Permission to {$action} {$module}",
        ];
    }

    public function forModule(string $module): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => $module,
        ]);
    }
}
