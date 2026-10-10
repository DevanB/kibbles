<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\SyncXBookmarks;
use App\Models\XConnection;

final readonly class DispatchXBookmarkSyncs
{
    public function handle(bool $full = false): void
    {
        XConnection::query()->each(
            fn (XConnection $connection): mixed => dispatch(new SyncXBookmarks($connection->user_id, $full)),
        );
    }
}
