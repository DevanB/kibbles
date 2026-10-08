<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePlaySession;
use App\Actions\DeletePlaySession;
use App\Actions\FinishPlaySession;
use App\Actions\StartPlaySession;
use App\Actions\UpdatePlaySession;
use App\Http\Requests\DeletePlaySessionRequest;
use App\Http\Requests\FinishPlaySessionRequest;
use App\Http\Requests\StartPlaySessionRequest;
use App\Http\Requests\StopPlaySessionRequest;
use App\Http\Requests\StorePlaySessionRequest;
use App\Http\Requests\UpdatePlaySessionRequest;
use App\Models\Game;
use App\Models\PlaySession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use InertiaUI\Modal\Modal;

final readonly class PlaySessionController
{
    public function start(StartPlaySessionRequest $request, Game $game, StartPlaySession $action): RedirectResponse
    {
        $action->handle($game);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Session started.'),
        ]);

        return to_route('games.show', $game);
    }

    public function stop(StopPlaySessionRequest $request, Game $game, PlaySession $playSession): Modal
    {
        return Inertia::modal('games/play-sessions/stop', [
            'game' => $game->toWire(),
            'playSession' => $playSession->toWire(),
        ])->baseRoute('games.show', $game);
    }

    public function finish(
        FinishPlaySessionRequest $request,
        Game $game,
        PlaySession $playSession,
        FinishPlaySession $action,
    ): RedirectResponse {
        $action->handle($playSession, $request->body());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Session saved.'),
        ]);

        return to_route('games.show', $game);
    }

    public function create(Game $game): Modal
    {
        Gate::authorize('create', [PlaySession::class, $game]);

        return Inertia::modal('games/play-sessions/create', [
            'game' => $game->toWire(),
        ])->baseRoute('games.show', $game);
    }

    public function store(
        StorePlaySessionRequest $request,
        Game $game,
        CreatePlaySession $action,
    ): RedirectResponse {
        $action->handle($game, $request->startedAt(), $request->endedAt());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Session saved.'),
        ]);

        return to_route('games.show', $game);
    }

    public function edit(Game $game, PlaySession $playSession): Modal
    {
        Gate::authorize('update', $playSession);

        return Inertia::modal('games/play-sessions/edit', [
            'game' => $game->toWire(),
            'playSession' => $playSession->toWire(),
        ])->baseRoute('games.show', $game);
    }

    public function update(
        UpdatePlaySessionRequest $request,
        Game $game,
        PlaySession $playSession,
        UpdatePlaySession $action,
    ): RedirectResponse {
        $action->handle($playSession, $request->startedAt(), $request->endedAt());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Session updated.'),
        ]);

        return to_route('games.show', $game);
    }

    public function destroy(
        DeletePlaySessionRequest $request,
        Game $game,
        PlaySession $playSession,
        DeletePlaySession $action,
    ): RedirectResponse {
        $action->handle($playSession);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Session deleted.'),
        ]);

        return to_route('games.show', $game);
    }
}
