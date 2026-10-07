<?php

declare(strict_types=1);

use App\Services\RawgClient;
use App\Services\RawgGameDetail;
use App\Services\RawgSearchResult;
use Illuminate\Support\Facades\Http;

it('returns no search results when no key is configured', function (): void {
    config(['services.rawg.key' => null]);

    expect(resolve(RawgClient::class)->search('hades'))->toBe([]);
});

it('returns no search results for a blank query', function (): void {
    config(['services.rawg.key' => 'testing']);

    expect(resolve(RawgClient::class)->search('   '))->toBe([]);
});

it('maps a successful search payload', function (): void {
    fakeHadesCatalog();

    $results = resolve(RawgClient::class)->search('hades');

    expect($results)->toHaveCount(1)
        ->and($results[0])->toBeInstanceOf(RawgSearchResult::class)
        ->and($results[0]->id)->toBe(HADES_RAWG_ID)
        ->and($results[0]->name)->toBe('Hades')
        ->and($results[0]->releasedYear)->toBe(2020)
        ->and($results[0]->backgroundImage)->toBe(HADES_IMAGE_URL);
});

it('skips malformed search rows and missing result lists', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games*' => Http::response([
            'results' => [
                ['id' => 1],
                ['name' => 'Nameless'],
                ['id' => 'nope', 'name' => 'Bad'],
                ['id' => 2, 'name' => 'Ok', 'released' => null, 'background_image' => 'not-a-url'],
                ['id' => 3, 'name' => 'Http', 'background_image' => 'http://example.com/g.jpg'],
            ],
        ]),
    ]);

    $results = resolve(RawgClient::class)->search('ok');

    expect($results)->toHaveCount(2)
        ->and($results[0]->id)->toBe(2)
        ->and($results[0]->releasedYear)->toBeNull()
        ->and($results[0]->backgroundImage)->toBeNull()
        ->and($results[1]->backgroundImage)->toBe('http://example.com/g.jpg');
});

it('returns no search results when the catalog request fails', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games*' => Http::response(['results' => []], 500),
    ]);

    expect(resolve(RawgClient::class)->search('hades'))->toBe([]);
});

it('returns no search results when the catalog connection fails', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games*' => Http::failedConnection(),
    ]);

    expect(resolve(RawgClient::class)->search('hades'))->toBe([]);
});

it('returns no search results when the payload has no results list', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games*' => Http::response(['count' => 0]),
    ]);

    expect(resolve(RawgClient::class)->search('hades'))->toBe([]);
});

it('does not find a game when no key is configured', function (): void {
    config(['services.rawg.key' => '']);

    expect(resolve(RawgClient::class)->find(HADES_RAWG_ID))->toBeNull();
});

it('maps a successful detail payload using description_raw', function (): void {
    fakeHadesCatalog();

    $detail = resolve(RawgClient::class)->find(HADES_RAWG_ID);

    expect($detail)->toBeInstanceOf(RawgGameDetail::class)
        ->and($detail->name)->toBe('Hades')
        ->and($detail->backgroundImage)->toBe(HADES_IMAGE_URL)
        ->and($detail->description)->toBe(HADES_DESCRIPTION);
});

it('returns null details for an unknown catalog id', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games/1*' => Http::response(['detail' => 'Not found.'], 404),
    ]);

    expect(resolve(RawgClient::class)->find(1))->toBeNull();
});

it('returns null details when the catalog connection fails', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games/*' => Http::failedConnection(),
    ]);

    expect(resolve(RawgClient::class)->find(HADES_RAWG_ID))->toBeNull();
});

it('returns null details when the payload is not a game', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games/1*' => Http::response('nope'),
    ]);

    expect(resolve(RawgClient::class)->find(1))->toBeNull();
});

it('ignores non-array search rows', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games*' => Http::response([
            'results' => ['nope'],
        ]),
    ]);

    expect(resolve(RawgClient::class)->search('hades'))->toBe([]);
});

it('returns null details when the game has no name', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games/1*' => Http::response([
            'id' => 1,
            'description_raw' => 'Nameless',
        ]),
    ]);

    expect(resolve(RawgClient::class)->find(1))->toBeNull();
});

it('stores a null description when description_raw is missing', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games/1*' => Http::response([
            'id' => 1,
            'name' => 'Mystery',
            'background_image' => HADES_IMAGE_URL,
            'description' => '<p>HTML only</p>',
            'description_raw' => '   ',
        ]),
    ]);

    $detail = resolve(RawgClient::class)->find(1);

    expect($detail)->not->toBeNull()
        ->and($detail->description)->toBeNull();
});
