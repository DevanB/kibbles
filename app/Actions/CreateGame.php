<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use App\Rules\UniqueOwnedGameTitle;
use App\Services\RawgClient;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class CreateGame
{
    public function __construct(private RawgClient $rawg) {}

    public function handle(User $user, string $title, ?int $rawgId = null): Game
    {
        $detail = $rawgId === null ? null : $this->rawg->find($rawgId);

        try {
            return $user->games()->create([
                'title' => $title,
                'status' => GameStatus::Backlog,
                'rawg_id' => $rawgId,
                'image_url' => $detail?->backgroundImage,
                'description' => $detail?->description,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw UniqueOwnedGameTitle::conflict();
        }
    }
}
