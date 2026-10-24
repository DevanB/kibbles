<?php

declare(strict_types=1);

use App\Actions\AttachBookmarkTag;
use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;

it('attaches an existing tag without creating another', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $attached = resolve(AttachBookmarkTag::class)->handle($bookmark, $tag);

    expect($attached->is($tag))->toBeTrue()
        ->and($bookmark->tags()->whereKey($tag)->exists())->toBeTrue()
        ->and($user->tags()->count())->toBe(1);
});

it('creates and attaches a tag from a typed name', function (): void {
    $user = User::factory()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $tag = resolve(AttachBookmarkTag::class)->handle($bookmark, 'Pest');

    expect($tag->name)->toBe('Pest')
        ->and($bookmark->tags()->whereKey($tag)->exists())->toBeTrue();
});

it('attaches the existing folded name instead of creating a duplicate', function (): void {
    $user = User::factory()->create();
    $existing = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $tag = resolve(AttachBookmarkTag::class)->handle($bookmark, 'laravel');

    expect($tag->is($existing))->toBeTrue()
        ->and($existing->refresh()->name)->toBe('Laravel')
        ->and($user->tags()->count())->toBe(1);
});
