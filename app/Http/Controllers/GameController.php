<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateGame;
use App\Actions\DeleteGame;
use App\Actions\ListJournalEntries;
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
use InertiaUI\Modal\Modal;

final readonly class GameController
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Game::class);

        return Inertia::render('games/index', [
            'games' => $user->games()
                ->latest()
                ->get()
                ->map($this->toWireGame(...))
                ->values()
                ->all(),
        ]);
    }

    public function create(): Modal
    {
        Gate::authorize('create', Game::class);

        return Inertia::modal('games/create')
            ->baseRoute('games.index');
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

        return to_route('games.show', $game);
    }

    public function show(Game $game, ListJournalEntries $list): Response
    {
        Gate::authorize('view', $game);

        return Inertia::render('games/show', [
            'game' => $this->toWireGame($game),
            'journalEntries' => $list->handle($game),
        ]);
    }

    public function edit(Game $game): Modal
    {
        Gate::authorize('update', $game);

        return Inertia::modal('games/edit', [
            'game' => $this->toWireGame($game),
        ])->baseRoute('games.show', $game);
    }

    public function update(UpdateGameRequest $request, Game $game, UpdateGame $action): RedirectResponse
    {
        $action->handle($game, $request->string('title')->value());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Game updated.'),
        ]);

        return to_route('games.show', $game);
    }

    public function destroy(DeleteGameRequest $request, Game $game, DeleteGame $action): RedirectResponse
    {
        $action->handle($game);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Game deleted.'),
        ]);

        return to_route('games.index');
    }

    /**
     * @return array{id: string, title: string}
     */
    private function toWireGame(Game $game): array
    {
        return [
            'id' => $game->id,
            'title' => $game->title,
        ];
    }
}
