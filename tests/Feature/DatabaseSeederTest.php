<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Models\Game;
use Database\Seeders\DatabaseSeeder;

it('does not create demo data when the environment is production', function (): void {
    $this->app->detectEnvironment(fn (): string => 'production');

    $this->artisan('db:seed', ['--force' => true])
        ->expectsOutputToContain('Skipping demo seed in production.')
        ->assertSuccessful();

    $this->assertDatabaseMissing('users', ['email' => 'devan@localhost.test']);
    $this->assertDatabaseCount('games', 0);
});

it('skips demo data in production when the seeder has no console command', function (): void {
    $this->app->detectEnvironment(fn (): string => 'production');

    (new DatabaseSeeder)->run();

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
});

it('updates demo game statuses on re-seed without duplicating journals', function (): void {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $hades = Game::query()->where('title', 'Hades')->first();

    expect($hades)->not->toBeNull();

    $hades->update(['status' => GameStatus::Abandoned]);

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect($hades->refresh()->status)->toBe(GameStatus::InProgress)
        ->and($hades->journalEntries)->toHaveCount(2)
        ->and($hades->playSessions)->toHaveCount(2)
        ->and(Game::query()->count())->toBe(5);
});
