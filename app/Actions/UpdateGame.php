<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;

final readonly class UpdateGame
{
    public function handle(Game $game, string $title): Game
    {
        $game->update([
            'title' => $title,
        ]);

        return $game;
    }
}
