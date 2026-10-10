<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\XConnection;

final class XConnectionPolicy
{
    public function create(): bool
    {
        return true;
    }

    public function update(User $user, XConnection $connection): bool
    {
        return $connection->user()->is($user);
    }

    public function delete(User $user, XConnection $connection): bool
    {
        return $connection->user()->is($user);
    }
}
