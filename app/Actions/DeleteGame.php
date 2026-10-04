<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;

final readonly class DeleteGame
{
    public function handle(Game $game): void
    {
        $game->delete();
    }
}
