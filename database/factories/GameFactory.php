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
            'rawg_id' => null,
            'image_url' => null,
            'description' => null,
        ];
    }

    public function catalogLinked(
        int $rawgId = 274755,
        string $imageUrl = 'https://media.rawg.io/media/games/1f4/1f47a270b8f241e4676b14d39ec620f7.jpg',
        string $description = 'Defy the god of the dead as you hack and slash out of the Underworld.',
    ): self {
        return $this->state(fn (): array => [
            'rawg_id' => $rawgId,
            'image_url' => $imageUrl,
            'description' => $description,
        ]);
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
