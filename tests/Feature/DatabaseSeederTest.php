<?php

declare(strict_types=1);

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
});
