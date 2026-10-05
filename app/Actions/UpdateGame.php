<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Rules\UniqueOwnedGameTitle;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class UpdateGame
{
    public function handle(Game $game, string $title, GameStatus $status): Game
    {
        try {
            $game->update([
                'title' => $title,
                'status' => $status,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw UniqueOwnedGameTitle::conflict();
        }

        return $game;
    }
}
