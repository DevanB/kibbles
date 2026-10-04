<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\User;

final class JournalEntryPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, JournalEntry $entry): bool
    {
        return $entry->game->user()->is($user);
    }

    public function create(User $user, Game $game): bool
    {
        return $game->user()->is($user);
    }

    public function update(User $user, JournalEntry $entry): bool
    {
        return $entry->game->user()->is($user);
    }

    public function delete(User $user, JournalEntry $entry): bool
    {
        return $entry->game->user()->is($user);
    }
}
