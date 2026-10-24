<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

final class TagPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, Tag $tag): bool
    {
        return $tag->user()->is($user);
    }

    public function create(): bool
    {
        return true;
    }

    public function update(User $user, Tag $tag): bool
    {
        return $tag->user()->is($user);
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $tag->user()->is($user);
    }
}
