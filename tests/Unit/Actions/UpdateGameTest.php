<?php

declare(strict_types=1);

use App\Actions\UpdateGame;
use App\Models\Game;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('may update a game title', function (): void {
    $game = Game::factory()->create([
        'title' => 'Catan',
    ]);

    $action = resolve(UpdateGame::class);

    $updated = $action->handle($game, 'Ticket to Ride');

    expect($updated->title)->toBe('Ticket to Ride')
        ->and($game->refresh()->title)->toBe('Ticket to Ride');
});

it('maps a unique constraint violation to a title validation error', function (): void {
    $user = User::factory()->create();
    Game::factory()->recycle($user)->create(['title' => 'Catan']);
    $game = Game::factory()->recycle($user)->create(['title' => 'Ticket to Ride']);

    $action = resolve(UpdateGame::class);

    try {
        $action->handle($game, 'catan');
        $this->fail('Expected a validation exception for a unique title conflict.');
    } catch (ValidationException $validationException) {
        expect($validationException->status)->toBe(422)
            ->and($validationException->errors())->toBe([
                'title' => ['You already have a game with this title.'],
            ])
            ->and($game->refresh()->title)->toBe('Ticket to Ride');
    }
});
