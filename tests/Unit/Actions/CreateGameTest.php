<?php

declare(strict_types=1);

use App\Actions\CreateGame;
use App\Models\Game;
use App\Models\User;

it('may create a game for a user', function (): void {
    $user = User::factory()->create();

    $action = resolve(CreateGame::class);

    $game = $action->handle($user, 'Catan');

    expect($game)->toBeInstanceOf(Game::class)
        ->and($game->title)->toBe('Catan')
        ->and($game->user()->is($user))->toBeTrue();
});
