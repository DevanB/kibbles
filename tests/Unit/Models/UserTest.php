<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toBe([
            'id',
            'name',
            'email',
            'email_verified_at',
            'two_factor_confirmed_at',
            'created_at',
            'updated_at',
        ]);
});

it('has many games', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->recycle($user)->create();

    expect($user->games)->toHaveCount(1)
        ->and($user->games->first()?->is($game))->toBeTrue();
});
