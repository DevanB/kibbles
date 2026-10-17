<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;
use App\Models\JournalEntry;

final readonly class CreateJournalEntry
{
    public function handle(Game $game, string $body, ?string $playSessionId = null): JournalEntry
    {
        return $game->journalEntries()->create([
            'body' => $body,
            'play_session_id' => $playSessionId,
        ]);
    }
}
