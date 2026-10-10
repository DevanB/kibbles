<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as XOAuthUser;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

const HADES_RAWG_ID = 274755;
const HADES_IMAGE_URL = 'https://media.rawg.io/media/games/1f4/1f47a270b8f241e4676b14d39ec620f7.jpg';
const HADES_DESCRIPTION = 'Defy the god of the dead as you hack and slash out of the Underworld.';

/**
 * @return array{id: int, name: string, background_image: string, description: string, description_raw: string}
 */
function hadesDetailPayload(): array
{
    return [
        'id' => HADES_RAWG_ID,
        'name' => 'Hades',
        'background_image' => HADES_IMAGE_URL,
        'description' => '<p>Defy the god of the dead.</p>',
        'description_raw' => HADES_DESCRIPTION,
    ];
}

/**
 * @return array{count: int, results: list<array{id: int, name: string, released: string, background_image: string}>}
 */
function hadesSearchPayload(): array
{
    return [
        'count' => 1,
        'results' => [
            [
                'id' => HADES_RAWG_ID,
                'name' => 'Hades',
                'released' => '2020-09-17',
                'background_image' => HADES_IMAGE_URL,
            ],
        ],
    ];
}

function fakeHadesCatalog(): void
{
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games/274755*' => Http::response(hadesDetailPayload()),
        'https://api.rawg.io/api/games*' => Http::response(hadesSearchPayload()),
    ]);
}

function something(): void
{
    // ..
}

function configureX(): void
{
    config([
        'services.x.client_id' => 'test-client-id',
        'services.x.client_secret' => 'test-client-secret',
        'services.x.redirect' => '/x-connection/callback',
    ]);
}

function fakeXDriver(SocialiteUser|Throwable|null $user = null): void
{
    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('setScopes')->andReturnSelf();
    $provider->shouldReceive('redirectUrl')->with(url('/x-connection/callback'))->andReturnSelf();
    $provider->shouldReceive('redirect')->andReturn(new RedirectResponse(
        'https://x.com/i/oauth2/authorize?client_id=test-client-id&redirect_uri='.urlencode(url('/x-connection/callback')).'&scope=tweet.read%20users.read%20bookmark.read%20bookmark.write%20offline.access',
    ));

    if ($user instanceof Throwable) {
        $provider->shouldReceive('user')->andThrow($user);
    } else {
        $provider->shouldReceive('user')->andReturn($user ?? XOAuthUser::fake());
    }

    Socialite::shouldReceive('driver')->with('x')->andReturn($provider);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function xTweet(string $id, array $overrides = []): array
{
    return [
        'id' => $id,
        'text' => 'Hello from '.$id,
        'author_id' => 'author-'.$id,
        'created_at' => '2026-09-23T12:00:00.000Z',
        ...$overrides,
    ];
}

/**
 * @return array{id: string, name: string, username: string, profile_image_url: string}
 */
function xAuthor(string $id, string $username = 'devan', string $name = 'Devan'): array
{
    return [
        'id' => $id,
        'name' => $name,
        'username' => $username,
        'profile_image_url' => 'https://pbs.twimg.com/profile_images/1/avatar.jpg',
    ];
}

/**
 * @return array{media_key: string, type: string, url: string, width: int, height: int}
 */
function xPhotoMedia(string $key = '3_1'): array
{
    return [
        'media_key' => $key,
        'type' => 'photo',
        'url' => 'https://pbs.twimg.com/media/demo.jpg',
        'width' => 1200,
        'height' => 800,
    ];
}

/**
 * @return array{media_key: string, type: string, preview_image_url: string, width: int, height: int, variants: list<array{content_type: string, url: string, bit_rate?: int}>}
 */
function xVideoMedia(string $key = '13_1'): array
{
    return [
        'media_key' => $key,
        'type' => 'video',
        'preview_image_url' => 'https://pbs.twimg.com/ext_tw_video_thumb/demo.jpg',
        'width' => 1280,
        'height' => 720,
        'variants' => [
            ['content_type' => 'application/x-mpegURL', 'url' => 'https://video.twimg.com/playlist.m3u8'],
            ['content_type' => 'video/mp4', 'url' => 'https://video.twimg.com/low.mp4', 'bit_rate' => 256000],
            ['content_type' => 'video/mp4', 'url' => 'https://video.twimg.com/high.mp4', 'bit_rate' => 2176000],
        ],
    ];
}

/**
 * @param  list<array<string, mixed>>  $tweets
 * @param  array<string, mixed>  $includes
 * @return array{data: list<array<string, mixed>>, includes: array<string, mixed>, meta: array<string, int|string>}
 */
function xBookmarksPayload(array $tweets, array $includes = [], ?string $nextToken = null): array
{
    $meta = ['result_count' => count($tweets)];

    if ($nextToken !== null) {
        $meta['next_token'] = $nextToken;
    }

    return [
        'data' => $tweets,
        'includes' => $includes,
        'meta' => $meta,
    ];
}
