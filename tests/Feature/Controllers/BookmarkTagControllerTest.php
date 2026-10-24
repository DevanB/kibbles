<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;

it('redirects guests to login', function (string $method, string $route, array $parameters = []): void {
    $this->{$method}(route($route, $parameters))->assertRedirectToRoute('login');
})->with([
    'store' => ['post', 'bookmarks.tags.store', ['bookmark' => '00000000-0000-0000-0000-000000000001']],
    'destroy' => ['delete', 'bookmarks.tags.destroy', [
        'bookmark' => '00000000-0000-0000-0000-000000000001',
        'tag' => '00000000-0000-0000-0000-000000000002',
    ]],
]);

it('attaches an existing tag to a bookmark', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $bookmark), ['tag_id' => $tag->id])
        ->assertRedirectToRoute('bookmarks.index');

    expect($bookmark->tags()->whereKey($tag)->exists())->toBeTrue();
});

it('creates a tag from a typed name and attaches it', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $bookmark), ['name' => '  Pest  '])
        ->assertRedirectToRoute('bookmarks.index');

    $tag = $user->tags()->named('Pest')->first();

    expect($tag?->name)->toBe('Pest')
        ->and($bookmark->tags()->whereKey($tag)->exists())->toBeTrue();
});

it('attaches the existing folded name instead of creating a duplicate', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $bookmark), ['name' => 'laravel'])
        ->assertRedirectToRoute('bookmarks.index');

    expect($user->tags()->count())->toBe(1)
        ->and($tag->refresh()->name)->toBe('Laravel')
        ->and($bookmark->tags()->whereKey($tag)->exists())->toBeTrue();
});

it('forbids another user from attaching a tag to a bookmark they do not own', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($owner)->create();
    $tag = Tag::factory()->recycle($intruder)->create(['name' => 'Mine']);

    $this->actingAs($intruder)
        ->post(route('bookmarks.tags.store', $bookmark), ['tag_id' => $tag->id])
        ->assertForbidden();

    expect($bookmark->tags()->count())->toBe(0);
});

it('forbids attaching another user tag to an owned bookmark', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($owner)->create();
    $tag = Tag::factory()->recycle($intruder)->create(['name' => 'Secret']);

    $this->actingAs($owner)
        ->post(route('bookmarks.tags.store', $bookmark), ['tag_id' => $tag->id])
        ->assertForbidden();

    expect($bookmark->tags()->count())->toBe(0);
});

it('rejects an invalid tag id when attaching', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $bookmark), ['tag_id' => 'not-a-uuid'])
        ->assertRedirectToRoute('bookmarks.index')
        ->assertSessionHasErrors(['tag_id' => 'The tag id must be a valid UUID.']);
});

it('rejects a non-string name when attaching', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $bookmark), ['name' => ['Pest']])
        ->assertRedirectToRoute('bookmarks.index')
        ->assertSessionHasErrors(['name' => 'The tag name must be a string.']);
});

it('rejects a name that is too long when attaching', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $bookmark), ['name' => str_repeat('a', 256)])
        ->assertRedirectToRoute('bookmarks.index')
        ->assertSessionHasErrors(['name' => 'The tag name may not be greater than 255 characters.']);
});

it('requires a tag id or name when attaching', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $bookmark), [])
        ->assertRedirectToRoute('bookmarks.index')
        ->assertSessionHasErrors(['tag_id' => 'A tag is required.', 'name' => 'A tag name is required.']);
});

it('detaches a tag from a bookmark and keeps both records', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $bookmark->tags()->attach($tag);

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->delete(route('bookmarks.tags.destroy', [$bookmark, $tag]))
        ->assertRedirectToRoute('bookmarks.index');

    expect($bookmark->tags()->count())->toBe(0)
        ->and($tag->fresh())->not->toBeNull()
        ->and($bookmark->fresh())->not->toBeNull();
});

it('forbids another user from detaching a tag on a bookmark they do not own', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($owner)->create();
    $tag = Tag::factory()->recycle($owner)->create(['name' => 'Laravel']);
    $bookmark->tags()->attach($tag);

    $this->actingAs($intruder)
        ->delete(route('bookmarks.tags.destroy', [$bookmark, $tag]))
        ->assertForbidden();

    expect($bookmark->tags()->whereKey($tag)->exists())->toBeTrue();
});
