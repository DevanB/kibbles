<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;
use App\Models\JournalEntry;

final readonly class RecallJournalEntries
{
    /**
     * @return array{
     *     journalEntries: list<array{id: string, body: string, next: string|null, createdAt: string, updatedAt: string}>,
     *     resume: string|null
     * }
     */
    public function handle(Game $game): array
    {
        $entries = $game->journalEntries()
            ->newestFirst()
            ->get();

        return [
            'journalEntries' => $entries
                ->map($this->toWire(...))
                ->values()
                ->all(),
            'resume' => $entries->first(
                fn (JournalEntry $entry): bool => $entry->next !== null,
            )?->next,
        ];
    }

    /**
     * @return array{id: string, body: string, next: string|null, createdAt: string, updatedAt: string}
     */
    private function toWire(JournalEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'body' => $entry->body,
            'next' => $entry->next,
            'createdAt' => $entry->created_at->toIso8601String(),
            'updatedAt' => $entry->updated_at->toIso8601String(),
        ];
    }
}
