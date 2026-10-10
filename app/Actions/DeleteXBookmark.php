<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\XBookmark;
use App\Models\XConnection;
use App\Services\XClient;

final readonly class DeleteXBookmark
{
    public function __construct(private XClient $x) {}

    public function handle(XBookmark $bookmark): bool
    {
        $connection = $bookmark->user->xConnection;

        if ($connection instanceof XConnection && ! $this->x->deleteBookmark($connection, $bookmark->x_post_id)) {
            return false;
        }

        $bookmark->delete();

        return true;
    }
}
