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
    public function __construct(private ResolveRawgDetails $details) {}

    public function handle(User $user, string $title, ?int $rawgId = null): Game
    {
        try {
            return $user->games()->create([
                'title' => $title,
                'status' => GameStatus::Backlog,
                'rawg_id' => $rawgId,
                ...$this->details->handle($rawgId),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw UniqueOwnedGameTitle::conflict();
        }
    }
}
