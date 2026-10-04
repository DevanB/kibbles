<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;
use App\Rules\UniqueOwnedGameTitle;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class UpdateGame
{
    public function handle(Game $game, string $title): Game
    {
        try {
            $game->update([
                'title' => $title,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw UniqueOwnedGameTitle::conflict();
        }

        return $game;
    }
}
