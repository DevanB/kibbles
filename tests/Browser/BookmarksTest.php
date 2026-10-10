<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\XBookmark;
use App\Models\XConnection;
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

it('shows saved posts, expands long text, and removes a bookmark', function (): void {
    configureX();

    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->synced()->create(['username' => 'devan']);
    $photo = XBookmark::factory()->recycle($user)->create([
        'author_name' => 'Taylor Otwell',
        'author_username' => 'taylorotwell',
        'text' => 'Laravel 13 is out.',
        'first_seen_at' => now(),
    ]);
    $quoted = XBookmark::factory()->recycle($user)->quoted()->create([
        'author_name' => 'Nuno Maduro',
        'author_username' => 'enunomaduro',
        'text' => 'Pest 5 browser testing is the good stuff.',
        'first_seen_at' => now()->subHour(),
    ]);
    $long = XBookmark::factory()->recycle($user)->longText()->create([
        'author_name' => 'Devan',
        'author_username' => 'devan',
        'first_seen_at' => now()->subDay(),
    ]);

    Http::fake([
        'https://api.x.com/2/users/*/bookmarks/*' => Http::response(['data' => ['bookmarked' => false]]),
    ]);

    $this->actingAs($user);

    $page = visit(route('bookmarks.index'));

    $page->assertSee('Saved posts from @devan')
        ->assertSee('Taylor Otwell')
        ->assertSee('Laravel 13 is out.')
        ->assertSee('Nuno Maduro')
        ->assertSee('The quoted post.')
        ->assertSee('Open on X')
        ->assertSee('Show full post')
        ->screenshot(filename: 'bookmarks-index-grid')
        ->assertNoJavaScriptErrors();

    $page->click('@show-full-post-'.$long->id)
        ->assertSee('Saved from the timeline.')
        ->assertSee('Show less')
        ->assertNoJavaScriptErrors();

    $page->click('@remove-bookmark-button-'.$photo->id)
        ->assertSee('Remove this bookmark?')
        ->assertSee('Laravel 13 is out.')
        ->screenshot(filename: 'bookmark-remove-modal')
        ->click('@cancel-remove-bookmark-button-'.$photo->id)
        ->assertSee('Laravel 13 is out.')
        ->assertNoJavaScriptErrors();

    expect($photo->fresh())->not->toBeNull();

    $page->click('@remove-bookmark-button-'.$quoted->id)
        ->click('@confirm-remove-bookmark-button-'.$quoted->id)
        ->assertSee('Bookmark removed.')
        ->assertDontSee('Pest 5 browser testing is the good stuff.')
        ->screenshot(filename: 'bookmarks-index-after-remove')
        ->assertNoJavaScriptErrors();

    expect($quoted->fresh())->toBeNull()
        ->and($photo->fresh())->not->toBeNull();
});
