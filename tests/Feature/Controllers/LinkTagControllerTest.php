<?php

declare(strict_types=1);

use App\Models\Link;
use App\Models\Tag;
use App\Models\User;

it('redirects guests to login', function (string $method, string $route, array $parameters = []): void {
    $this->{$method}(route($route, $parameters))->assertRedirectToRoute('login');
})->with([
    'store' => ['post', 'bookmarks.tags.store', ['link' => '00000000-0000-0000-0000-000000000001']],
    'destroy' => ['delete', 'bookmarks.tags.destroy', [
        'link' => '00000000-0000-0000-0000-000000000001',
        'tag' => '00000000-0000-0000-0000-000000000002',
    ]],
]);

it('attaches an existing tag to a link', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $link), ['tag_id' => $tag->id])
        ->assertRedirectToRoute('bookmarks.index');

    expect($link->tags()->whereKey($tag)->exists())->toBeTrue();
});

it('creates a tag from a typed name and attaches it', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $link), ['name' => '  Pest  '])
        ->assertRedirectToRoute('bookmarks.index');

    $tag = $user->tags()->named('Pest')->first();

    expect($tag?->name)->toBe('Pest')
        ->and($link->tags()->whereKey($tag)->exists())->toBeTrue();
});

it('attaches the existing folded name instead of creating a duplicate', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $link), ['name' => 'laravel'])
        ->assertRedirectToRoute('bookmarks.index');

    expect($user->tags()->count())->toBe(1)
        ->and($tag->refresh()->name)->toBe('Laravel')
        ->and($link->tags()->whereKey($tag)->exists())->toBeTrue();
});

it('forbids another user from attaching a tag to a link they do not own', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($owner)->create();
    $tag = Tag::factory()->recycle($intruder)->create(['name' => 'Mine']);

    $this->actingAs($intruder)
        ->post(route('bookmarks.tags.store', $link), ['tag_id' => $tag->id])
        ->assertForbidden();

    expect($link->tags()->count())->toBe(0);
});

it('rejects another user tag when attaching to an owned link', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($owner)->create();
    $tag = Tag::factory()->recycle($intruder)->create(['name' => 'Secret']);

    $this->actingAs($owner)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $link), ['tag_id' => $tag->id])
        ->assertRedirectToRoute('bookmarks.index')
        ->assertSessionHasErrors(['tag_id' => 'The selected tag does not exist.']);

    expect($link->tags()->count())->toBe(0);
});

it('rejects an invalid tag id when attaching', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $link), ['tag_id' => 'not-a-uuid'])
        ->assertRedirectToRoute('bookmarks.index')
        ->assertSessionHasErrors(['tag_id' => 'The tag id must be a valid UUID.']);
});

it('rejects a non-string name when attaching', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $link), ['name' => ['Pest']])
        ->assertRedirectToRoute('bookmarks.index')
        ->assertSessionHasErrors(['name' => 'The tag name must be a string.']);
});

it('rejects a name that is too long when attaching', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $link), ['name' => str_repeat('a', 256)])
        ->assertRedirectToRoute('bookmarks.index')
        ->assertSessionHasErrors(['name' => 'The tag name may not be greater than 255 characters.']);
});

it('requires a tag id or name when attaching', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.tags.store', $link), [])
        ->assertRedirectToRoute('bookmarks.index')
        ->assertSessionHasErrors(['tag_id' => 'A tag is required.', 'name' => 'A tag name is required.']);
});

it('detaches a tag from a link and keeps both records', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $link->tags()->attach($tag);

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->delete(route('bookmarks.tags.destroy', [$link, $tag]))
        ->assertRedirectToRoute('bookmarks.index');

    expect($link->tags()->count())->toBe(0)
        ->and($tag->fresh())->not->toBeNull()
        ->and($link->fresh())->not->toBeNull();
});

it('forbids another user from detaching a tag on a link they do not own', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($owner)->create();
    $tag = Tag::factory()->recycle($owner)->create(['name' => 'Laravel']);
    $link->tags()->attach($tag);

    $this->actingAs($intruder)
        ->delete(route('bookmarks.tags.destroy', [$link, $tag]))
        ->assertForbidden();

    expect($link->tags()->whereKey($tag)->exists())->toBeTrue();
});
