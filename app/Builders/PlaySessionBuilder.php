<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\JournalEntry;
use App\Models\PlaySession;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<PlaySession>
 */
final class PlaySessionBuilder extends Builder
{
    public function open(): static
    {
        return $this->whereNull('ended_at');
    }

    public function closed(): static
    {
        return $this->whereNotNull('ended_at');
    }

    public function newestFirst(): static
    {
        return $this->latest('started_at')->orderByDesc('id');
    }

    public function linkableFor(?JournalEntry $current = null): static
    {
        return $this->where(function (Builder $query) use ($current): void {
            $query->whereDoesntHave('journalEntry');

            if (is_string($current?->play_session_id)) {
                $query->orWhereKey($current->play_session_id);
            }
        });
    }
}
