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

it('maps a unique constraint violation to a title validation error', function (): void {
    $user = User::factory()->create();
    Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $action = resolve(CreateGame::class);

    try {
        $action->handle($user, 'catan');
        $this->fail('Expected a validation exception for a unique title conflict.');
    } catch (ValidationException $exception) {
        expect($exception->status)->toBe(422)
            ->and($exception->errors())->toBe([
                'title' => ['You already have a game with this title.'],
            ]);
    }
});
