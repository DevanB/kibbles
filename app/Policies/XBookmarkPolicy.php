<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\XBookmark;

final class XBookmarkPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, XBookmark $bookmark): bool
    {
        return $bookmark->user()->is($user);
    }

    public function delete(User $user, XBookmark $bookmark): bool
    {
        return $bookmark->user()->is($user);
    }
}
