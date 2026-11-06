<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Link;
use App\Models\Tag;

final readonly class DetachLinkTag
{
    public function handle(Link $link, Tag $tag): void
    {
        $link->tags()->detach($tag);
    }
}
