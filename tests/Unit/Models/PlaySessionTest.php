<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\PlaySession;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

it('allows only one open session per user at the database', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->recycle($user)->create();
    PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    expect(fn (): PlaySession => PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'ended_at' => null,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('formats hour-only totals', function (): void {
    expect(PlaySession::formatMinutes(120))->toBe('2h')
        ->and(PlaySession::totalPlayedLabel(null))->toBe('No time logged');
});

it('rejects an end time before the start time at the database', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->recycle($user)->create();

    expect(fn (): PlaySession => PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'started_at' => now(),
        'ended_at' => now()->subHour(),
    ]))->toThrow(QueryException::class);
});
