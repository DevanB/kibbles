<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\CarbonInterface;

final readonly class XPost
{
    /**
     * @param  list<XMedia>  $media
     */
    public function __construct(
        public string $authorName,
        public string $authorUsername,
        public ?string $authorAvatarUrl,
        public string $text,
        public CarbonInterface $postedAt,
        public array $media,
        public string $url,
    ) {}

    /**
     * @return array{authorName: string, authorUsername: string, authorAvatarUrl: string|null, text: string, postedAt: string, media: list<array{type: string, url: string, previewUrl: string|null, width: int|null, height: int|null, mp4Url: string|null}>, url: string}
     */
    public function toWire(): array
    {
        return [
            'authorName' => $this->authorName,
            'authorUsername' => $this->authorUsername,
            'authorAvatarUrl' => $this->authorAvatarUrl,
            'text' => $this->text,
            'postedAt' => $this->postedAt->toIso8601String(),
            'media' => array_map(
                fn (XMedia $media): array => $media->toWire(),
                $this->media,
            ),
            'url' => $this->url,
        ];
    }
}
