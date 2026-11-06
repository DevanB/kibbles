<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Link;

final readonly class DeleteLink
{
    public function handle(Link $link): void
    {
        $link->delete();
    }
}
