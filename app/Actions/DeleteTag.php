<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tag;

final readonly class DeleteTag
{
    public function handle(Tag $tag): void
    {
        $tag->delete();
    }
}
