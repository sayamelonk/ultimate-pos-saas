<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'tenant_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_system' => false,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);
    }

    public function tenantOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Tenant Owner',
            'slug' => 'tenant-owner',
            'is_system' => true,
        ]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Manager',
            'slug' => 'manager',
            'is_system' => false,
        ]);
    }

    public function outletManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Outlet Manager',
            'slug' => 'outlet-manager',
            'is_system' => false,
        ]);
    }

    public function supervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Supervisor',
            'slug' => 'supervisor',
            'is_system' => false,
        ]);
    }

    public function cashier(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cashier',
            'slug' => 'cashier',
            'is_system' => false,
        ]);
    }

    public function waiter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Waiter',
            'slug' => 'waiter',
            'is_system' => false,
        ]);
    }

    public function withSlug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }
}
