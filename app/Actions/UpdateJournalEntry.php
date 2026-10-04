<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\JournalEntry;

final readonly class UpdateJournalEntry
{
    public function handle(JournalEntry $entry, string $body, ?string $next): JournalEntry
    {
        $entry->update([
            'body' => $body,
            'next' => $next,
        ]);

        return $entry;
    }
}
