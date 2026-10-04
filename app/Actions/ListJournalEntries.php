<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;
use App\Models\JournalEntry;

final readonly class ListJournalEntries
{
    /**
     * @return array<int, array{id: string, body: string, createdAt: string, updatedAt: string}>
     */
    public function handle(Game $game): array
    {
        return $game->journalEntries()
            ->newestFirst()
            ->get()
            ->map($this->toWire(...))
            ->values()
            ->all();
    }

    /**
     * @return array{id: string, body: string, createdAt: string, updatedAt: string}
     */
    public function toWire(JournalEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'body' => $entry->body,
            'createdAt' => $entry->created_at->toIso8601String(),
            'updatedAt' => $entry->updated_at->toIso8601String(),
        ];
    }
}
