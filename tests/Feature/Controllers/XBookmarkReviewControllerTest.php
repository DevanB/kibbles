<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\XBookmark;

it('reviews never-reviewed bookmarks before stale ones and catches up when nothing is due', function (): void {
    $this->freezeTime();

    $user = User::factory()->withoutTwoFactor()->create();
    $other = User::factory()->withoutTwoFactor()->create();

    XBookmark::factory()->recycle($other)->create([
        'reviewed_at' => null,
        'first_seen_at' => now()->subDays(120),
    ]);
    XBookmark::factory()->recycle($user)->create([
        'reviewed_at' => now()->subDays(30),
        'first_seen_at' => now()->subDays(80),
    ]);
    $pastTheWindow = XBookmark::factory()->recycle($user)->create([
        'reviewed_at' => now()->subDays(30)->subSecond(),
        'first_seen_at' => now()->subDays(70),
    ]);
    $olderStamp = XBookmark::factory()->recycle($user)->create([
        'reviewed_at' => now()->subDays(45),
        'first_seen_at' => now()->subDays(10),
    ]);
    $syncedEarlier = XBookmark::factory()->recycle($user)->create([
        'reviewed_at' => null,
        'first_seen_at' => now()->subDays(2),
    ]);
    $syncedLater = XBookmark::factory()->recycle($user)->create([
        'reviewed_at' => null,
        'first_seen_at' => now()->subDay(),
    ]);

    $queue = [
        [$syncedEarlier, 4],
        [$syncedLater, 3],
        [$olderStamp, 2],
        [$pastTheWindow, 1],
    ];

    $this->actingAs($user);

    foreach ($queue as [$bookmark, $due]) {
        $this->get(route('x-bookmark-reviews.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('x-bookmark-reviews/index')
                ->where('status', 'reviewing')
                ->where('bookmark.id', $bookmark->id)
                ->where('due', $due));

        $this->post(route('x-bookmark-reviews.store'), [
            'bookmark_id' => $bookmark->id,
        ])->assertRedirectToRoute('x-bookmark-reviews.index');

        expect($bookmark->refresh()->reviewed_at?->toDateTimeString())->toBe(now()->toDateTimeString());
    }

    $this->get(route('x-bookmark-reviews.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('x-bookmark-reviews/index')
            ->where('status', 'caught-up')
            ->missing('bookmark')
            ->missing('due')
            ->missing('leftToday'));
});

it('does not count reviews stamped before Eastern midnight toward the next Eastern day', function (string $now, string $stampedAt): void {
    $this->travelTo($now);

    $user = User::factory()->withoutTwoFactor()->create();
    XBookmark::factory()->recycle($user)->count(10)->create([
        'reviewed_at' => $stampedAt,
    ]);
    $due = XBookmark::factory()->recycle($user)->create([
        'reviewed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('x-bookmark-reviews.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('x-bookmark-reviews/index')
            ->where('status', 'reviewing')
            ->where('bookmark.id', $due->id)
            ->where('due', 1)
            ->where('leftToday', 10));
})->with([
    'EST' => ['2026-01-15 05:00:01', '2026-01-15 04:59:59'],
    'EDT' => ['2026-07-15 04:00:01', '2026-07-15 03:59:59'],
]);

it('counts reviews stamped after Eastern midnight toward that Eastern day', function (string $now, string $stampedAt): void {
    $this->travelTo($now);

    $user = User::factory()->withoutTwoFactor()->create();
    XBookmark::factory()->recycle($user)->count(10)->create([
        'reviewed_at' => $stampedAt,
    ]);
    XBookmark::factory()->recycle($user)->create([
        'reviewed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('x-bookmark-reviews.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('x-bookmark-reviews/index')
            ->where('status', 'done-for-today')
            ->where('due', 1)
            ->missing('bookmark')
            ->missing('leftToday'));
})->with([
    'EST' => ['2026-01-15 05:00:01', '2026-01-15 05:00:01'],
    'EDT' => ['2026-07-15 04:00:01', '2026-07-15 04:00:01'],
]);

it('stops for the day after ten reviews while a bookmark is still due', function (): void {
    $this->freezeTime();

    $user = User::factory()->withoutTwoFactor()->create();
    XBookmark::factory()->recycle($user)->count(10)->reviewed()->create();
    XBookmark::factory()->recycle($user)->create([
        'reviewed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('x-bookmark-reviews.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('x-bookmark-reviews/index')
            ->where('status', 'done-for-today')
            ->where('due', 1)
            ->missing('bookmark')
            ->missing('leftToday'));
});

it('rejects storing a review for another user bookmark', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $bookmark = XBookmark::factory()->recycle($owner)->create();

    $this->actingAs($intruder)
        ->post(route('x-bookmark-reviews.store'), ['bookmark_id' => $bookmark->id])
        ->assertForbidden();

    expect($bookmark->fresh()?->reviewed_at)->toBeNull();
});
