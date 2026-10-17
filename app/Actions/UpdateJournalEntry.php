<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\JournalEntry;

final readonly class UpdateJournalEntry
{
    public function handle(JournalEntry $entry, string $body, ?string $playSessionId = null): JournalEntry
    {
        $entry->update([
            'body' => $body,
            'play_session_id' => $playSessionId,
        ]);

        return $entry;
    }
}
