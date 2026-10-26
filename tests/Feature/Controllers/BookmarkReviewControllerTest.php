<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\XBookmark;

it('queues the authenticated user unreviewed bookmarks oldest first', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $other = User::factory()->withoutTwoFactor()->create();
    XBookmark::factory()->recycle($user)->reviewed()->create([
        'text' => 'Already reviewed',
        'first_seen_at' => now()->subDays(3),
    ]);
    $older = XBookmark::factory()->recycle($user)->create([
        'text' => 'Oldest unreviewed',
        'first_seen_at' => now()->subDays(2),
    ]);
    XBookmark::factory()->recycle($user)->create([
        'text' => 'Newer unreviewed',
        'first_seen_at' => now()->subDay(),
    ]);
    XBookmark::factory()->recycle($other)->create([
        'text' => 'Someone else',
        'first_seen_at' => now()->subDays(4),
    ]);

    $this->actingAs($user)
        ->get(route('bookmark-reviews.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bookmark-reviews/index')
            ->where('bookmark.id', $older->id)
            ->where('bookmark.text', 'Oldest unreviewed')
            ->where('remaining', 2)
            ->where('total', 3));
});

it('rejects storing a review for another user bookmark', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($owner)->create();

    $this->actingAs($intruder)
        ->post(route('bookmark-reviews.store'), ['bookmark_id' => $bookmark->id])
        ->assertForbidden();

    expect($bookmark->fresh()?->reviewed_at)->toBeNull();
});
