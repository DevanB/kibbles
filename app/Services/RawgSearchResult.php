<?php

declare(strict_types=1);

namespace App\Services;

final readonly class RawgSearchResult
{
    public function __construct(
        public int $id,
        public string $name,
        public ?int $releasedYear,
        public ?string $backgroundImage,
    ) {}

    public static function tryFrom(mixed $payload): ?self
    {
        if (! is_array($payload)) {
            return null;
        }

        $id = $payload['id'] ?? null;
        $name = $payload['name'] ?? null;

        if (! is_numeric($id) || (int) $id < 1 || (string) (int) $id !== (string) $id) {
            return null;
        }

        if (! is_string($name) || $name === '') {
            return null;
        }

        return new self(
            id: (int) $id,
            name: $name,
            releasedYear: self::year($payload['released'] ?? null),
            backgroundImage: self::imageUrl($payload['background_image'] ?? null),
        );
    }

    public static function year(mixed $released): ?int
    {
        if (! is_string($released) || ! preg_match('/^(\d{4})/', $released, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    public static function imageUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        if (! str_starts_with($url, 'https://') && ! str_starts_with($url, 'http://')) {
            return null;
        }

        return $url;
    }

    /**
     * @return array{id: int, name: string, releasedYear: int|null, backgroundImage: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'releasedYear' => $this->releasedYear,
            'backgroundImage' => $this->backgroundImage,
        ];
    }
}
