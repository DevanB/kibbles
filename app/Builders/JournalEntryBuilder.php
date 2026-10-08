<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<JournalEntry>
 */
final class JournalEntryBuilder extends Builder
{
    public function newestFirst(): static
    {
        return $this->latest()->orderByDesc('id');
    }
}
