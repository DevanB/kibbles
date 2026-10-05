<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateJournalEntry;
use App\Actions\DeleteJournalEntry;
use App\Actions\ListJournalEntries;
use App\Actions\UpdateJournalEntry;
use App\Http\Requests\CreateJournalEntryRequest;
use App\Http\Requests\DeleteJournalEntryRequest;
use App\Http\Requests\ShowJournalEntryRequest;
use App\Http\Requests\UpdateJournalEntryRequest;
use App\Models\Game;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use InertiaUI\Modal\Modal;

final readonly class JournalEntryController
{
    public function create(Game $game): Modal
    {
        Gate::authorize('create', [JournalEntry::class, $game]);

        return Inertia::modal('games/journal-entries/create', [
            'game' => $this->toWireGame($game),
        ])->baseRoute('games.show', $game);
    }

    public function store(
        CreateJournalEntryRequest $request,
        Game $game,
        CreateJournalEntry $action,
    ): RedirectResponse {
        $action->handle($game, $request->string('body')->value());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Journal entry added.'),
        ]);

        return to_route('games.show', $game);
    }

    public function show(
        ShowJournalEntryRequest $request,
        Game $game,
        JournalEntry $journalEntry,
        ListJournalEntries $list,
    ): Modal {
        return Inertia::modal('games/journal-entries/show', [
            'game' => $this->toWireGame($game),
            'journalEntry' => $list->toWire($journalEntry),
        ])->baseRoute('games.show', $game);
    }

    public function edit(Game $game, JournalEntry $journalEntry, ListJournalEntries $list): Modal
    {
        Gate::authorize('update', $journalEntry);

        return Inertia::modal('games/journal-entries/edit', [
            'game' => $this->toWireGame($game),
            'journalEntry' => $list->toWire($journalEntry),
        ])->baseRoute('games.show', $game);
    }

    public function update(
        UpdateJournalEntryRequest $request,
        Game $game,
        JournalEntry $journalEntry,
        UpdateJournalEntry $action,
    ): RedirectResponse {
        $action->handle($journalEntry, $request->string('body')->value());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Journal entry updated.'),
        ]);

        return to_route('games.show', $game);
    }

    public function destroy(
        DeleteJournalEntryRequest $request,
        Game $game,
        JournalEntry $journalEntry,
        DeleteJournalEntry $action,
    ): RedirectResponse {
        $action->handle($journalEntry);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Journal entry deleted.'),
        ]);

        return to_route('games.show', $game);
    }

    /**
     * @return array{id: string, title: string, status: string, statusLabel: string}
     */
    private function toWireGame(Game $game): array
    {
        return [
            'id' => $game->id,
            'title' => $game->title,
            'status' => $game->status->value,
            'statusLabel' => $game->status->label(),
        ];
    }
}
