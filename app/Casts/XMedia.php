<?php

declare(strict_types=1);

namespace App\Casts;

use App\Enums\XMediaType;
use App\ValueObjects\XMedia as XMediaValue;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JsonException;

/**
 * @implements CastsAttributes<list<XMediaValue>, list<XMediaValue>>
 */
final class XMedia implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return list<XMediaValue>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (! is_string($value) || $value === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_map($this->fromStored(...), $decoded));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        throw_unless(is_array($value), InvalidArgumentException::class, 'X media must be a list of '.XMediaValue::class.' instances.');

        return json_encode(array_map($this->toStored(...), $value), JSON_THROW_ON_ERROR);
    }

    private function fromStored(mixed $item): XMediaValue
    {
        $storedType = data_get($item, 'type');
        $type = is_string($storedType) ? (XMediaType::tryFrom($storedType) ?? XMediaType::Photo) : XMediaType::Photo;
        $url = data_get($item, 'url');
        $previewUrl = data_get($item, 'preview_url');
        $width = data_get($item, 'width');
        $height = data_get($item, 'height');
        $mp4Url = data_get($item, 'mp4_url');

        return new XMediaValue(
            type: $type,
            url: is_string($url) ? $url : '',
            previewUrl: is_string($previewUrl) ? $previewUrl : null,
            width: is_int($width) ? $width : null,
            height: is_int($height) ? $height : null,
            mp4Url: is_string($mp4Url) ? $mp4Url : null,
        );
    }

    /**
     * @return array{type: string, url: string, preview_url: string|null, width: int|null, height: int|null, mp4_url: string|null}
     */
    private function toStored(mixed $item): array
    {
        throw_unless($item instanceof XMediaValue, InvalidArgumentException::class, 'X media items must be '.XMediaValue::class.' instances.');

        return [
            'type' => $item->type->value,
            'url' => $item->url,
            'preview_url' => $item->previewUrl,
            'width' => $item->width,
            'height' => $item->height,
            'mp4_url' => $item->mp4Url,
        ];
    }
}
