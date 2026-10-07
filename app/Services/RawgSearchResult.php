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

    public static function from(mixed $payload): self
    {
        $id = data_get($payload, 'id');
        $name = data_get($payload, 'name');
        $released = data_get($payload, 'released');
        $image = data_get($payload, 'background_image');

        return new self(
            id: is_int($id) ? $id : 0,
            name: is_string($name) ? $name : '',
            releasedYear: is_string($released) ? (int) mb_substr($released, 0, 4) : null,
            backgroundImage: is_string($image) ? $image : null,
        );
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
