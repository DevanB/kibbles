<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\PlaySession;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final readonly class StartPlaySession
{
    public function handle(Game $game): PlaySession
    {
        try {
            return DB::transaction(function () use ($game): PlaySession {
                $session = $game->playSessions()->create([
                    'user_id' => $game->user_id,
                    'started_at' => now(),
                ]);

                if (in_array($game->status, [GameStatus::Backlog, GameStatus::Abandoned], true)) {
                    $game->update([
                        'status' => GameStatus::InProgress,
                    ]);
                }

                return $session;
            });
        } catch (UniqueConstraintViolationException) {
            $open = PlaySession::openFor($game->user);

            throw PlaySession::openConflict($open?->game->title ?? $game->title);
        }
    }
}
