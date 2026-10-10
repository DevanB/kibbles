<?php

declare(strict_types=1);

use App\Enums\XMediaType;
use App\Exceptions\XClientException;
use App\Models\XConnection;
use App\Services\XClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('parses photos, videos, gifs, and quoted posts from X', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::response(xBookmarksPayload(
            [
                xTweet('1', [
                    'attachments' => ['media_keys' => ['3_1', '13_1', '16_1', 'missing']],
                    'referenced_tweets' => [
                        ['type' => 'retweeted', 'id' => '8'],
                        ['type' => 'quoted', 'id' => '9'],
                    ],
                ]),
                xTweet(''),
            ],
            [
                'users' => [
                    xAuthor('author-1', 'alice', 'Alice'),
                    xAuthor('quoted-author', 'quoted', 'Quoted Author'),
                ],
                'tweets' => [
                    xTweet('9', [
                        'author_id' => 'quoted-author',
                        'text' => 'Quoted text',
                        'attachments' => ['media_keys' => ['3_2']],
                    ]),
                ],
                'media' => [
                    xPhotoMedia('3_1'),
                    xVideoMedia('13_1'),
                    [
                        'media_key' => '16_1',
                        'type' => 'animated_gif',
                        'preview_image_url' => 'https://pbs.twimg.com/tweet_video_thumb/gif.jpg',
                        'width' => 400,
                        'height' => 300,
                        'variants' => [
                            ['content_type' => 'video/mp4', 'url' => 'https://video.twimg.com/gif.mp4'],
                        ],
                    ],
                    xPhotoMedia('3_2'),
                ],
            ],
            'next-page',
        )),
    ]);

    $page = resolve(XClient::class)->bookmarks($connection, 'cursor-1');

    expect($page->nextToken)->toBe('next-page')
        ->and($page->bookmarks)->toHaveCount(2)
        ->and($page->bookmarks[0]->authorUsername)->toBe('alice')
        ->and($page->bookmarks[0]->media)->toHaveCount(3)
        ->and($page->bookmarks[0]->media[0]->type)->toBe(XMediaType::Photo)
        ->and($page->bookmarks[0]->media[1]->type)->toBe(XMediaType::Video)
        ->and($page->bookmarks[0]->media[1]->mp4Url)->toBe('https://video.twimg.com/high.mp4')
        ->and($page->bookmarks[0]->media[2]->type)->toBe(XMediaType::Gif)
        ->and($page->bookmarks[0]->media[2]->mp4Url)->toBe('https://video.twimg.com/gif.mp4')
        ->and($page->bookmarks[0]->quotedPost?->url)->toBe('https://x.com/quoted/status/9')
        ->and($page->bookmarks[0]->quotedPost?->text)->toBe('Quoted text')
        ->and($page->bookmarks[1]->xPostId)->toBeEmpty();

    Http::assertSent(fn (Request $request): bool => ($request->data()['pagination_token'] ?? null) === 'cursor-1'
        && array_key_exists('expansions', $request->data()));
});

it('requests bookmark ids without expansions or extra fields', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::response(xBookmarksPayload(
            [xTweet('1'), xTweet(''), xTweet('2')],
            nextToken: 'next-page',
        )),
    ]);

    $page = resolve(XClient::class)->bookmarkIds($connection, 'cursor-1');

    expect($page->ids)->toBe(['1', '2'])
        ->and($page->nextToken)->toBe('next-page');

    Http::assertSent(fn (Request $request): bool => ($request->data()['pagination_token'] ?? null) === 'cursor-1'
        && ($request->data()['max_results'] ?? null) === 100
        && ! array_key_exists('expansions', $request->data())
        && ! array_key_exists('tweet.fields', $request->data())
        && ! array_key_exists('user.fields', $request->data())
        && ! array_key_exists('media.fields', $request->data()));
});

