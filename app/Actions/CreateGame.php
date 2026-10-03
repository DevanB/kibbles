<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;
use App\Models\User;

final readonly class CreateGame
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, array $attributes): Game
    {
        return $user->games()->create($attributes);
    }
}
