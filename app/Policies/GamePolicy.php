<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

final class GamePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Game $game): bool
    {
        return $game->user()->is($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Game $game): bool
    {
        return $game->user()->is($user);
    }

    public function delete(User $user, Game $game): bool
    {
        return $game->user()->is($user);
    }
}
