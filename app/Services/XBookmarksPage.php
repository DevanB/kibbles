<?php

declare(strict_types=1);

namespace App\Services;

final readonly class XBookmarksPage
{
    /**
     * @param  list<FetchedXBookmark>  $bookmarks
     */
    public function __construct(
        public array $bookmarks,
        public ?string $nextToken,
    ) {}
}
