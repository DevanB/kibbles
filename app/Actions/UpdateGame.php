<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Rules\UniqueOwnedGameTitle;
use App\Services\RawgClient;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class UpdateGame
{
    public function __construct(private RawgClient $rawg) {}

    public function handle(Game $game, string $title, GameStatus $status, ?int $rawgId = null): Game
    {
        $attributes = [
            'title' => $title,
            'status' => $status,
        ];

        if ($rawgId !== $game->rawg_id) {
            $detail = $rawgId === null ? null : $this->rawg->find($rawgId);
            $attributes['rawg_id'] = $rawgId;
            $attributes['image_url'] = $detail?->backgroundImage;
            $attributes['description'] = $detail?->description;
        }

        try {
            $game->update($attributes);
        } catch (UniqueConstraintViolationException) {
            throw UniqueOwnedGameTitle::conflict();
        }

        return $game;
    }
}
