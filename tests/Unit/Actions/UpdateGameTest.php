<?php

declare(strict_types=1);

use App\Actions\UpdateGame;
use App\Models\Game;

it('may update a game', function (): void {
    $game = Game::factory()->create([
        'title' => 'Old Title',
    ]);

    $action = resolve(UpdateGame::class);

    $action->handle($game, [
        'title' => 'New Title',
    ]);

    expect($game->refresh()->title)->toBe('New Title');
});
