<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;

const KIBBLES_API_TOKEN = 'test-kibbles-api-token';

beforeEach(function (): void {
    config(['services.kibbles_api.token' => KIBBLES_API_TOKEN]);
});

it('returns 401 when no token is provided', function (): void {
    $this->getJson(route('bookmarks.random'))
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

it('returns 401 when the token is wrong', function (): void {
    $this->withToken('wrong-token')
        ->getJson(route('bookmarks.random'))
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

it('returns 401 when the share token is used', function (): void {
    config(['services.kibbles_api.share_token' => 'test-kibbles-share-token']);

    $this->withToken('test-kibbles-share-token')
        ->getJson(route('bookmarks.random'))
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

it('returns 401 when the token config is empty', function (mixed $token): void {
    config(['services.kibbles_api.token' => $token]);

    $this->withToken('')
        ->getJson(route('bookmarks.random'))
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
})->with([
    'empty string' => '',
    'null' => null,
]);

it('returns 500 when no user exists', function (): void {
    $this->withToken(KIBBLES_API_TOKEN)
        ->getJson(route('bookmarks.random'))
        ->assertInternalServerError()
        ->assertExactJson(['error' => 'owner_unresolved']);
});

it('returns 500 when more than one user exists', function (): void {
    User::factory()->create();
    User::factory()->create();

    $this->withToken(KIBBLES_API_TOKEN)
        ->getJson(route('bookmarks.random'))
        ->assertInternalServerError()
        ->assertExactJson(['error' => 'owner_unresolved']);
});

it('returns a bookmark tagged read', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'read']);
    $bookmark = XBookmark::factory()->recycle($user)->create([
        'x_post_id' => '1234567890',
        'author_name' => 'Ada Lovelace',
        'author_username' => 'ada',
        'text' => 'Notes on the engine.',
    ]);
    $bookmark->tags()->attach($tag);

    $this->withToken(KIBBLES_API_TOKEN)
        ->getJson(route('bookmarks.random'))
        ->assertOk()
        ->assertJson([
            'data' => [
                'id' => $bookmark->id,
                'url' => 'https://x.com/ada/status/1234567890',
                'text' => 'Notes on the engine.',
                'authorName' => 'Ada Lovelace',
                'authorUsername' => 'ada',
                'tags' => [
                    ['id' => $tag->id, 'name' => 'read'],
                ],
            ],
        ]);
});

it('does not return untagged bookmarks', function (): void {
    $user = User::factory()->create();
    Tag::factory()->recycle($user)->create(['name' => 'read']);
    XBookmark::factory()->recycle($user)->create();

    $this->withToken(KIBBLES_API_TOKEN)
        ->getJson(route('bookmarks.random'))
        ->assertOk()
        ->assertExactJson(['data' => null]);
});

it('matches the tag name case-insensitively', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Read']);
    $bookmark = XBookmark::factory()->recycle($user)->create([
        'x_post_id' => '9876543210',
        'author_name' => 'Grace Hopper',
        'author_username' => 'grace',
        'text' => 'A compiler note.',
    ]);
    $bookmark->tags()->attach($tag);

    $this->withToken(KIBBLES_API_TOKEN)
        ->getJson(route('bookmarks.random', ['tag' => 'READ']))
        ->assertOk()
        ->assertJson([
            'data' => [
                'id' => $bookmark->id,
                'url' => 'https://x.com/grace/status/9876543210',
                'text' => 'A compiler note.',
                'authorName' => 'Grace Hopper',
                'authorUsername' => 'grace',
                'tags' => [
                    ['id' => $tag->id, 'name' => 'Read'],
                ],
            ],
        ]);
});

it('returns 404 when the tag is unknown', function (): void {
    User::factory()->create();

    $this->withToken(KIBBLES_API_TOKEN)
        ->getJson(route('bookmarks.random', ['tag' => 'missing']))
        ->assertNotFound()
        ->assertExactJson(['error' => 'tag_not_found']);
});
