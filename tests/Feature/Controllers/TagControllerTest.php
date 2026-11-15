<?php

declare(strict_types=1);

use App\Models\Link;
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
            ->where('tags.0.usage', 'Not used')
            ->where('tags.1.name', 'Pest')
            ->where('tags.1.usage', 'Not used'));
});

it('shows how many posts and bookmarks use each tag', function (int $xPosts, int $bookmarks, string $usage): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    if ($xPosts > 0) {
        $tag->xBookmarks()->attach(XBookmark::factory()->recycle($user)->count($xPosts)->create());
    }

    if ($bookmarks > 0) {
        $tag->links()->attach(Link::factory()->recycle($user)->count($bookmarks)->create());
    }

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tags.0.usage', $usage));
})->with([
    'unused' => [0, 0, 'Not used'],
    'X posts only' => [2, 0, '2 X posts'],
    'bookmarks only' => [0, 3, '3 bookmarks'],
    'both types' => [2, 1, '2 X posts · 1 bookmark'],
]);

it('counts only attachments on that tag for the authenticated user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $other = User::factory()->withoutTwoFactor()->create();
    $laravel = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $php = Tag::factory()->recycle($user)->create(['name' => 'PHP']);
    $secret = Tag::factory()->recycle($other)->create(['name' => 'Laravel']);
    $laravel->xBookmarks()->attach(XBookmark::factory()->recycle($user)->create());
    $php->links()->attach(Link::factory()->recycle($user)->count(2)->create());
    $secret->xBookmarks()->attach(XBookmark::factory()->recycle($other)->count(3)->create());

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('tags', 2)
            ->where('tags.0.id', $laravel->id)
            ->where('tags.0.usage', '1 X post')
            ->where('tags.1.id', $php->id)
            ->where('tags.1.usage', '2 bookmarks'));
});

it('drops the usage count after a detach and after deleting a tagged bookmark', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $first = XBookmark::factory()->recycle($user)->create();
    $second = XBookmark::factory()->recycle($user)->create();
    $link = Link::factory()->recycle($user)->create();
    $tag->xBookmarks()->attach([$first->id, $second->id]);
    $tag->links()->attach($link);

    $this->actingAs($user);

    $this->get(route('tags.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tags.0.usage', '2 X posts · 1 bookmark'));

    $this->fromRoute('tags.index')
        ->delete(route('x-bookmarks.tags.destroy', [$first, $tag]))
        ->assertRedirectToRoute('tags.index');

    $this->get(route('tags.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tags.0.usage', '1 X post · 1 bookmark'));

    $this->fromRoute('tags.index')
        ->delete(route('bookmarks.destroy', $link))
        ->assertRedirectToRoute('tags.index');

    $this->get(route('tags.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tags.0.usage', '1 X post'));
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
            ->where('tag.name', 'Laravel')
            ->where('tag.usage', '1 X post')
            ->where('tag.warning', 'This removes the tag from 1 X post. The bookmarks stay. This cannot be undone.'));

    $this->actingAs($user)
        ->delete(route('tags.destroy', $tag))
        ->assertRedirectToRoute('tags.index');

    expect($tag->fresh())->toBeNull()
        ->and($bookmark->fresh())->not->toBeNull()
        ->and($bookmark->tags()->count())->toBe(0);
});
