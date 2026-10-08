<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\PlaySession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlaySession>
 */
final class PlaySessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => fn (array $attributes): string => Game::query()
                ->whereKey($attributes['game_id'])
                ->firstOrFail()
                ->user_id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
        ];
    }

    public function open(): self
    {
        return $this->state(fn (): array => [
            'ended_at' => null,
            'started_at' => now()->subHour(),
        ]);
    }
}
