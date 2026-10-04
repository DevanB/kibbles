<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
final class JournalEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'body' => fake()->paragraph(),
            'next' => fake()->optional()->sentence(),
        ];
    }
}
