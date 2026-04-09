<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
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
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
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

    public function withSlug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }

    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    public function viewProducts(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'View Products',
            'slug' => 'view-products',
            'module' => 'products',
            'description' => 'Permission to view products',
        ]);
    }

    public function manageProducts(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Manage Products',
            'slug' => 'manage-products',
            'module' => 'products',
            'description' => 'Permission to manage products',
        ]);
    }

    public function accessPos(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Access POS',
            'slug' => 'access-pos',
            'module' => 'pos',
            'description' => 'Permission to access POS',
        ]);
    }

    public function viewReports(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'View Reports',
            'slug' => 'view-reports',
            'module' => 'reports',
            'description' => 'Permission to view reports',
        ]);
    }

    public function manageUsers(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Manage Users',
            'slug' => 'manage-users',
            'module' => 'users',
            'description' => 'Permission to manage users',
        ]);
    }

    public function manageSettings(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Manage Settings',
            'slug' => 'manage-settings',
            'module' => 'settings',
            'description' => 'Permission to manage settings',
        ]);
    }
}
