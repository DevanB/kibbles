<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Models\Game;

it('does not create demo data when the environment is production', function (): void {
    $this->app->detectEnvironment(fn (): string => 'production');

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $this->assertDatabaseMissing('users', ['email' => 'devan@localhost.test']);
    $this->assertDatabaseCount('games', 0);
});

it('creates the demo user and games when the environment is not production', function (): void {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $this->assertDatabaseHas('users', [
        'name' => 'Devan',
        'email' => 'devan@localhost.test',
    ]);
    $this->assertDatabaseCount('games', 5);

    $statuses = Game::query()
        ->whereHas('user', fn ($query) => $query->where('email', 'devan@localhost.test'))
        ->pluck('status')
        ->map(fn (GameStatus $status): string => $status->value)
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($statuses)->toBe([
        GameStatus::Abandoned->value,
        GameStatus::Backlog->value,
        GameStatus::Finished->value,
        GameStatus::InProgress->value,
    ]);
});

it('updates demo game statuses on re-seed without duplicating journals', function (): void {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $hades = Game::query()->where('title', 'Hades')->first();

    expect($hades)->not->toBeNull();

    $hades->update(['status' => GameStatus::Abandoned]);

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect($hades->refresh()->status)->toBe(GameStatus::InProgress)
        ->and($hades->journalEntries)->toHaveCount(2)
        ->and(Game::query()->count())->toBe(5);
});
