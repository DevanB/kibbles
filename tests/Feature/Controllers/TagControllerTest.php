<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;
use Illuminate\Support\Str;

it('redirects guests to login', function (string $method, string $route, array $parameters = []): void {
    $this->{$method}(route($route, $parameters))->assertRedirectToRoute('login');
})->with([
    'index' => ['get', 'tags.index'],
    'show' => ['get', 'tags.show', ['tag' => '00000000-0000-0000-0000-000000000001']],
    'edit' => ['get', 'tags.edit', ['tag' => '00000000-0000-0000-0000-000000000001']],
    'update' => ['put', 'tags.update', ['tag' => '00000000-0000-0000-0000-000000000001']],
    'destroy' => ['delete', 'tags.destroy', ['tag' => '00000000-0000-0000-0000-000000000001']],
]);

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertRedirectToRoute('verification.notice');
});

it('lists only the authenticated user tags in alphabetical order', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    Tag::factory()->recycle($user)->create(['name' => 'Pest']);
    $laravel = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    Tag::factory()->create(['name' => 'Secret']);

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tags/index')
            ->has('tags', 2)
            ->where('tags.0.id', $laravel->id)
            ->where('tags.0.name', 'Laravel')
            ->where('tags.1.name', 'Pest'));
});

it('returns 404 for a missing tag', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('tags.show', Str::uuid()->toString()))
        ->assertNotFound();
});

it('forbids another user from viewing, renaming, or deleting a tag', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($owner)->create(['name' => 'Laravel']);

    $this->actingAs($intruder)
        ->get(route('tags.show', $tag))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->get(route('tags.edit', $tag))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->put(route('tags.update', $tag), ['name' => 'PHP'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('tags.destroy', $tag))
        ->assertForbidden();

    expect($tag->fresh()?->name)->toBe('Laravel');
});

it('renames a tag and keeps the typed casing', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => 'true'])
        ->get(route('tags.edit', $tag))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tags/edit')
            ->where('tag.id', $tag->id)
            ->where('tag.name', 'Laravel'));

    $this->actingAs($user)
        ->fromRoute('tags.index')
        ->put(route('tags.update', $tag), ['name' => '  PHP  '])
        ->assertRedirectToRoute('tags.index');

    expect($tag->refresh()->name)->toBe('PHP');
});

it('allows renaming a tag to the same folded name', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    $this->actingAs($user)
        ->put(route('tags.update', $tag), ['name' => 'laravel'])
        ->assertRedirectToRoute('tags.index');

    expect($tag->refresh()->name)->toBe('laravel');
});

it('rejects a case-insensitive duplicate name when renaming', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Pest']);

    $this->actingAs($user)
        ->fromRoute('tags.edit', $tag)
        ->put(route('tags.update', $tag), ['name' => 'laravel'])
        ->assertRedirectToRoute('tags.edit', $tag)
        ->assertSessionHasErrors(['name' => 'You already have a tag with this name.']);

    expect($tag->refresh()->name)->toBe('Pest');
});

it('rejects a non-string name when renaming a tag', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    $this->actingAs($user)
        ->fromRoute('tags.edit', $tag)
        ->put(route('tags.update', $tag), ['name' => ['Laravel']])
        ->assertRedirectToRoute('tags.edit', $tag)
        ->assertSessionHasErrors(['name' => 'The name must be a string.']);
});

it('rejects a name that is too long when renaming a tag', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    $this->actingAs($user)
        ->fromRoute('tags.edit', $tag)
        ->put(route('tags.update', $tag), ['name' => str_repeat('a', 256)])
        ->assertRedirectToRoute('tags.edit', $tag)
        ->assertSessionHasErrors(['name' => 'The name may not be greater than 255 characters.']);
});

it('requires a name when renaming a tag', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    $this->actingAs($user)
        ->fromRoute('tags.edit', $tag)
        ->put(route('tags.update', $tag), ['name' => ''])
        ->assertRedirectToRoute('tags.edit', $tag)
        ->assertSessionHasErrors(['name' => 'A name is required.']);
});

it('deletes a tag and leaves the bookmarks', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $bookmark->tags()->attach($tag);

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => 'true'])
        ->get(route('tags.show', $tag))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tags/show')
            ->where('tag.name', 'Laravel'));

    $this->actingAs($user)
        ->delete(route('tags.destroy', $tag))
        ->assertRedirectToRoute('tags.index');

    expect($tag->fresh())->toBeNull()
        ->and($bookmark->fresh())->not->toBeNull()
        ->and($bookmark->tags()->count())->toBe(0);
});
