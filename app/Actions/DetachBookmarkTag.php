<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tag;
use App\Models\XBookmark;

final readonly class DetachBookmarkTag
{
    public function handle(XBookmark $bookmark, Tag $tag): void
    {
        $bookmark->tags()->detach($tag);
    }
}
