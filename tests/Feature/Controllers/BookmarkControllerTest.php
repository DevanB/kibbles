<?php

declare(strict_types=1);

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
                ->etc())
            ->where('xConnection.username', $connection->username)
            ->where('xConnection.lastSyncedAt', $connection->last_synced_at?->toIso8601String()));
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

it('returns the first 20 bookmarks and the following page in newest-first order', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->create();

    $bookmarks = XBookmark::factory()
        ->recycle($user)
        ->withoutMedia()
        ->count(25)
        ->sequence(fn ($sequence): array => [
            'text' => 'Saved post '.($sequence->index + 1),
            'first_seen_at' => now()->subMinutes($sequence->index),
        ])
        ->create();

    $first = $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertOk();

    $nextCursor = null;

    $first->assertInertia(function ($page) use ($bookmarks, &$nextCursor): void {
        $page->has('bookmarks.data', 20)
            ->where('bookmarks.data.0.id', $bookmarks[0]->id)
            ->where('bookmarks.data.0.text', 'Saved post 1')
            ->where('bookmarks.data.19.id', $bookmarks[19]->id)
            ->where('bookmarks.data.19.text', 'Saved post 20')
            ->whereType('bookmarks.next_cursor', 'string');

        $nextCursor = $page->toArray()['props']['bookmarks']['next_cursor'];
    });

    expect($nextCursor)->toBeString()->not->toBeEmpty();

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['cursor' => $nextCursor]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('bookmarks.data', 5)
            ->where('bookmarks.data.0.id', $bookmarks[20]->id)
            ->where('bookmarks.data.0.text', 'Saved post 21')
            ->where('bookmarks.data.4.id', $bookmarks[24]->id)
            ->where('bookmarks.data.4.text', 'Saved post 25')
            ->where('bookmarks.next_cursor', null));
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

    $this->actingAs($user)
        ->delete(route('bookmarks.destroy', $bookmark))
        ->assertRedirectToRoute('bookmarks.index');

    expect($bookmark->fresh())->toBeNull();
});
