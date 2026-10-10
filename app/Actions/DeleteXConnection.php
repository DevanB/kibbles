<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\XConnection;

final readonly class DeleteXConnection
{
    public function handle(XConnection $connection): void
    {
        $connection->user->xBookmarks()->delete();
        $connection->delete();
    }
}
