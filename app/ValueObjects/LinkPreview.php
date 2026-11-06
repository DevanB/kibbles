<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class LinkPreview
{
    public function __construct(
        public ?string $title,
        public ?string $siteName,
        public ?string $description,
        public ?string $imageUrl,
    ) {}
}
