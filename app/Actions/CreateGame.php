<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use App\Rules\UniqueOwnedGameTitle;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class CreateGame
{
    public function handle(User $user, string $title): Game
    {
        try {
            return $user->games()->create([
                'title' => $title,
                'status' => GameStatus::Backlog,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw UniqueOwnedGameTitle::conflict();
        }
    }
}
