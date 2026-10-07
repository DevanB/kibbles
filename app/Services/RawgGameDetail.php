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

    public static function from(mixed $payload): self
    {
        $name = data_get($payload, 'name');
        $image = data_get($payload, 'background_image');
        $description = data_get($payload, 'description_raw');

        return new self(
            name: is_string($name) ? $name : '',
            backgroundImage: is_string($image) ? $image : null,
            description: is_string($description) ? $description : null,
        );
    }
}
