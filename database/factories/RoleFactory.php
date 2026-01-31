<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
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
        $name = fake()->jobTitle();

        return [
            'tenant_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_system' => false,
        ];
    }

    /**
     * Indicate that the role belongs to a specific tenant.
     */
    public function forTenant(mixed $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Indicate that the role is a system role.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'tenant_id' => null,
        ]);
    }

    /**
     * Create a tenant-owner role.
     */
    public function tenantOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Tenant Owner',
            'slug' => 'tenant-owner',
            'description' => 'Owner of a tenant with full access',
            'is_system' => true,
            'tenant_id' => null,
        ]);
    }

    /**
     * Create a super-admin role.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Super administrator with access to all tenants',
            'is_system' => true,
            'tenant_id' => null,
        ]);
    }
}
