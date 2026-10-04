<?php

declare(strict_types=1);

use App\Actions\UpdateGame;
use App\Models\Game;

it('may update a game title', function (): void {
    $game = Game::factory()->create([
        'title' => 'Catan',
    ]);

    $action = resolve(UpdateGame::class);

    $updated = $action->handle($game, 'Ticket to Ride');

    expect($updated->title)->toBe('Ticket to Ride')
        ->and($game->refresh()->title)->toBe('Ticket to Ride');
});
