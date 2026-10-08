<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePlaySession;
use App\Actions\DeletePlaySession;
use App\Actions\FinishPlaySession;
use App\Actions\StartPlaySession;
use App\Actions\UpdatePlaySession;
use App\Http\Requests\DeletePlaySessionRequest;
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
        StartPlaySession $start,
        CreatePlaySession $create,
    ): RedirectResponse {
        if ($request->isStart()) {
            $start->handle($game);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Session started.'),
            ]);

            return to_route('games.show', $game);
        }

        $create->handle($game, $request->startedAt(), $request->endedAt());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Session saved.'),
        ]);

        return to_route('games.show', $game);
    }

    public function edit(Game $game, PlaySession $playSession): Modal
    {
        if ($playSession->ended_at === null) {
            Gate::authorize('view', $playSession);

            return Inertia::modal('games/play-sessions/stop', [
                'game' => $game->toWire(),
                'playSession' => $playSession->toWire(),
            ])->baseRoute('games.show', $game);
        }

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
        FinishPlaySession $finish,
        UpdatePlaySession $update,
    ): RedirectResponse {
        if ($request->isFinish()) {
            $body = $request->body();
            $finish->handle($playSession, $body);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Session saved.'),
            ]);

            return to_route('games.show', $body === null ? $game : [
                'game' => $game,
                'tab' => 'journal',
            ]);
        }

        $update->handle($playSession, $request->startedAt(), $request->endedAt());

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
