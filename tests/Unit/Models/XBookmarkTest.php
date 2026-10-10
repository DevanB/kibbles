<?php

declare(strict_types=1);

use App\Casts\XMedia;
use App\Casts\XPost;
use App\Enums\XMediaType;
use App\Models\User;
use App\Models\XBookmark;
use App\ValueObjects\XMedia as XMediaValue;
use App\ValueObjects\XPost as XPostValue;
use InvalidArgumentException;

it('round-trips media and quoted posts through the custom casts', function (): void {
    $bookmark = XBookmark::factory()->quoted()->video()->create([
        'author_username' => 'alice',
        'x_post_id' => '123',
    ])->refresh();

    expect($bookmark->media[0]->type)->toBe(XMediaType::Video)
        ->and($bookmark->media[0]->mp4Url)->toBe('https://video.twimg.com/high.mp4')
        ->and($bookmark->quoted_post?->authorUsername)->toBe('quoted')
        ->and($bookmark->url())->toBe('https://x.com/alice/status/123')
        ->and($bookmark->toWire()['url'])->toBe('https://x.com/alice/status/123')
        ->and($bookmark->toWire()['quotedPost']['url'])->toBe('https://x.com/quoted/status/99')
        ->and($bookmark->user)->toBeInstanceOf(User::class);
});

it('treats invalid stored media and quoted posts as empty', function (): void {
    $bookmark = XBookmark::factory()->create();
    $media = new XMedia;
    $quoted = new XPost;

    expect($media->get($bookmark, 'media', '', []))->toBe([])
        ->and($media->get($bookmark, 'media', 'not-json', []))->toBe([])
        ->and($media->get($bookmark, 'media', '[]', []))->toBe([])
        ->and($media->get($bookmark, 'media', '"nope"', []))->toBe([])
        ->and($quoted->get($bookmark, 'quoted_post', null, []))->toBeNull()
        ->and($quoted->get($bookmark, 'quoted_post', '', []))->toBeNull()
        ->and($quoted->get($bookmark, 'quoted_post', 'not-json', []))->toBeNull()
        ->and($quoted->get($bookmark, 'quoted_post', '"nope"', []))->toBeNull()
        ->and($quoted->get($bookmark, 'quoted_post', '{}', []))->toBeInstanceOf(XPostValue::class)
        ->and($quoted->set($bookmark, 'quoted_post', null, []))->toBeNull();
});

it('rejects values that are not media or post objects', function (): void {
    $bookmark = XBookmark::factory()->create();

    expect(fn () => (new XMedia)->set($bookmark, 'media', 'nope', []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => (new XMedia)->set($bookmark, 'media', ['nope'], []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => (new XPost)->set($bookmark, 'quoted_post', 'nope', []))
        ->toThrow(InvalidArgumentException::class);
});

it('hydrates media items with missing fields as a photo', function (): void {
    $bookmark = XBookmark::factory()->create();
    $media = (new XMedia)->get($bookmark, 'media', '[{}]', []);

    expect($media[0])->toBeInstanceOf(XMediaValue::class)
        ->and($media[0]->type)->toBe(XMediaType::Photo)
        ->and($media[0]->url)->toBe('');
});
