<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\SyncXBookmarks;
use App\Models\User;

final readonly class CreateBookmarkSync
{
    public function handle(User $user, bool $full = false): void
    {
        SyncXBookmarks::dispatch($user->id, $full);
    }
}
