<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
final class GameFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->unique()->sentence(3),
            'status' => GameStatus::Backlog,
        ];
    }

    public function inProgress(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => GameStatus::InProgress,
        ]);
    }

    public function abandoned(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => GameStatus::Abandoned,
        ]);
    }

    public function finished(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => GameStatus::Finished,
        ]);
    }
}
