<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\XMediaType;
use App\Exceptions\XClientException;
use App\Models\XConnection;
use App\ValueObjects\XMedia;
use App\ValueObjects\XPost;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class XClient
{
    /**
     * @var list<string>
     */
    private const array EXPANSIONS = [
        'author_id',
        'attachments.media_keys',
        'referenced_tweets.id',
        'referenced_tweets.id.author_id',
    ];

    /**
     * @var list<string>
     */
    private const array TWEET_FIELDS = [
        'created_at',
        'attachments',
        'referenced_tweets',
        'author_id',
        'text',
    ];

    /**
     * @var list<string>
     */
    private const array USER_FIELDS = [
        'name',
        'username',
        'profile_image_url',
    ];

    /**
     * @var list<string>
     */
    private const array MEDIA_FIELDS = [
        'type',
        'url',
        'preview_image_url',
        'width',
        'height',
        'variants',
    ];

    public function bookmarks(XConnection $connection, ?string $paginationToken = null): XBookmarksPage
    {
        $query = [
            'max_results' => 100,
            'expansions' => implode(',', self::EXPANSIONS),
            'tweet.fields' => implode(',', self::TWEET_FIELDS),
            'user.fields' => implode(',', self::USER_FIELDS),
            'media.fields' => implode(',', self::MEDIA_FIELDS),
        ];

        if ($paginationToken !== null) {
            $query['pagination_token'] = $paginationToken;
        }

        $response = $this->send($connection, 'get', '/users/'.$connection->x_user_id.'/bookmarks', $query);
        $includes = $this->objectFromJson($response->json('includes'));

        return new XBookmarksPage(
            bookmarks: array_map(
                fn (mixed $tweet): FetchedXBookmark => $this->bookmarkFromTweet($tweet, $includes),
                $this->listFromJson($response->json('data')),
            ),
            nextToken: $this->stringOrNull($response->json('meta.next_token')),
        );
    }

    public function deleteBookmark(XConnection $connection, string $tweetId): bool
    {
        try {
            $this->send($connection, 'delete', '/users/'.$connection->x_user_id.'/bookmarks/'.$tweetId);
        } catch (XClientException) {
            return false;
        }

        return true;
    }

    public function refresh(XConnection $connection): void
    {
        $clientId = config('services.x.client_id');
        $clientSecret = config('services.x.client_secret');

        if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '') {
            throw new XClientException('X OAuth credentials are not configured.');
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->withBasicAuth($clientId, $clientSecret)
                ->connectTimeout(3)
                ->timeout(10)
                ->post('https://api.x.com/2/oauth2/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $connection->refresh_token,
                    'client_id' => $clientId,
                ]);
        } catch (ConnectionException $exception) {
            throw new XClientException('Unable to refresh the X access token.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new XClientException('Unable to refresh the X access token.');
        }

        $accessToken = $response->json('access_token');
        $refreshToken = $response->json('refresh_token');
        $expiresIn = $response->json('expires_in');

        if (! is_string($accessToken) || $accessToken === '' || ! is_string($refreshToken) || $refreshToken === '') {
            throw new XClientException('X rotated an incomplete token pair.');
        }

        $connection->forceFill([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => Date::now()->addSeconds(is_int($expiresIn) ? $expiresIn : 7200),
        ])->save();
    }

    /**
     * @param  array<string, int|string>  $query
     * @param  'get'|'delete'  $method
     */
    private function send(XConnection $connection, string $method, string $path, array $query = []): Response
    {
        $this->ensureFreshToken($connection);

        try {
            $response = $this->request($connection)->{$method}($path, $query);
        } catch (ConnectionException $exception) {
            throw new XClientException('Unable to reach the X API.', previous: $exception);
        }

        if ($response->unauthorized()) {
            $this->refresh($connection);

            try {
                $response = $this->request($connection)->{$method}($path, $query);
            } catch (ConnectionException $exception) {
                throw new XClientException('Unable to reach the X API.', previous: $exception);
            }
        }

        if (! $response->successful()) {
            Log::warning('X API request failed.', [
                'path' => $path,
                'status' => $response->status(),
            ]);

            throw new XClientException('The X API request failed.');
        }

        return $response;
    }

    private function ensureFreshToken(XConnection $connection): void
    {
        if ($connection->expires_at->isAfter(Date::now()->addMinute())) {
            return;
        }

        $this->refresh($connection);
    }

    private function request(XConnection $connection): PendingRequest
    {
        return Http::baseUrl('https://api.x.com/2')
            ->acceptJson()
            ->withToken($connection->access_token)
            ->connectTimeout(3)
            ->timeout(15);
    }

    /**
     * @param  array<string, mixed>  $includes
     */
    private function bookmarkFromTweet(mixed $tweet, array $includes): FetchedXBookmark
    {
        $id = $this->stringOrEmpty(data_get($tweet, 'id'));
        $author = $this->user(data_get($tweet, 'author_id'), $includes);
        $quoted = $this->quotedPost($tweet, $includes);

        return new FetchedXBookmark(
            xPostId: $id,
            authorName: $this->stringOrEmpty(data_get($author, 'name')),
            authorUsername: $this->stringOrEmpty(data_get($author, 'username')),
            authorAvatarUrl: $this->stringOrNull(data_get($author, 'profile_image_url')),
            text: $this->stringOrEmpty(data_get($tweet, 'text')),
            postedAt: $this->timestamp(data_get($tweet, 'created_at')),
            media: $this->media(data_get($tweet, 'attachments.media_keys'), $includes),
            quotedPost: $quoted,
        );
    }

    /**
     * @param  array<string, mixed>  $includes
     */
    private function quotedPost(mixed $tweet, array $includes): ?XPost
    {
        $quotedId = collect($this->listFromJson(data_get($tweet, 'referenced_tweets')))
            ->first(fn (mixed $reference): bool => data_get($reference, 'type') === 'quoted');

        if (! is_array($quotedId)) {
            return null;
        }

        $id = $this->stringOrEmpty(data_get($quotedId, 'id'));
        $quoted = $this->includedTweet($id, $includes);

        if ($quoted === []) {
            return null;
        }

        $author = $this->user(data_get($quoted, 'author_id'), $includes);
        $username = $this->stringOrEmpty(data_get($author, 'username'));

        return new XPost(
            authorName: $this->stringOrEmpty(data_get($author, 'name')),
            authorUsername: $username,
            authorAvatarUrl: $this->stringOrNull(data_get($author, 'profile_image_url')),
            text: $this->stringOrEmpty(data_get($quoted, 'text')),
            postedAt: $this->timestamp(data_get($quoted, 'created_at')),
            media: $this->media(data_get($quoted, 'attachments.media_keys'), $includes),
            url: $this->postUrl($username, $id),
        );
    }

    /**
     * @param  array<string, mixed>  $includes
     * @return array<string, mixed>
     */
    private function user(mixed $authorId, array $includes): array
    {
        $id = $this->stringOrEmpty($authorId);

        foreach (Arr::wrap(data_get($includes, 'users')) as $user) {
            if (is_array($user) && data_get($user, 'id') === $id) {
                return $this->objectFromJson($user);
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $includes
     * @return array<string, mixed>
     */
    private function includedTweet(string $id, array $includes): array
    {
        foreach (Arr::wrap(data_get($includes, 'tweets')) as $tweet) {
            if (is_array($tweet) && data_get($tweet, 'id') === $id) {
                return $this->objectFromJson($tweet);
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $includes
     * @return list<XMedia>
     */
    private function media(mixed $mediaKeys, array $includes): array
    {
        $keys = array_values(array_filter(
            Arr::wrap($mediaKeys),
            fn (mixed $key): bool => is_string($key) && $key !== '',
        ));

        if ($keys === []) {
            return [];
        }

        $byKey = [];

        foreach (Arr::wrap(data_get($includes, 'media')) as $item) {
            $key = data_get($item, 'media_key');

            if (is_array($item) && is_string($key)) {
                $byKey[$key] = $this->objectFromJson($item);
            }
        }

        return array_values(array_filter(array_map(
            fn (string $key): ?XMedia => isset($byKey[$key]) ? $this->mediaFromInclude($byKey[$key]) : null,
            $keys,
        )));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function mediaFromInclude(array $item): XMedia
    {
        $type = match (data_get($item, 'type')) {
            'video' => XMediaType::Video,
            'animated_gif' => XMediaType::Gif,
            default => XMediaType::Photo,
        };
        $mp4Url = $this->bestMp4Url(data_get($item, 'variants'));
        $previewUrl = $this->stringOrNull(data_get($item, 'preview_image_url'));
        $url = $this->stringOrNull(data_get($item, 'url')) ?? $previewUrl ?? $mp4Url ?? '';
        $width = data_get($item, 'width');
        $height = data_get($item, 'height');

        return new XMedia(
            type: $type,
            url: $url,
            previewUrl: $previewUrl,
            width: is_int($width) ? $width : null,
            height: is_int($height) ? $height : null,
            mp4Url: $type === XMediaType::Photo ? null : $mp4Url,
        );
    }

    private function bestMp4Url(mixed $variants): ?string
    {
        $bestUrl = null;
        $bestBitrate = -1;

        foreach (Arr::wrap($variants) as $variant) {
            if (! is_array($variant) || data_get($variant, 'content_type') !== 'video/mp4') {
                continue;
            }

            $url = $this->stringOrNull(data_get($variant, 'url'));
            $bitrate = data_get($variant, 'bit_rate');

            if ($url === null) {
                continue;
            }

            $bitrateValue = is_int($bitrate) ? $bitrate : 0;

            if ($bitrateValue >= $bestBitrate) {
                $bestBitrate = $bitrateValue;
                $bestUrl = $url;
            }
        }

        return $bestUrl;
    }

    private function timestamp(mixed $value): CarbonInterface
    {
        return is_string($value) && $value !== '' ? Date::parse($value) : Date::now();
    }

    private function postUrl(string $username, string $id): string
    {
        return 'https://x.com/'.$username.'/status/'.$id;
    }

    /**
     * @return array<string, mixed>
     */
    private function objectFromJson(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $object = [];

        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $object[$key] = $item;
            }
        }

        return $object;
    }

    /**
     * @return list<mixed>
     */
    private function listFromJson(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    private function stringOrEmpty(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
