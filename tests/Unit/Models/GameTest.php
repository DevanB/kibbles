<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Str;

test('to array', function (): void {
    $game = Game::factory()->create()->refresh();

    expect(array_keys($game->toArray()))
        ->toBe([
            'id',
            'user_id',
            'title',
            'created_at',
            'updated_at',
        ]);
});

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->recycle($user)->create();

    expect($game->user->is($user))->toBeTrue();
});

it('uses a uuid primary key', function (): void {
    $game = Game::factory()->create();

    expect($game->id)->toBeString()
        ->and(Str::isUuid($game->id))->toBeTrue();
});
