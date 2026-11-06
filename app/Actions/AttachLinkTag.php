<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Link;
use App\Models\Tag;

final readonly class AttachLinkTag
{
    public function __construct(private CreateTag $createTag) {}

    public function handle(Link $link, Tag|string $tag): Tag
    {
        $resolved = $tag instanceof Tag
            ? $tag
            : $this->createTag->findOrCreate($link->user, $tag);

        $link->tags()->syncWithoutDetaching([$resolved->id]);

        return $resolved;
    }
}
