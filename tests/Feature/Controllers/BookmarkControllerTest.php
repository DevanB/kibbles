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
            ->has('bookmarks', 1)
            ->has('bookmarks.0', fn ($bookmark) => $bookmark
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
            ->where('bookmarks.0.id', $newer->id)
            ->where('bookmarks.1.id', $older->id));
});

it('renders the empty index without an X connection', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bookmarks/index')
            ->has('bookmarks', 0)
            ->where('xConnection', null));
});

it('shows the remove confirmation modal for an owned bookmark', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => 'true'])
        ->get(route('bookmarks.show', $bookmark))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bookmarks/show')
            ->where('bookmark.id', $bookmark->id)
            ->where('bookmark.text', $bookmark->text));
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

it('removes the bookmark on X and locally', function (): void {
    configureX();

    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->create(['x_user_id' => '42']);
    $bookmark = XBookmark::factory()->recycle($user)->create(['x_post_id' => '100']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks/100' => Http::response(['data' => ['bookmarked' => false]]),
    ]);

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->delete(route('bookmarks.destroy', $bookmark))
        ->assertRedirectToRoute('bookmarks.index');

    expect($bookmark->fresh())->toBeNull();
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
        ->assertRedirectToRoute('bookmarks.index');

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