it('looks up tweets by id with expansions', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/tweets*' => Http::response(xBookmarksPayload(
            [xTweet('9', ['text' => 'Looked up'])],
            ['users' => [xAuthor('author-9', 'lookup', 'Lookup')]],
        )),
    ]);

    $tweets = resolve(XClient::class)->tweets($connection, ['9']);

    expect($tweets)->toHaveCount(1)
        ->and($tweets[0]->text)->toBe('Looked up')
        ->and($tweets[0]->authorUsername)->toBe('lookup');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/tweets')
        && ($request->data()['ids'] ?? null) === '9'
        && array_key_exists('expansions', $request->data()));
});

it('returns no tweets when the id list is empty', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake();

    expect(resolve(XClient::class)->tweets($connection, []))->toBeEmpty();

    Http::assertNothingSent();
});

it('uses now when X omits created_at and skips a quoted post that was not included', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::response(xBookmarksPayload(
            [
                xTweet('1', [
                    'created_at' => null,
                    'author_id' => 'missing',
                    'referenced_tweets' => [['type' => 'quoted', 'id' => 'missing-quote']],
                ]),
            ],
        )),
    ]);

    $page = resolve(XClient::class)->bookmarks($connection);

    expect($page->bookmarks[0]->postedAt->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($page->bookmarks[0]->authorUsername)->toBeEmpty()
        ->and($page->bookmarks[0]->quotedPost)->toBeNull();
});

it('deletes a bookmark on X', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks/100' => Http::response(['data' => ['bookmarked' => false]]),
    ]);

    expect(resolve(XClient::class)->deleteBookmark($connection, '100'))->toBeTrue();
});

it('returns false when X refuses to delete a bookmark', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks/100' => Http::response(['title' => 'forbidden'], 403),
    ]);

    expect(resolve(XClient::class)->deleteBookmark($connection, '100'))->toBeFalse();
});

it('fails to refresh when OAuth credentials are missing', function (): void {
    config(['services.x.client_id' => null, 'services.x.client_secret' => null]);

    $connection = XConnection::factory()->expired()->create();

    expect(fn () => resolve(XClient::class)->refresh($connection))
        ->toThrow(XClientException::class, 'X OAuth credentials are not configured.');
});

it('fails to refresh when X returns an incomplete token pair', function (): void {
    configureX();

    $connection = XConnection::factory()->expired()->create();

    Http::fake([
        'https://api.x.com/2/oauth2/token' => Http::response([
            'access_token' => 'only-access',
        ]),
    ]);

    expect(fn () => resolve(XClient::class)->refresh($connection))
        ->toThrow(XClientException::class, 'X rotated an incomplete token pair.');
});

it('fails to refresh when the token endpoint errors', function (): void {
    configureX();

    $connection = XConnection::factory()->expired()->create();

    Http::fake([
        'https://api.x.com/2/oauth2/token' => Http::response(['error' => 'invalid'], 400),
    ]);

    expect(fn () => resolve(XClient::class)->refresh($connection))
        ->toThrow(XClientException::class, 'Unable to refresh the X access token.');
});

it('fails to refresh when the token endpoint cannot be reached', function (): void {
    configureX();

    $connection = XConnection::factory()->expired()->create();

    Http::fake([
        'https://api.x.com/2/oauth2/token' => Http::failedConnection(),
    ]);

    expect(fn () => resolve(XClient::class)->refresh($connection))
        ->toThrow(XClientException::class, 'Unable to refresh the X access token.');
});

it('fails when the bookmarks endpoint cannot be reached', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::failedConnection(),
    ]);

    expect(fn () => resolve(XClient::class)->bookmarks($connection))
        ->toThrow(XClientException::class, 'Unable to reach the X API.');
});

