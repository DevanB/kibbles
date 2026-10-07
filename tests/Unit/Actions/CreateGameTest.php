<?php

declare(strict_types=1);

use App\Actions\CreateGame;
use App\Models\Game;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('may create a game for a user', function (): void {
    $user = User::factory()->create();

    $action = resolve(CreateGame::class);

    $game = $action->handle($user, 'Catan');

    expect($game)->toBeInstanceOf(Game::class)
        ->and($game->title)->toBe('Catan')
        ->and($game->user()->is($user))->toBeTrue();
});

it('stores catalog details when creating a linked game', function (): void {
    fakeHadesCatalog();

    $user = User::factory()->create();

    $game = resolve(CreateGame::class)->handle($user, 'Hades', HADES_RAWG_ID);

    expect($game->rawg_id)->toBe(HADES_RAWG_ID)
        ->and($game->image_url)->toBe(HADES_IMAGE_URL)
        ->and($game->description)->toBe(HADES_DESCRIPTION);
});

it('maps a unique constraint violation to a title validation error', function (): void {
    $user = User::factory()->create();
    Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $action = resolve(CreateGame::class);

    try {
        $action->handle($user, 'catan');
        $this->fail('Expected a validation exception for a unique title conflict.');
    } catch (ValidationException $validationException) {
        expect($validationException->status)->toBe(422)
            ->and($validationException->errors())->toBe([
                'title' => ['You already have a game with this title.'],
            ]);
    }
});
