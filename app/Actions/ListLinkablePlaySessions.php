<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\PlaySession;

final readonly class ListLinkablePlaySessions
{
    /**
     * @return array<int, array{id: string, startedAt: string, endedAt: string|null, durationMinutes: int|null, journalEntryId: string|null}>
     */
    public function handle(Game $game, ?JournalEntry $current = null): array
    {
        return $game->playSessions()
            ->linkableFor($current)
            ->newestFirst()
            ->with('journalEntry')
            ->get()
            ->map(fn (PlaySession $session): array => $session->toWire())
            ->values()
            ->all();
    }
}
