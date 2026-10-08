<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;
use App\Models\JournalEntry;

final readonly class ListJournalEntries
{
    /**
     * @return array<int, array{id: string, body: string, createdAt: string, updatedAt: string, playSessionId: string|null}>
     */
    public function handle(Game $game): array
    {
        return $game->journalEntries()
            ->newestFirst()
            ->get()
            ->map(fn (JournalEntry $entry): array => $entry->toWire())
            ->values()
            ->all();
    }
}
