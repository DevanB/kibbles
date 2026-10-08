<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FinishPlaySession;
use App\Http\Requests\FinishPlaySessionRequest;
use App\Models\Game;
use App\Models\PlaySession;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class FinishPlaySessionController
{
    public function __invoke(
        FinishPlaySessionRequest $request,
        Game $game,
        PlaySession $playSession,
        FinishPlaySession $action,
    ): RedirectResponse {
        $body = $request->body();
        $action->handle($playSession, $body);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Session saved.'),
        ]);

        return to_route('games.show', $body === null ? $game : [
            'game' => $game,
            'tab' => 'journal',
        ]);
    }
}
