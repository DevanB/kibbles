<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tag;
use App\Models\XBookmark;

final readonly class AttachBookmarkTag
{
    public function __construct(private CreateTag $createTag) {}

    public function handle(XBookmark $bookmark, Tag|string $tag): Tag
    {
        $resolved = $tag instanceof Tag
            ? $tag
            : $this->createTag->findOrCreate($bookmark->user, $tag);

        $bookmark->tags()->syncWithoutDetaching([$resolved->id]);

        return $resolved;
    }
}
