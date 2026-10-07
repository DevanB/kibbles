<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
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
