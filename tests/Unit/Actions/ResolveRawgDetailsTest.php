<?php

declare(strict_types=1);

use App\Actions\ResolveRawgDetails;

it('returns empty details when no catalog id is provided', function (): void {
    expect(resolve(ResolveRawgDetails::class)->handle(null))->toBe([
        'image_url' => null,
        'description' => null,
    ]);
});

it('returns catalog details for a known id', function (): void {
    fakeHadesCatalog();

    expect(resolve(ResolveRawgDetails::class)->handle(HADES_RAWG_ID))->toBe([
        'image_url' => HADES_IMAGE_URL,
        'description' => HADES_DESCRIPTION,
    ]);
});

it('returns empty details when the catalog lookup fails', function (): void {
    config(['services.rawg.key' => 'testing']);

    Illuminate\Support\Facades\Http::fake([
        'https://api.rawg.io/api/games/*' => Illuminate\Support\Facades\Http::failedConnection(),
    ]);

    expect(resolve(ResolveRawgDetails::class)->handle(HADES_RAWG_ID))->toBe([
        'image_url' => null,
        'description' => null,
    ]);
});
