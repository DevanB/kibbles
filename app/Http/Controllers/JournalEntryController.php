<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateJournalEntry;
use App\Actions\DeleteJournalEntry;
use App\Actions\UpdateJournalEntry;
use App\Http\Requests\CreateJournalEntryRequest;
use App\Http\Requests\DeleteJournalEntryRequest;
use App\Http\Requests\UpdateJournalEntryRequest;
use App\Models\Game;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class JournalEntryController
{
    public function store(
        CreateJournalEntryRequest $request,
        Game $game,
        CreateJournalEntry $action,
    ): RedirectResponse {
        $action->handle(
            $game,
            $request->string('body')->value(),
            $this->validatedNext($request),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Journal entry added.'),
        ]);

        return to_route('games.show', $game);
    }

    public function update(
        UpdateJournalEntryRequest $request,
        Game $game,
        JournalEntry $journalEntry,
        UpdateJournalEntry $action,
    ): RedirectResponse {
        $action->handle(
            $journalEntry,
            $request->string('body')->value(),
            $this->validatedNext($request),
        );

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

    private function validatedNext(CreateJournalEntryRequest|UpdateJournalEntryRequest $request): ?string
    {
        $next = $request->validated('next');

        return is_string($next) ? $next : null;
    }
}
