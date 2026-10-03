<?php

declare(strict_types=1);

use App\Actions\CreateGame;
use App\Models\Game;
use App\Models\User;

it('may create a game', function (): void {
    $user = User::factory()->create();

    $action = resolve(CreateGame::class);

    $game = $action->handle($user, [
        'title' => 'Chess',
    ]);

    expect($game)->toBeInstanceOf(Game::class)
        ->and($game->title)->toBe('Chess')
        ->and($game->user_id)->toBe($user->id);
});
