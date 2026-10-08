<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StopPlaySessionRequest;
use App\Models\Game;
use App\Models\PlaySession;
use Inertia\Inertia;
use InertiaUI\Modal\Modal;

final readonly class StopPlaySessionController
{
    public function __invoke(StopPlaySessionRequest $request, Game $game, PlaySession $playSession): Modal
    {
        return Inertia::modal('games/play-sessions/stop', [
            'game' => $game->toWire(),
            'playSession' => $playSession->toWire(),
        ])->baseRoute('games.show', $game);
    }
}
