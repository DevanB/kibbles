<?php

declare(strict_types=1);

use App\Services\RawgClient;
use Illuminate\Support\Facades\Http;

it('does not find a game when no key is configured', function (): void {
    config(['services.rawg.key' => null]);

    expect(resolve(RawgClient::class)->find(HADES_RAWG_ID))->toBeNull();
});

it('returns nothing when the catalog request fails', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games*' => Http::response(['detail' => 'Server error'], 500),
    ]);

    $rawg = resolve(RawgClient::class);

    expect($rawg->search('hades'))->toBeArray()->toBeEmpty()
        ->and($rawg->find(HADES_RAWG_ID))->toBeNull();
});

it('returns nothing for an unknown catalog id', function (): void {
    config(['services.rawg.key' => 'testing']);

    Http::fake([
        'https://api.rawg.io/api/games/1*' => Http::response(['detail' => 'Not found.'], 404),
    ]);

    expect(resolve(RawgClient::class)->find(1))->toBeNull();
});
