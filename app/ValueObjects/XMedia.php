<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\XMediaType;

final readonly class XMedia
{
    public function __construct(
        public XMediaType $type,
        public string $url,
        public ?string $previewUrl,
        public ?int $width,
        public ?int $height,
        public ?string $mp4Url,
    ) {}

    /**
     * @return array{type: string, url: string, previewUrl: string|null, width: int|null, height: int|null, mp4Url: string|null}
     */
    public function toWire(): array
    {
        return [
            'type' => $this->type->value,
            'url' => $this->url,
            'previewUrl' => $this->previewUrl,
            'width' => $this->width,
            'height' => $this->height,
            'mp4Url' => $this->mp4Url,
        ];
    }
}
