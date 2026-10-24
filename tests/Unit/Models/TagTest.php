<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;
use Illuminate\Database\UniqueConstraintViolationException;

it('enforces case-insensitive unique names per user at the database', function (): void {
    $user = User::factory()->create();
    Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    expect(fn (): Tag => Tag::factory()->recycle($user)->create(['name' => 'laravel']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('allows different users to store the same tag name at the database', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Tag::factory()->recycle($other)->create(['name' => 'Laravel']);

    $tag = Tag::factory()->recycle($owner)->create(['name' => 'Laravel']);

    expect($tag->name)->toBe('Laravel')
        ->and($tag->user()->is($owner))->toBeTrue()
        ->and($tag->toWire())->toBe([
            'id' => $tag->id,
            'name' => 'Laravel',
        ]);
});

it('orders tags alphabetically and finds them by folded name', function (): void {
    $user = User::factory()->create();
    Tag::factory()->recycle($user)->create(['name' => 'Pest']);
    Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    expect($user->tags()->alphabetical()->pluck('name')->all())->toBe(['Laravel', 'Pest'])
        ->and($user->tags()->named('laravel')->value('name'))->toBe('Laravel');
});

it('attaches bookmarks through the pivot without a custom pivot model', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $tag->xBookmarks()->attach($bookmark);

    expect($tag->xBookmarks()->whereKey($bookmark)->exists())->toBeTrue()
        ->and($bookmark->tags()->whereKey($tag)->exists())->toBeTrue();
});
