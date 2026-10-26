<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;

it('keeps, removes, and tags through the review queue until it is empty', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $first = XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Taylor Otwell',
        'text' => 'First in the queue.',
        'first_seen_at' => now()->subDays(2),
    ]);
    $second = XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Nuno Maduro',
        'text' => 'Second in the queue.',
        'first_seen_at' => now()->subDay(),
    ]);
    $third = XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Jess Archer',
        'text' => 'Third in the queue.',
        'first_seen_at' => now()->subHour(),
    ]);

    $this->actingAs($user);

    $page = visit(route('bookmark-reviews.index'));

    $page->assertSee('Review')
        ->assertSee('3 left · 3 bookmarks')
        ->assertSee('Taylor Otwell')
        ->assertSee('First in the queue.')
        ->assertPresent('@sidebar-x-review')
        ->assertPresent('@keep-bookmark-button')
        ->assertPresent('@remove-review-button')
        ->screenshot(filename: 'bookmark-reviews-queue')
        ->assertNoJavaScriptErrors();

    $page->keys('@bookmark-reviews-page', 't')
        ->assertPresent('@tag-combobox-'.$first->id)
        ->screenshot(filename: 'bookmark-reviews-tag-combobox')
        ->click('@tag-option-'.$tag->id)
        ->assertPresent('@bookmark-tag-'.$first->id.'-'.$tag->id)
        ->assertNoJavaScriptErrors();

    expect($first->tags()->whereKey($tag)->exists())->toBeTrue();

    $page->keys('@bookmark-reviews-page', 'k')
        ->assertSee('Nuno Maduro')
        ->assertSee('2 left · 3 bookmarks')
        ->assertSee('Second in the queue.')
        ->assertNoJavaScriptErrors();

    expect($first->fresh()?->reviewed_at)->not->toBeNull()
        ->and($second->fresh())->not->toBeNull();

    $page->keys('@bookmark-reviews-page', 'x')
        ->assertSee('Jess Archer')
        ->assertSee('1 left · 2 bookmarks')
        ->assertSee('Third in the queue.')
        ->assertNoJavaScriptErrors();

    expect($second->fresh())->toBeNull();

    $page->click('@keep-bookmark-button')
        ->assertSee('All caught up')
        ->assertPresent('@bookmark-reviews-empty')
        ->screenshot(filename: 'bookmark-reviews-caught-up')
        ->assertNoJavaScriptErrors();

    expect($third->fresh()?->reviewed_at)->not->toBeNull()
        ->and($user->xBookmarks()->count())->toBe(2);
});
