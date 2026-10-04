<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;
use App\Models\User;

final readonly class CreateGame
{
    public function handle(User $user, string $title): Game
    {
        return $user->games()->create([
            'title' => $title,
        ]);
    }
}
