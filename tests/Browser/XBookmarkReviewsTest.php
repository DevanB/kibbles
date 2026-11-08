<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;

it('tags, keeps, and removes due bookmarks until the day is done', function (): void {
    $this->freezeTime();

    $user = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    XBookmark::factory()->recycle($user)->count(8)->reviewed()->create();
    XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Recent Review',
        'text' => 'Reviewed two days ago.',
        'reviewed_at' => now()->subDays(2),
        'first_seen_at' => now()->subDays(40),
    ]);
    XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Stale Bookmark',
        'text' => 'Last reviewed more than a month ago.',
        'reviewed_at' => now()->subDays(31),
        'first_seen_at' => now()->subDays(50),
    ]);
    $first = XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Taylor Otwell',
        'text' => 'First in the queue.',
        'first_seen_at' => now()->subDays(4),
    ]);
    $second = XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Nuno Maduro',
        'text' => 'Second in the queue.',
        'first_seen_at' => now()->subDays(3),
    ]);
    $third = XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Jess Archer',
        'text' => 'Third in the queue.',
        'first_seen_at' => now()->subDays(2),
    ]);
    $fourth = XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Adam Wathan',
        'text' => 'Fourth in the queue.',
        'first_seen_at' => now()->subDay(),
    ]);

    $this->actingAs($user);

    $page = visit(route('x-bookmark-reviews.index'));

    $page->assertSee('Review')
        ->assertSee('5 due · 2 left today')
        ->assertSee('Taylor Otwell')
        ->assertSee('First in the queue.')
        ->assertPresent('@keep-bookmark-button')
        ->assertPresent('@remove-review-button')
        ->screenshot(filename: 'bookmark-reviews-queue')
        ->assertNoJavaScriptErrors();

    $page->keys('@bookmark-reviews-page', 't')
        ->assertPresent('@tag-combobox-'.$first->id)
        ->click('@tag-option-'.$tag->id)
        ->assertPresent('@bookmark-tag-'.$first->id.'-'.$tag->id)
        ->assertSee('Taylor Otwell')
        ->assertNoJavaScriptErrors();

    expect($first->fresh()?->reviewed_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($first->tags()->whereKey($tag)->exists())->toBeTrue();

    $page->keys('@bookmark-reviews-page', 'k')
        ->assertSee('Nuno Maduro')
        ->assertSee('4 due · 1 left today')
        ->assertSee('Second in the queue.')
        ->assertNoJavaScriptErrors();

    $page->keys('@bookmark-reviews-page', 'x')
        ->assertSee('Jess Archer')
        ->assertSee('3 due · 1 left today')
        ->assertSee('Third in the queue.')
        ->assertNoJavaScriptErrors();

    expect($second->fresh())->toBeNull();

    $page->click('@keep-bookmark-button')
        ->assertSee('Done for today')
        ->assertSee('Come back tomorrow for more.')
        ->assertSee('2 due')
        ->assertPresent('@bookmark-reviews-done-today')
        ->screenshot(filename: 'bookmark-reviews-done-today')
        ->assertNoJavaScriptErrors();

    expect($fourth->fresh()?->reviewed_at)->toBeNull()
        ->and($third->fresh()?->reviewed_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('shows all caught up when no bookmark is due', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    visit(route('x-bookmark-reviews.index'))
        ->assertSee('All caught up')
        ->assertSee('Nothing is due for review.')
        ->assertPresent('@bookmark-reviews-empty')
        ->screenshot(filename: 'bookmark-reviews-caught-up')
        ->assertNoJavaScriptErrors();
});
