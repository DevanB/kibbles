<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\User;

final class JournalEntryPolicy
{
    public function create(User $user, Game $game): bool
    {
        return $user->can('update', $game);
    }

    public function update(User $user, JournalEntry $entry): bool
    {
        return $user->can('update', $entry->game);
    }

    public function delete(User $user, JournalEntry $entry): bool
    {
        return $user->can('delete', $entry->game);
    }
}
