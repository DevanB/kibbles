<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StartPlaySession;
use App\Http\Requests\StartPlaySessionRequest;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class StartPlaySessionController
{
    public function __invoke(StartPlaySessionRequest $request, Game $game, StartPlaySession $action): RedirectResponse
    {
        $action->handle($game);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Session started.'),
        ]);

        return to_route('games.show', $game);
    }
}
