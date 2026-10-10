<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\SyncXBookmarks;
use App\Models\User;

final readonly class CreateBookmarkSync
{
    public function handle(User $user, bool $full = false): void
    {
        dispatch(new SyncXBookmarks($user->id, $full));
    }
}
