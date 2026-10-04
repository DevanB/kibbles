<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateGame;
use App\Actions\DeleteGame;
use App\Actions\UpdateGame;
use App\Http\Requests\StoreGameRequest;
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
            'games' => $user->games()->latest()->get(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Game::class);

        return Inertia::render('games/create');
    }

    public function store(StoreGameRequest $request, #[CurrentUser] User $user, CreateGame $action): RedirectResponse
    {
        $action->handle($user, $request->string('title')->value());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Game created.'),
        ]);

        return to_route('games.index');
    }

    public function show(Game $game): RedirectResponse
    {
        Gate::authorize('view', $game);

        return to_route('games.edit', $game);
    }

    public function edit(Game $game): Response
    {
        Gate::authorize('update', $game);

        return Inertia::render('games/edit', [
            'game' => $game,
        ]);
    }

    public function update(UpdateGameRequest $request, Game $game, UpdateGame $action): RedirectResponse
    {
        $action->handle($game, $request->string('title')->value());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Game updated.'),
        ]);

        return to_route('games.index');
    }

    public function destroy(Game $game, DeleteGame $action): RedirectResponse
    {
        Gate::authorize('delete', $game);

        $action->handle($game);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Game deleted.'),
        ]);

        return to_route('games.index');
    }
}
