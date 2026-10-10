<?php

declare(strict_types=1);

namespace App\Services;

use App\ValueObjects\XMedia;
use App\ValueObjects\XPost;
use Carbon\CarbonInterface;

final readonly class FetchedXBookmark
{
    /**
     * @param  list<XMedia>  $media
     */
    public function __construct(
        public string $xPostId,
        public string $authorName,
        public string $authorUsername,
        public ?string $authorAvatarUrl,
        public string $text,
        public CarbonInterface $postedAt,
        public array $media,
        public ?XPost $quotedPost,
    ) {}
}
