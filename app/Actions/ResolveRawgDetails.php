<?php

declare(strict_types=1);

namespace App\Actions;

use App\Services\RawgClient;

final readonly class ResolveRawgDetails
{
    public function __construct(private RawgClient $rawg) {}

    /**
     * @return array{image_url: string|null, description: string|null}
     */
    public function handle(?int $rawgId): array
    {
        if ($rawgId === null) {
            return [
                'image_url' => null,
                'description' => null,
            ];
        }

        $detail = $this->rawg->find($rawgId);

        return [
            'image_url' => $detail?->backgroundImage,
            'description' => $detail?->description,
        ];
    }
}
