<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\PlaySession;
use App\Models\User;

final class PlaySessionPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, PlaySession $playSession): bool
    {
        return $playSession->game->user()->is($user);
    }

    public function create(User $user, Game $game): bool
    {
        return $game->user()->is($user);
    }

    public function update(User $user, PlaySession $playSession): bool
    {
        return $playSession->game->user()->is($user);
    }

    public function delete(User $user, PlaySession $playSession): bool
    {
        return $playSession->game->user()->is($user);
    }
}
