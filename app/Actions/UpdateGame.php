<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;

final readonly class UpdateGame
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Game $game, array $attributes): void
    {
        $game->update($attributes);
    }
}
