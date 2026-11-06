<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\LinkBuilder;
use App\Enums\LinkPreviewStatus;
use Carbon\CarbonInterface;
use Database\Factories\LinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read string $id
 * @property-read string $user_id
 * @property-read string $url
 * @property-read string|null $title
 * @property-read string|null $site_name
 * @property-read string|null $description
 * @property-read string|null $image_url
 * @property-read LinkPreviewStatus $preview_status
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Collection<int, Tag> $tags
 *
 * @method static LinkBuilder query()
 * @method static LinkBuilder newestFirst()
 * @method static LinkBuilder tagged(Tag $tag)
 * @method static LinkBuilder untagged()
 */
#[Fillable([
    'url',
    'title',
    'site_name',
    'description',
    'image_url',
    'preview_status',
])]
#[UseEloquentBuilder(LinkBuilder::class)]
final class Link extends Model
{
    /** @use HasFactory<LinkFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return array{
     *     id: string,
     *     url: string,
     *     title: string|null,
     *     siteName: string|null,
     *     description: string|null,
     *     imageUrl: string|null,
     *     previewStatus: string,
     *     tags: list<array{id: string, name: string}>
     * }
     */
    public function toWire(): array
    {
        $tags = [];

        foreach ($this->tags as $tag) {
            $tags[] = $tag->toWire();
        }

        return [
            'id' => $this->id,
            'url' => $this->url,
            'title' => $this->title,
            'siteName' => $this->site_name,
            'description' => $this->description,
            'imageUrl' => $this->image_url,
            'previewStatus' => $this->preview_status->value,
            'tags' => $tags,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'url' => 'string',
            'title' => 'string',
            'site_name' => 'string',
            'description' => 'string',
            'image_url' => 'string',
            'preview_status' => LinkPreviewStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
