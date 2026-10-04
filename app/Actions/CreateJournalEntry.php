<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;
use App\Models\JournalEntry;

final readonly class CreateJournalEntry
{
    public function handle(Game $game, string $body, ?string $next): JournalEntry
    {
        return $game->journalEntries()->create([
            'body' => $body,
            'next' => $next,
        ]);
    }
}
