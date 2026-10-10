<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\XBookmarkBuilder;
use App\Casts\XMedia;
use App\Casts\XPost;
use App\ValueObjects\XMedia as XMediaValue;
use App\ValueObjects\XPost as XPostValue;
use Carbon\CarbonInterface;
use Database\Factories\XBookmarkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $user_id
 * @property-read string $x_post_id
 * @property-read string $author_name
 * @property-read string $author_username
 * @property-read string|null $author_avatar_url
 * @property-read string $text
 * @property-read CarbonInterface $posted_at
 * @property-read CarbonInterface $first_seen_at
 * @property-read list<XMediaValue> $media
 * @property-read XPostValue|null $quoted_post
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 *
 * @method static XBookmarkBuilder query()
 */
#[Fillable([
    'x_post_id',
    'author_name',
    'author_username',
    'author_avatar_url',
    'text',
    'posted_at',
    'first_seen_at',
    'media',
    'quoted_post',
])]
#[UseEloquentBuilder(XBookmarkBuilder::class)]
final class XBookmark extends Model
{
    /** @use HasFactory<XBookmarkFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function url(): string
    {
        return 'https://x.com/'.$this->author_username.'/status/'.$this->x_post_id;
    }

    /**
     * @return array{id: string, xPostId: string, authorName: string, authorUsername: string, authorAvatarUrl: string|null, text: string, postedAt: string, firstSeenAt: string, media: list<array{type: string, url: string, previewUrl: string|null, width: int|null, height: int|null, mp4Url: string|null}>, quotedPost: array{authorName: string, authorUsername: string, authorAvatarUrl: string|null, text: string, postedAt: string, media: list<array{type: string, url: string, previewUrl: string|null, width: int|null, height: int|null, mp4Url: string|null}>, url: string}|null, url: string}
     */
    public function toWire(): array
    {
        return [
            'id' => $this->id,
            'xPostId' => $this->x_post_id,
            'authorName' => $this->author_name,
            'authorUsername' => $this->author_username,
            'authorAvatarUrl' => $this->author_avatar_url,
            'text' => $this->text,
            'postedAt' => $this->posted_at->toIso8601String(),
            'firstSeenAt' => $this->first_seen_at->toIso8601String(),
            'media' => array_map(
                fn (XMediaValue $media): array => $media->toWire(),
                $this->media,
            ),
            'quotedPost' => $this->quoted_post?->toWire(),
            'url' => $this->url(),
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
            'x_post_id' => 'string',
            'author_name' => 'string',
            'author_username' => 'string',
            'author_avatar_url' => 'string',
            'text' => 'string',
            'posted_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'media' => XMedia::class,
            'quoted_post' => XPost::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
