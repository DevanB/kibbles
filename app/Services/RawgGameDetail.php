<?php

declare(strict_types=1);

namespace App\Services;

final readonly class RawgGameDetail
{
    public function __construct(
        public string $name,
        public ?string $backgroundImage,
        public ?string $description,
    ) {}

    public static function tryFrom(mixed $payload): ?self
    {
        if (! is_array($payload)) {
            return null;
        }

        $name = $payload['name'] ?? null;

        if (! is_string($name) || $name === '') {
            return null;
        }

        $description = $payload['description_raw'] ?? null;

        return new self(
            name: $name,
            backgroundImage: RawgSearchResult::imageUrl($payload['background_image'] ?? null),
            description: is_string($description) && mb_trim($description) !== '' ? $description : null,
        );
    }
}
