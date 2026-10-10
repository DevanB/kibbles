<?php

declare(strict_types=1);

use App\Actions\DispatchXBookmarkSyncs;
use App\Actions\SyncXBookmarks;
use App\Exceptions\XClientException;
use App\Jobs\SyncXBookmarks as SyncXBookmarksJob;
use App\Models\User;
use App\Models\XBookmark;
use App\Models\XConnection;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('upserts bookmarks from X and records first_seen_at', function (): void {
    configureX();

    $user = User::factory()->create();
    $connection = XConnection::factory()->recycle($user)->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::response(xBookmarksPayload(
            [xTweet('100', ['attachments' => ['media_keys' => ['3_1']]])],
            [
                'users' => [xAuthor('author-100')],
                'media' => [xPhotoMedia()],
            ],
        )),
    ]);

    resolve(SyncXBookmarks::class)->handle($user);

    $bookmark = $user->xBookmarks()->first();

    expect($bookmark)->not->toBeNull()
        ->and($bookmark->x_post_id)->toBe('100')
        ->and($bookmark->author_username)->toBe('devan')
        ->and($bookmark->first_seen_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($bookmark->media[0]->url)->toBe('https://pbs.twimg.com/media/demo.jpg')
        ->and($connection->refresh()->last_synced_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($connection->last_full_synced_at)->toBeNull();
});

it('updates an existing bookmark without changing first_seen_at', function (): void {
    configureX();

    $user = User::factory()->create();
    XConnection::factory()->recycle($user)->create(['x_user_id' => '42']);
    $existing = XBookmark::factory()->recycle($user)->create([
        'x_post_id' => '100',
        'text' => 'Old text',
        'first_seen_at' => now()->subDay(),
    ]);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::response(xBookmarksPayload(
            [xTweet('100', ['text' => 'Updated text'])],
            ['users' => [xAuthor('author-100')]],
        )),
    ]);

    resolve(SyncXBookmarks::class)->handle($user, true);

    expect($existing->refresh()->text)->toBe('Updated text')
        ->and($existing->first_seen_at->toDateTimeString())->toBe(now()->subDay()->toDateTimeString());
});

it('stops an incremental sync at the first already-known post', function (): void {
    configureX();

    $user = User::factory()->create();
    XConnection::factory()->recycle($user)->create(['x_user_id' => '42']);
    XBookmark::factory()->recycle($user)->create(['x_post_id' => 'known']);

    Http::fake(function (Request $request) {
        expect($request->data()['pagination_token'] ?? null)->toBeNull();

        return Http::response(xBookmarksPayload(
            [
                xTweet('new'),
                xTweet('known'),
                xTweet('older-should-not-sync'),
            ],
            ['users' => [xAuthor('author-new'), xAuthor('author-known'), xAuthor('author-older-should-not-sync')]],
            'page-2',
        ));
    });

    resolve(SyncXBookmarks::class)->handle($user);

    expect($user->xBookmarks()->pluck('x_post_id')->all())->toEqualCanonicalizing(['new', 'known'])
        ->and($user->xBookmarks()->where('x_post_id', 'older-should-not-sync')->exists())->toBeFalse();

    Http::assertSentCount(1);
});

it('pages a full sync and deletes local bookmarks X no longer returns', function (): void {
    configureX();

    $user = User::factory()->create();
    $connection = XConnection::factory()->recycle($user)->create(['x_user_id' => '42']);
    XBookmark::factory()->recycle($user)->create(['x_post_id' => 'keep']);
    XBookmark::factory()->recycle($user)->create(['x_post_id' => 'gone']);

    Http::fake(function (Request $request) {
        if (($request->data()['pagination_token'] ?? null) === 'page-2') {
            return Http::response(xBookmarksPayload(
                [xTweet('keep')],
                ['users' => [xAuthor('author-keep')]],
            ));
        }

        return Http::response(xBookmarksPayload(
            [xTweet('fresh')],
            ['users' => [xAuthor('author-fresh')]],
            'page-2',
        ));
    });

    resolve(SyncXBookmarks::class)->handle($user, true);

    expect($user->xBookmarks()->pluck('x_post_id')->all())->toEqualCanonicalizing(['fresh', 'keep'])
        ->and($connection->refresh()->last_full_synced_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('does not delete local bookmarks when a full sync errors before the window completes', function (): void {
    configureX();

    $user = User::factory()->create();
    XConnection::factory()->recycle($user)->create(['x_user_id' => '42']);
    XBookmark::factory()->recycle($user)->create(['x_post_id' => 'local']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::sequence()
            ->push(xBookmarksPayload([xTweet('fresh')], ['users' => [xAuthor('author-fresh')]], 'page-2'))
            ->push(['title' => 'upstream error'], 500),
    ]);

    expect(fn () => resolve(SyncXBookmarks::class)->handle($user, true))
        ->toThrow(XClientException::class)
        ->and($user->xBookmarks()->pluck('x_post_id')->all())->toEqualCanonicalizing(['fresh', 'local'])
        ->and($user->xConnection?->last_full_synced_at)->toBeNull();
});

it('refreshes and persists a rotated token before calling X', function (): void {
    configureX();

    $user = User::factory()->create();
    $connection = XConnection::factory()->expired()->recycle($user)->create([
        'x_user_id' => '42',
        'access_token' => 'stale-access',
        'refresh_token' => 'stale-refresh',
    ]);

    Http::fake([
        'https://api.x.com/2/oauth2/token' => Http::response([
            'access_token' => 'rotated-access',
            'refresh_token' => 'rotated-refresh',
            'expires_in' => 7200,
        ]),
        'https://api.x.com/2/users/42/bookmarks*' => Http::response(xBookmarksPayload(
            [xTweet('100')],
            ['users' => [xAuthor('author-100')]],
        )),
    ]);

    resolve(SyncXBookmarks::class)->handle($user);

    expect($connection->refresh()->access_token)->toBe('rotated-access')
        ->and($connection->refresh_token)->toBe('rotated-refresh');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.x.com/2/oauth2/token'
        && $request['refresh_token'] === 'stale-refresh');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/users/42/bookmarks')
        && $request->hasHeader('Authorization', 'Bearer rotated-access'));
});

