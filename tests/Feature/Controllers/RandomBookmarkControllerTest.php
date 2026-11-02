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

it('returns a bookmark tagged read', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'read']);
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $bookmark->tags()->attach($tag);
    $bookmark->load('tags');

    config(['services.kibbles_api.user_id' => $user->id]);

    $this->withToken(KIBBLES_API_TOKEN)
        ->getJson(route('bookmarks.random'))
        ->assertOk()
        ->assertExactJson(['data' => $bookmark->toWire()]);
});

it('does not return untagged bookmarks', function (): void {
    $user = User::factory()->create();
    Tag::factory()->recycle($user)->create(['name' => 'read']);
    XBookmark::factory()->recycle($user)->create();

    config(['services.kibbles_api.user_id' => $user->id]);

    $this->withToken(KIBBLES_API_TOKEN)
        ->getJson(route('bookmarks.random'))
        ->assertOk()
        ->assertExactJson(['data' => null]);
});

it('does not return another users read-tagged bookmarks', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Tag::factory()->recycle($owner)->create(['name' => 'read']);
    $foreignTag = Tag::factory()->recycle($other)->create(['name' => 'read']);
    $foreignBookmark = XBookmark::factory()->recycle($other)->create();
    $foreignBookmark->tags()->attach($foreignTag);

    config(['services.kibbles_api.user_id' => $owner->id]);

    $this->withToken(KIBBLES_API_TOKEN)
        ->getJson(route('bookmarks.random'))
        ->assertOk()
        ->assertExactJson(['data' => null]);
});

it('matches the tag name case-insensitively', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Read']);
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $bookmark->tags()->attach($tag);
    $bookmark->load('tags');

    config(['services.kibbles_api.user_id' => $user->id]);

    $this->withToken(KIBBLES_API_TOKEN)
        ->getJson(route('bookmarks.random', ['tag' => 'READ']))
        ->assertOk()
        ->assertExactJson(['data' => $bookmark->toWire()]);
});

it('returns 404 when the tag is unknown', function (): void {
    $user = User::factory()->create();

    config(['services.kibbles_api.user_id' => $user->id]);

    $this->withToken(KIBBLES_API_TOKEN)
        ->getJson(route('bookmarks.random', ['tag' => 'missing']))
        ->assertNotFound()
        ->assertExactJson(['error' => 'tag_not_found']);
});
