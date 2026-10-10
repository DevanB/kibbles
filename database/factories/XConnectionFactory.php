<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\XConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<XConnection>
 */
final class XConnectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'x_user_id' => (string) fake()->unique()->numerify('##########'),
            'username' => fake()->unique()->userName(),
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->addHours(2),
            'last_synced_at' => null,
            'last_full_synced_at' => null,
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function synced(): self
    {
        return $this->state(fn (array $attributes): array => [
            'last_synced_at' => now()->subMinutes(10),
            'last_full_synced_at' => now()->subDay(),
        ]);
    }
}
