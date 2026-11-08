<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tag;
use App\Models\XBookmark;
use Illuminate\Support\Facades\DB;

final readonly class AttachBookmarkTag
{
    public function __construct(
        private CreateTag $createTag,
        private ReviewXBookmark $review,
    ) {}

    public function handle(XBookmark $bookmark, Tag|string $tag): Tag
    {
        return DB::transaction(function () use ($bookmark, $tag): Tag {
            $resolved = $tag instanceof Tag
                ? $tag
                : $this->createTag->findOrCreate($bookmark->user, $tag);

            $bookmark->tags()->syncWithoutDetaching([$resolved->id]);
            $this->review->handle($bookmark);

            return $resolved;
        });
    }
}
