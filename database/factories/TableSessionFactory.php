<?php

namespace Database\Factories;

use App\Models\Table;
use App\Models\TableSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TableSession>
 */
class TableSessionFactory extends Factory
{
    protected $model = TableSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'table_id' => Table::factory(),
            'opened_by' => User::factory(),
            'closed_by' => null,
            'opened_at' => now(),
            'closed_at' => null,
            'guest_count' => fake()->numberBetween(1, 8),
            'notes' => fake()->optional(0.3)->sentence(),
            'status' => TableSession::STATUS_ACTIVE,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TableSession::STATUS_ACTIVE,
            'closed_at' => null,
            'closed_by' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TableSession::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => User::factory(),
        ]);
    }

    public function withGuests(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'guest_count' => $count,
        ]);
    }

    public function openedHoursAgo(int $hours): static
    {
        return $this->state(fn (array $attributes) => [
            'opened_at' => now()->subHours($hours),
        ]);
    }

    public function openedMinutesAgo(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'opened_at' => now()->subMinutes($minutes),
        ]);
    }

    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }

    public function closedAfterMinutes(int $minutes): static
    {
        $openedAt = now()->subMinutes($minutes);

        return $this->state(fn (array $attributes) => [
            'opened_at' => $openedAt,
            'closed_at' => now(),
            'closed_by' => User::factory(),
            'status' => TableSession::STATUS_CLOSED,
        ]);
    }
}