it('defaults the rotated token lifetime when X omits expires_in', function (): void {
    configureX();

    $connection = XConnection::factory()->expired()->create();

    Http::fake([
        'https://api.x.com/2/oauth2/token' => Http::response([
            'access_token' => 'rotated-access',
            'refresh_token' => 'rotated-refresh',
            'expires_in' => '7200',
        ]),
    ]);

    resolve(XClient::class)->refresh($connection);

    expect($connection->refresh()->access_token)->toBe('rotated-access')
        ->and($connection->expires_at->toDateTimeString())->toBe(now()->addSeconds(7200)->toDateTimeString());
});

it('falls back to preview or mp4 urls and ignores unusable media variants', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::response(xBookmarksPayload(
            [
                xTweet('1', [
                    'attachments' => ['media_keys' => ['13_1', '', 7, 'missing']],
                ]),
            ],
            [
                'users' => [xAuthor('author-1')],
                'media' => [
                    [
                        'media_key' => '13_1',
                        'type' => 'video',
                        'preview_image_url' => 'https://pbs.twimg.com/ext_tw_video_thumb/fallback.jpg',
                        'width' => '1280',
                        'height' => '720',
                        'variants' => [
                            'not-an-object',
                            ['content_type' => 'application/x-mpegURL', 'url' => 'https://video.twimg.com/playlist.m3u8'],
                            ['content_type' => 'video/mp4'],
                            ['content_type' => 'video/mp4', 'url' => 'https://video.twimg.com/plain.mp4', 'bit_rate' => 'fast'],
                        ],
                    ],
                    ['type' => 'photo'],
                ],
            ],
        )),
    ]);

    $page = resolve(XClient::class)->bookmarks($connection);

    expect($page->bookmarks[0]->media)->toHaveCount(1)
        ->and($page->bookmarks[0]->media[0]->type)->toBe(XMediaType::Video)
        ->and($page->bookmarks[0]->media[0]->url)->toBe('https://pbs.twimg.com/ext_tw_video_thumb/fallback.jpg')
        ->and($page->bookmarks[0]->media[0]->mp4Url)->toBe('https://video.twimg.com/plain.mp4')
        ->and($page->bookmarks[0]->media[0]->width)->toBeNull()
        ->and($page->bookmarks[0]->media[0]->height)->toBeNull();
});

it('keeps posts with no media keys and uses an empty url when X omits every media url', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::response(xBookmarksPayload(
            [
                xTweet('1'),
                xTweet('2', [
                    'attachments' => ['media_keys' => ['3_empty']],
                ]),
            ],
            [
                'users' => [xAuthor('author-1'), xAuthor('author-2')],
                'media' => [
                    [
                        'media_key' => '3_empty',
                        'type' => 'photo',
                    ],
                ],
            ],
        )),
    ]);

    $page = resolve(XClient::class)->bookmarks($connection);

    expect($page->bookmarks[0]->media)->toBeEmpty()
        ->and($page->bookmarks[1]->media[0]->url)->toBeEmpty()
        ->and($page->bookmarks[1]->media[0]->mp4Url)->toBeNull();
});

it('treats a non-object X payload as an empty page', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/users/42/bookmarks*' => Http::response([
            'data' => 'nope',
            'includes' => 'nope',
        ]),
    ]);

    $page = resolve(XClient::class)->bookmarks($connection);

    expect($page->bookmarks)->toBeEmpty()
        ->and($page->nextToken)->toBeNull();
});

it('fails when the unauthorized retry cannot reach X', function (): void {
    configureX();

    $connection = XConnection::factory()->create(['x_user_id' => '42']);

    Http::fake([
        'https://api.x.com/2/oauth2/token' => Http::response([
            'access_token' => 'rotated-access',
            'refresh_token' => 'rotated-refresh',
            'expires_in' => 7200,
        ]),
        'https://api.x.com/2/users/42/bookmarks*' => Http::sequence()
            ->push(['title' => 'unauthorized'], 401)
            ->pushFailedConnection(),
    ]);

    expect(fn () => resolve(XClient::class)->bookmarks($connection))
        ->toThrow(XClientException::class, 'Unable to reach the X API.');
});
