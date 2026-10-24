<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;
use App\Models\XConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

it('redirects guests to login', function (string $method, string $route, array $parameters = []): void {
    $this->{$method}(route($route, $parameters))->assertRedirectToRoute('login');
})->with([
    'index' => ['get', 'bookmarks.index'],
    'show' => ['get', 'bookmarks.show', ['bookmark' => '00000000-0000-0000-0000-000000000001']],
    'destroy' => ['delete', 'bookmarks.destroy', ['bookmark' => '00000000-0000-0000-0000-000000000001']],
]);

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertRedirectToRoute('verification.notice');
});

it('returns 404 for a missing bookmark', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('bookmarks.show', Str::uuid()->toString()))
        ->assertNotFound();
});

it('lists only the authenticated user bookmarks and connection', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $connection = XConnection::factory()->recycle($user)->synced()->create(['username' => 'devan']);
    $owned = XBookmark::factory()->recycle($user)->create(['text' => 'Mine']);
    XBookmark::factory()->create(['text' => 'Someone else']);

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bookmarks/index')
            ->has('bookmarks.data', 1)
            ->has('bookmarks.data.0', fn ($bookmark) => $bookmark
                ->where('id', $owned->id)
                ->where('text', 'Mine')
                ->where('url', $owned->url())
                ->where('tags', [])
                ->etc())
            ->where('xConnection.username', $connection->username)
            ->where('xConnection.lastSyncedAt', $connection->last_synced_at?->toIso8601String())
            ->where('activeTag', null));
});

it('lists bookmarks newest first', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->create();
    $older = XBookmark::factory()->recycle($user)->create(['first_seen_at' => now()->subDay()]);
    $newer = XBookmark::factory()->recycle($user)->create(['first_seen_at' => now()]);

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('bookmarks.data.0.id', $newer->id)
            ->where('bookmarks.data.1.id', $older->id));
});

it('returns a cursor page of bookmarks and the following page in newest-first order', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->create();

    $bookmarks = XBookmark::factory()
        ->recycle($user)
        ->withoutMedia()
        ->count(25)
        ->sequence(fn ($sequence): array => [
            'first_seen_at' => now()->subMinutes($sequence->index),
        ])
        ->create();

    $first = $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertOk();

    $nextCursor = null;
    $firstPageSize = 0;

    $first->assertInertia(function ($page) use ($bookmarks, &$nextCursor, &$firstPageSize): void {
        $props = $page->toArray()['props']['bookmarks'];
        $firstPageSize = count($props['data']);
        $nextCursor = $props['next_cursor'];

        expect($firstPageSize)->toBeGreaterThan(0)
            ->and($firstPageSize)->toBeLessThan($bookmarks->count())
            ->and($props['data'][0]['id'])->toBe($bookmarks[0]->id)
            ->and($props['data'][$firstPageSize - 1]['id'])->toBe($bookmarks[$firstPageSize - 1]->id)
            ->and($nextCursor)->not->toBeEmpty();
    });

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['cursor' => $nextCursor]))
        ->assertOk()
        ->assertInertia(function ($page) use ($bookmarks, $firstPageSize): void {
            $props = $page->toArray()['props']['bookmarks'];
            $nextPage = $props['data'];

            expect($nextPage[0]['id'])->toBe($bookmarks[$firstPageSize]->id)
                ->and($nextPage[count($nextPage) - 1]['id'])->toBe($bookmarks->last()->id)
                ->and($nextPage)->toHaveCount($bookmarks->count() - $firstPageSize)
                ->and($props['next_cursor'])->toBeNull();
        });
});

it('includes attached tags on the bookmark payload', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $bookmark->tags()->attach($tag);

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('bookmarks.data.0.tags.0.id', $tag->id)
            ->where('bookmarks.data.0.tags.0.name', 'Laravel'));

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => 'true'])
        ->get(route('bookmarks.show', $bookmark))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('bookmark.tags.0.id', $tag->id)
            ->where('bookmark.tags.0.name', 'Laravel'));
});

