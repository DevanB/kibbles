<?php

declare(strict_types=1);

use App\Actions\UpdateGame;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

it('may update a game title', function (): void {
    $game = Game::factory()->create([
        'title' => 'Catan',
    ]);

    $action = resolve(UpdateGame::class);

    $updated = $action->handle($game, 'Ticket to Ride', GameStatus::Finished);

    expect($updated->title)->toBe('Ticket to Ride')
        ->and($game->refresh()->title)->toBe('Ticket to Ride');
});

it('does not refetch catalog details when the catalog id is unchanged', function (): void {
    fakeHadesCatalog();

    $game = Game::factory()->catalogLinked()->create(['title' => 'Hades']);

    Http::fake();

    resolve(UpdateGame::class)->handle($game, 'HADES', GameStatus::Finished, HADES_RAWG_ID);

    Http::assertNothingSent();

    expect($game->refresh()->title)->toBe('HADES')
        ->and($game->rawg_id)->toBe(HADES_RAWG_ID)
        ->and($game->image_url)->toBe(HADES_IMAGE_URL);
});

it('maps a unique constraint violation to a title validation error', function (): void {
    $user = User::factory()->create();
    Game::factory()->recycle($user)->create(['title' => 'Catan']);
    $game = Game::factory()->recycle($user)->create(['title' => 'Ticket to Ride']);

    $action = resolve(UpdateGame::class);

    try {
        $action->handle($game, 'catan', GameStatus::Backlog);
        $this->fail('Expected a validation exception for a unique title conflict.');
    } catch (ValidationException $validationException) {
        expect($validationException->status)->toBe(422)
            ->and($validationException->errors())->toBe([
                'title' => ['You already have a game with this title.'],
            ])
            ->and($game->refresh()->title)->toBe('Ticket to Ride');
    }
});
