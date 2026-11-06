<?php

declare(strict_types=1);

use App\Enums\LinkPreviewStatus;
use App\Jobs\FetchLinkPreview;
use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

const KIBBLES_SHARE_TOKEN = 'test-kibbles-share-token';
const KIBBLES_READ_TOKEN = 'test-kibbles-api-token';

beforeEach(function (): void {
    config([
        'services.kibbles_api.token' => KIBBLES_READ_TOKEN,
        'services.kibbles_api.share_token' => KIBBLES_SHARE_TOKEN,
    ]);
});

it('returns 401 when no token is provided', function (): void {
    User::factory()->create();

    $this->postJson(route('links.store'), ['url' => 'https://example.com/from-share'])
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

it('returns 401 when the token is wrong', function (): void {
    User::factory()->create();

    $this->withToken('wrong-token')
        ->postJson(route('links.store'), ['url' => 'https://example.com/from-share'])
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

it('returns 401 when the read token is used', function (): void {
    User::factory()->create();

    $this->withToken(KIBBLES_READ_TOKEN)
        ->postJson(route('links.store'), ['url' => 'https://example.com/from-share'])
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

it('returns 401 when the share token config is empty', function (mixed $token): void {
    config(['services.kibbles_api.share_token' => $token]);
    User::factory()->create();

    $this->withToken(KIBBLES_SHARE_TOKEN)
        ->postJson(route('links.store'), ['url' => 'https://example.com/from-share'])
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
})->with([
    'empty string' => '',
    'null' => null,
]);

it('returns 401 when no user exists', function (): void {
    $this->withToken(KIBBLES_SHARE_TOKEN)
        ->postJson(route('links.store'), ['url' => 'https://example.com/from-share'])
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

it('returns 401 when more than one user exists', function (): void {
    User::factory()->create();
    User::factory()->create();

    $this->withToken(KIBBLES_SHARE_TOKEN)
        ->postJson(route('links.store'), ['url' => 'https://example.com/from-share'])
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

it('saves a private URL without fetching a preview', function (): void {
    Queue::fake([FetchLinkPreview::class]);

    $user = User::factory()->create();

    $this->withToken(KIBBLES_SHARE_TOKEN)
        ->postJson(route('links.store'), ['url' => 'http://192.168.1.50/nas'])
        ->assertCreated()
        ->assertExactJson(['message' => 'Saved to Kibbles.']);

    $link = $user->links()->first();

    expect($link)->not->toBeNull()
        ->and($link->url)->toBe('http://192.168.1.50/nas')
        ->and($link->preview_status)->toBe(LinkPreviewStatus::Ready);

    Queue::assertNothingPushed();
});

it('saves a new link and returns 201', function (): void {
    Queue::fake([FetchLinkPreview::class]);

    $user = User::factory()->create();

    $this->withToken(KIBBLES_SHARE_TOKEN)
        ->postJson(route('links.store'), ['url' => 'https://Example.com/from-share?utm_source=ios'])
        ->assertCreated()
        ->assertExactJson(['message' => 'Saved to Kibbles.']);

    $link = $user->links()->first();

    expect($link)->not->toBeNull()
        ->and($link->url)->toBe('https://example.com/from-share')
        ->and($link->preview_status)->toBe(LinkPreviewStatus::Pending);

    Queue::assertPushed(FetchLinkPreview::class, fn (FetchLinkPreview $job): bool => $job->linkId === $link->id);
});

it('returns 200 when the normalized URL is already saved', function (): void {
    Queue::fake([FetchLinkPreview::class]);

    $user = User::factory()->create();
    Link::factory()->recycle($user)->create(['url' => 'https://example.com/from-share']);

    $this->withToken(KIBBLES_SHARE_TOKEN)
        ->postJson(route('links.store'), ['url' => 'https://example.com/from-share?utm_campaign=again'])
        ->assertOk()
        ->assertExactJson(['message' => 'Already in Kibbles.']);

    expect($user->links()->count())->toBe(1);

    Queue::assertNothingPushed();
});

it('returns 422 when the URL is invalid', function (): void {
    User::factory()->create();

    $this->withToken(KIBBLES_SHARE_TOKEN)
        ->postJson(route('links.store'), ['url' => 'javascript:alert(1)'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['url' => 'Enter a valid http or https URL.']);
});

it('returns 422 when the URL is missing', function (): void {
    User::factory()->create();

    $this->withToken(KIBBLES_SHARE_TOKEN)
        ->postJson(route('links.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('url');
});
