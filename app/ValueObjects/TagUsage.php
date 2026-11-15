<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\Tag;

final readonly class TagUsage
{
    public function __construct(public int $xPosts, public int $bookmarks) {}

    public static function fromTag(Tag $tag): self
    {
        return new self(
            xPosts: (int) $tag->x_bookmarks_count,
            bookmarks: (int) $tag->links_count,
        );
    }

    public function line(): string
    {
        $parts = array_values(array_filter([
            $this->phrase($this->xPosts, 'X post', 'X posts'),
            $this->phrase($this->bookmarks, 'bookmark', 'bookmarks'),
        ]));

        if ($parts === []) {
            return 'Not used';
        }

        return implode(' · ', $parts);
    }

    public function warning(): string
    {
        if ($this->xPosts === 0 && $this->bookmarks === 0) {
            return 'This tag is not used on any posts or bookmarks. The bookmarks stay. This cannot be undone.';
        }

        return 'This removes the tag from '.$this->line().'. The bookmarks stay. This cannot be undone.';
    }

    private function phrase(int $count, string $singular, string $plural): ?string
    {
        if ($count === 0) {
            return null;
        }

        if ($count === 1) {
            return '1 '.$singular;
        }

        return $count.' '.$plural;
    }
}