it('retries a bookmarks request after an unauthorized response rotates the token', function (): void {
    configureX();

    $user = User::factory()->create();
    XConnection::factory()->recycle($user)->create([
        'x_user_id' => '42',
        'access_token' => 'stale-access',
        'refresh_token' => 'stale-refresh',
    ]);

    Http::fake([
        'https://api.x.com/2/oauth2/token' => Http::response([
            'access_token' => 'rotated-access',
            'refresh_token' => 'rotated-refresh',
            'expires_in' => 1800,
        ]),
        'https://api.x.com/2/users/42/bookmarks*' => Http::sequence()
            ->push(['title' => 'unauthorized'], 401)
            ->push(xBookmarksPayload([xTweet('100')], ['users' => [xAuthor('author-100')]])),
    ]);

    resolve(SyncXBookmarks::class)->handle($user);

    expect($user->xBookmarks()->where('x_post_id', '100')->exists())->toBeTrue();
});

it('skips bookmarks that have no X post id', function (): void {
    configureX();

    $user = User::factory()->create();
    XConnection::factory()->recycle($user)->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::response(xBookmarksPayload(
            [xTweet('')],
            ['users' => [xAuthor('author-')]],
        )),
    ]);

    resolve(SyncXBookmarks::class)->handle($user);

    expect($user->xBookmarks()->count())->toBe(0)
        ->and($user->xConnection?->last_synced_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('skips work when the user has no X connection', function (): void {
    $user = User::factory()->create();

    resolve(SyncXBookmarks::class)->handle($user);

    expect($user->xBookmarks()->count())->toBe(0);
});

it('runs the queued job for an existing user', function (): void {
    configureX();

    $user = User::factory()->create();
    XConnection::factory()->recycle($user)->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::response(xBookmarksPayload(
            [xTweet('100')],
            ['users' => [xAuthor('author-100')]],
        )),
    ]);

    new SyncXBookmarksJob($user->id)->handle(resolve(SyncXBookmarks::class));

    expect($user->xBookmarks()->where('x_post_id', '100')->exists())->toBeTrue();
});

it('skips work when the queued user no longer exists', function (): void {
    new SyncXBookmarksJob('00000000-0000-0000-0000-000000000099')->handle(resolve(SyncXBookmarks::class));

    expect(XBookmark::query()->count())->toBe(0);
});

it('dispatches a unique incremental job per user', function (): void {
    $job = new SyncXBookmarksJob('user-1', false);

    expect($job->uniqueId())->toBe('user-1:incremental')
        ->and(new SyncXBookmarksJob('user-1', true)->uniqueId())->toBe('user-1:full');
});

it('dispatches scheduled syncs for each connection', function (): void {
    Queue::fake([SyncXBookmarksJob::class]);

    $first = XConnection::factory()->create();
    $second = XConnection::factory()->create();

    resolve(DispatchXBookmarkSyncs::class)->handle();
    resolve(DispatchXBookmarkSyncs::class)->handle(true);

    Queue::assertPushed(SyncXBookmarksJob::class, 4);
    Queue::assertPushed(SyncXBookmarksJob::class, fn (SyncXBookmarksJob $job): bool => $job->userId === $first->user_id && $job->full === false);
    Queue::assertPushed(SyncXBookmarksJob::class, fn (SyncXBookmarksJob $job): bool => $job->userId === $second->user_id && $job->full);
});

it('schedules incremental bookmark syncs every six hours and a weekly full sync', function (): void {
    $events = collect(resolve(Schedule::class)->events());

    $incremental = $events->first(fn ($event): bool => $event->description === 'x-bookmarks-incremental');
    $full = $events->first(fn ($event): bool => $event->description === 'x-bookmarks-full');

    expect($incremental)->not->toBeNull()
        ->and($incremental?->expression)->toBe('0 */6 * * *')
        ->and($full)->not->toBeNull()
        ->and($full?->expression)->toBe('0 0 * * 0');
});
