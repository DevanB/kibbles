<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateGame;
use App\Actions\DeleteGame;
use App\Actions\UpdateGame;
use App\Http\Requests\CreateGameRequest;
use App\Http\Requests\DeleteGameRequest;
use App\Http\Requests\UpdateGameRequest;
use App\Models\Game;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class GameController
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Game::class);

        return Inertia::render('games/index', [
            'games' => $user->games()->latest()->get(['id', 'title']),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Game::class);

        return Inertia::render('games/create');
    }

    public function store(
        CreateGameRequest $request,
        #[CurrentUser] User $owner,
        CreateGame $action,
    ): RedirectResponse {
        $game = $action->handle($owner, $request->string('title')->value());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Game created.'),
        ]);

        return to_route('games.edit', $game);
    }

    public function edit(Game $game): Response
    {
        Gate::authorize('view', $game);

        return Inertia::render('games/edit', [
            'game' => [
                'id' => $game->id,
                'title' => $game->title,
            ],
        ]);
    }

    public function update(UpdateGameRequest $request, Game $game, UpdateGame $action): RedirectResponse
    {
        $action->handle($game, $request->string('title')->value());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Game updated.'),
        ]);

        return to_route('games.edit', $game);
    }

    public function destroy(DeleteGameRequest $request, Game $game, DeleteGame $action): RedirectResponse
    {
        $request->validated();

        $action->handle($game);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Game deleted.'),
        ]);

        return to_route('games.index');
    }
}
