<?php

declare(strict_types=1);

namespace App\Services;

final readonly class XBookmarkIdsPage
{
    /**
     * @param  list<string>  $ids
     */
    public function __construct(
        public array $ids,
        public ?string $nextToken,
    ) {}
}
