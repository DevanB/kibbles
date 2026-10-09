<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

it('enforces case-insensitive unique titles per user at the database', function (): void {
    $user = User::factory()->create();
    Game::factory()->recycle($user)->create(['title' => 'Catan']);

    expect(fn (): Game => Game::factory()->recycle($user)->create(['title' => 'catan']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('allows different users to store the same title at the database', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Game::factory()->recycle($other)->create(['title' => 'Catan']);

    $game = Game::factory()->recycle($owner)->create(['title' => 'Catan']);

    expect($game->title)->toBe('Catan')
        ->and($game->user()->is($owner))->toBeTrue();
});
