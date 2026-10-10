<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\XPost as XPostValue;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use JsonException;

/**
 * @implements CastsAttributes<XPostValue|null, mixed>
 */
final class XPost implements CastsAttributes
{
    public function __construct(private XMedia $media = new XMedia) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?XPostValue
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        $authorName = data_get($decoded, 'author_name');
        $authorUsername = data_get($decoded, 'author_username');
        $authorAvatarUrl = data_get($decoded, 'author_avatar_url');
        $text = data_get($decoded, 'text');
        $postedAt = data_get($decoded, 'posted_at');
        $url = data_get($decoded, 'url');

        return new XPostValue(
            authorName: is_string($authorName) ? $authorName : '',
            authorUsername: is_string($authorUsername) ? $authorUsername : '',
            authorAvatarUrl: is_string($authorAvatarUrl) ? $authorAvatarUrl : null,
            text: is_string($text) ? $text : '',
            postedAt: is_string($postedAt) ? Date::parse($postedAt) : Date::now(),
            media: $this->media->get($model, 'media', json_encode(data_get($decoded, 'media') ?? [], JSON_THROW_ON_ERROR), $attributes),
            url: is_string($url) ? $url : '',
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof XPostValue) {
            throw new InvalidArgumentException('Quoted posts must be '.XPostValue::class.' instances.');
        }

        return json_encode([
            'author_name' => $value->authorName,
            'author_username' => $value->authorUsername,
            'author_avatar_url' => $value->authorAvatarUrl,
            'text' => $value->text,
            'posted_at' => $value->postedAt->toIso8601String(),
            'media' => json_decode($this->media->set($model, 'media', $value->media, $attributes), true, 512, JSON_THROW_ON_ERROR),
            'url' => $value->url,
        ], JSON_THROW_ON_ERROR);
    }
}