it('filters bookmarks to one tag at a time', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $laravel = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $pest = Tag::factory()->recycle($user)->create(['name' => 'Pest']);
    $taggedLaravel = XBookmark::factory()->recycle($user)->create(['text' => 'Laravel post']);
    $taggedPest = XBookmark::factory()->recycle($user)->create(['text' => 'Pest post']);
    $untagged = XBookmark::factory()->recycle($user)->create(['text' => 'Loose post']);
    $taggedLaravel->tags()->attach($laravel);
    $taggedPest->tags()->attach($pest);

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => $laravel->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('bookmarks.data', 1)
            ->where('bookmarks.data.0.id', $taggedLaravel->id)
            ->where('activeTag.id', $laravel->id)
            ->where('activeTag.name', 'Laravel'));

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => 'untagged']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('bookmarks.data', 1)
            ->where('bookmarks.data.0.id', $untagged->id)
            ->where('activeTag.id', 'untagged')
            ->where('activeTag.name', 'Untagged'));
});

it('returns a cursor page of tagged bookmarks and the following page', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $tagged = XBookmark::factory()
        ->recycle($user)
        ->withoutMedia()
        ->count(25)
        ->sequence(fn ($sequence): array => [
            'first_seen_at' => now()->subMinutes($sequence->index),
        ])
        ->create();
    XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'first_seen_at' => now()->subDays(2),
        'text' => 'untagged leftover',
    ]);

    foreach ($tagged as $bookmark) {
        $bookmark->tags()->attach($tag);
    }

    $first = $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => $tag->id]))
        ->assertOk();

    $nextCursor = null;
    $firstPageSize = 0;

    $first->assertInertia(function ($page) use ($tagged, &$nextCursor, &$firstPageSize): void {
        $props = $page->toArray()['props']['bookmarks'];
        $firstPageSize = count($props['data']);
        $nextCursor = $props['next_cursor'];

        expect($firstPageSize)->toBeGreaterThan(0)
            ->and($firstPageSize)->toBeLessThan($tagged->count())
            ->and($props['data'][0]['id'])->toBe($tagged[0]->id)
            ->and($nextCursor)->not->toBeEmpty();
    });

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => $tag->id, 'cursor' => $nextCursor]))
        ->assertOk()
        ->assertInertia(function ($page) use ($tagged, $firstPageSize): void {
            $props = $page->toArray()['props']['bookmarks'];

            expect($props['data'][0]['id'])->toBe($tagged[$firstPageSize]->id)
                ->and($props['data'])->toHaveCount($tagged->count() - $firstPageSize)
                ->and(collect($props['data'])->pluck('id'))->not->toContain($tagged[0]->id);
        });
});

it('returns 404 when filtering by another user tag', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $foreign = Tag::factory()->create(['name' => 'Secret']);

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => $foreign->id]))
        ->assertNotFound();
});

it('lists every bookmark when the tag query is empty', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => '']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('bookmarks.data', 1)
            ->where('bookmarks.data.0.id', $bookmark->id)
            ->where('activeTag', null));
});

it('ignores a non-string tag query and lists every bookmark', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => ['laravel']]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('bookmarks.data', 1)
            ->where('bookmarks.data.0.id', $bookmark->id)
            ->where('activeTag', null));
});

it('forbids another user from viewing or removing a bookmark', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($owner)->create();

    $this->actingAs($intruder)
        ->get(route('bookmarks.show', $bookmark))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('bookmarks.destroy', $bookmark))
        ->assertForbidden();

    expect($bookmark->fresh())->not->toBeNull();
});

it('keeps the local bookmark when X refuses the delete', function (): void {
    configureX();

    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->create(['x_user_id' => '42']);
    $bookmark = XBookmark::factory()->recycle($user)->create(['x_post_id' => '100']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks/100' => Http::response(['title' => 'forbidden'], 403),
    ]);

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->delete(route('bookmarks.destroy', $bookmark))
        ->assertRedirectToRoute('bookmarks.index')
        ->assertSessionHasErrors('bookmark');

    expect($bookmark->fresh())->not->toBeNull();
});

it('removes a local bookmark when X is not connected', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $bookmark->tags()->attach($tag);

    $this->actingAs($user)
        ->delete(route('bookmarks.destroy', $bookmark))
        ->assertRedirectToRoute('bookmarks.index');

    expect($bookmark->fresh())->toBeNull()
        ->and($tag->fresh())->not->toBeNull()
        ->and($tag->xBookmarks()->count())->toBe(0);
});
