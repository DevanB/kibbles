<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\XBookmark;
use App\Models\XConnection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('renders the empty bookmarks page and the connect action', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('bookmarks.index'));

    $page->assertSee('Bookmarks')
        ->assertSee('Connect X to see saved posts')
        ->assertSee('Connect X')
        ->assertPresent('@connect-x-button')
        ->screenshot(filename: 'bookmarks-index-empty')
        ->assertNoJavaScriptErrors();
});

it('shows saved posts, media, expands long text, and removes a bookmark', function (): void {
    configureX();

    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->synced()->create([
        'username' => 'devan',
        'x_user_id' => '42',
    ]);
    $photo = XBookmark::factory()->recycle($user)->hotlinkedPhoto(
        'https://placehold.co/800x531/1d4ed8/ffffff/jpeg',
        800,
        531,
    )->create([
        'author_name' => 'Taylor Otwell',
        'author_username' => 'taylorotwell',
        'author_avatar_url' => 'https://avatars.githubusercontent.com/u/463230?s=96&v=4',
        'text' => 'Laravel 13 is out.',
        'first_seen_at' => now()->subMinutes(5),
    ]);
    $quoted = XBookmark::factory()->recycle($user)->quotedWithMedia()->create([
        'x_post_id' => 'quoted-100',
        'author_name' => 'Nuno Maduro',
        'author_username' => 'enunomaduro',
        'author_avatar_url' => 'https://avatars.githubusercontent.com/u/5457236?s=96&v=4',
        'text' => 'Pest 5 browser testing is the good stuff.',
        'first_seen_at' => now()->subHour(),
    ]);
    $video = XBookmark::factory()->recycle($user)->hotlinkedVideo()->create([
        'author_name' => 'X Engineering',
        'author_username' => 'xeng',
        'author_avatar_url' => 'https://avatars.githubusercontent.com/u/9919?s=96&v=4',
        'text' => 'A short clip from the lab.',
        'first_seen_at' => now()->subHours(3),
    ]);
    $long = XBookmark::factory()->recycle($user)->longText()->withoutMedia()->create([
        'author_name' => 'Devan',
        'author_username' => 'devan',
        'author_avatar_url' => 'https://avatars.githubusercontent.com/u/354652?s=96&v=4',
        'first_seen_at' => now()->subDay(),
    ]);
    XBookmark::factory()->recycle($user)->hotlinkedPhoto(
        'https://placehold.co/480x720/0f172a/ffffff/jpeg',
        480,
        720,
    )->create([
        'author_name' => 'Jess Archer',
        'author_username' => 'jessarchercodes',
        'author_avatar_url' => 'https://avatars.githubusercontent.com/u/1794495?s=96&v=4',
        'text' => 'Portrait tiles make the masonry columns actually stagger.',
        'first_seen_at' => now()->subHours(6),
    ]);
    XBookmark::factory()->recycle($user)->hotlinkedPhoto(
        'https://placehold.co/960x360/155e75/ffffff/jpeg',
        960,
        360,
    )->create([
        'author_name' => 'Canary',
        'author_username' => 'canary',
        'author_avatar_url' => 'https://avatars.githubusercontent.com/u/18133?s=96&v=4',
        'text' => 'Wide shot.',
        'first_seen_at' => now()->subHours(8),
    ]);
    XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Ada',
        'author_username' => 'ada',
        'author_avatar_url' => 'https://avatars.githubusercontent.com/u/1024025?s=96&v=4',
        'text' => "Two lines.\nThen a bit more copy so this card sits taller than a one-liner.",
        'first_seen_at' => now()->subHours(12),
    ]);

    Http::fake([
        'https://api.x.com/2/users/*/bookmarks/*' => Http::response(['data' => ['bookmarked' => false]]),
    ]);

    $this->actingAs($user);

    $page = visit(route('bookmarks.index'));

    $page->assertSee('Saved posts from @devan')
        ->assertSee('Synced')
        ->assertSee('ago')
        ->assertSee('Taylor Otwell')
        ->assertSee('@taylorotwell')
        ->assertSee('Laravel 13 is out.')
        ->assertSee('Saved')
        ->assertSee('Nuno Maduro')
        ->assertSee('The quoted post with a photo.')
        ->assertPresent('[aria-label="Open on X"]')
        ->assertPresent('[aria-label="Remove bookmark"]')
        ->assertAttribute('@open-on-x-'.$photo->id, 'href', $photo->url())
        ->assertAttribute('@open-quoted-'.$quoted->id, 'href', 'https://x.com/quoted/status/99')
        ->assertSee('Show full post')
        ->assertPresent('@bookmark-media-'.$photo->id)
        ->assertPresent('@bookmark-video-'.$video->id)
        ->assertPresent('@bookmark-media-'.$quoted->id.'-quoted')
        ->screenshot(filename: 'bookmarks-index-grid')
        ->assertNoJavaScriptErrors();

    $page->click('@show-full-post-'.$long->id)
        ->assertSee('Saved from the timeline.')
        ->assertSee('Show less')
        ->assertNoJavaScriptErrors();

    $page->click('[data-test="bookmark-'.$photo->id.'"] [aria-label="Remove bookmark"]')
        ->assertSee('Remove this bookmark?')
        ->assertSee('Laravel 13 is out.')
        ->screenshot(filename: 'bookmark-remove-modal')
        ->click('@cancel-remove-bookmark-button-'.$photo->id)
        ->assertSee('Laravel 13 is out.')
        ->assertNoJavaScriptErrors();

    expect($photo->fresh())->not->toBeNull();

    $page->click('[data-test="bookmark-'.$quoted->id.'"] [aria-label="Remove bookmark"]')
        ->click('@confirm-remove-bookmark-button-'.$quoted->id)
        ->assertSee('Bookmark removed.')
        ->assertPresent('@bookmark-media-'.$photo->id)
        ->assertPresent('@bookmark-video-'.$video->id)
        ->screenshot(filename: 'bookmarks-index-after-remove')
        ->assertNoJavaScriptErrors();

    expect($quoted->fresh())->toBeNull()
        ->and($photo->fresh())->not->toBeNull()
        ->and($video->fresh())->not->toBeNull()
        ->and($user->xBookmarks()->count())->toBe(6);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://api.x.com/2/users/42/bookmarks/quoted-100');
});
