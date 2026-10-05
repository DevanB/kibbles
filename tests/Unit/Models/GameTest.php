<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
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
            'status',
        ]);
});

it('defaults status to backlog', function (): void {
    $game = Game::factory()->create();

    expect($game->status)->toBe(GameStatus::Backlog);
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
