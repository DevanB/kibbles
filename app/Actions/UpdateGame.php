<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Rules\UniqueOwnedGameTitle;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class UpdateGame
{
    public function __construct(private ResolveRawgDetails $details) {}

    public function handle(Game $game, string $title, GameStatus $status, ?int $rawgId = null): Game
    {
        $attributes = [
            'title' => $title,
            'status' => $status,
        ];

        if ($rawgId !== $game->rawg_id) {
            $attributes['rawg_id'] = $rawgId;
            $attributes = [...$attributes, ...$this->details->handle($rawgId)];
        }

        try {
            $game->update($attributes);
        } catch (UniqueConstraintViolationException) {
            throw UniqueOwnedGameTitle::conflict();
        }

        return $game;
    }
}
