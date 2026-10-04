<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Str;

final readonly class ListRecentJournalEntries
{
    /**
     * @return array<int, array{id: string, preview: string, createdAt: string, game: array{id: string, title: string}}>
     */
    public function handle(User $user, int $limit = 8): array
    {
        return JournalEntry::query()
            ->whereIn('game_id', $user->games()->select('id'))
            ->with('game')
            ->newestFirst()
            ->limit($limit)
            ->get()
            ->map($this->toWire(...))
            ->values()
            ->all();
    }

    /**
     * @return array{id: string, preview: string, createdAt: string, game: array{id: string, title: string}}
     */
    public function toWire(JournalEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'preview' => Str::limit($entry->body, 80),
            'createdAt' => $entry->created_at->toIso8601String(),
            'game' => [
                'id' => $entry->game->id,
                'title' => $entry->game->title,
            ],
        ];
    }
}
