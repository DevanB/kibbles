<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\JournalEntry;

final readonly class DeleteJournalEntry
{
    public function handle(JournalEntry $entry): void
    {
        $entry->delete();
    }
}
