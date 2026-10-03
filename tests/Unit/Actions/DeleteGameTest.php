<?php

declare(strict_types=1);

use App\Actions\DeleteGame;
use App\Models\Game;

it('may delete a game', function (): void {
    $game = Game::factory()->create();

    $action = resolve(DeleteGame::class);

    $action->handle($game);

    expect($game->exists)->toBeFalse();
});
