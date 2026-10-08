<?php

declare(strict_types=1);

it('returns the configured revision as plain text', function (): void {
    config(['app.revision' => '0123456789abcdef0123456789abcdef01234567']);

    $response = $this->get(route('version'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect($response->getContent())->toBe('0123456789abcdef0123456789abcdef01234567');
});

it('does not expose application configuration besides the revision', function (): void {
    $applicationKey = (string) config('app.key');

    config([
        'app.revision' => 'cafebabedeadbeefcafebabedeadbeefcafebabe',
        'app.debug' => true,
        'app.env' => 'production',
    ]);

    $response = $this->get(route('version'));

    $response->assertOk();

    expect($response->getContent())
        ->toBe('cafebabedeadbeefcafebabedeadbeefcafebabe')
        ->not->toContain($applicationKey)
        ->not->toContain('APP_')
        ->not->toContain('production');
});

it('does not start a session', function (): void {
    config(['app.revision' => '0123456789abcdef0123456789abcdef01234567']);

    $response = $this->get(route('version'));

    $response->assertOk()
        ->assertCookieMissing((string) config('session.cookie'));
});

it('falls back to dev when the revision is unset', function (mixed $revision): void {
    config(['app.revision' => $revision]);

    $response = $this->get(route('version'));

    $response->assertOk();

    expect($response->getContent())->toBe('dev');
})->with([
    'empty string' => '',
    'null' => null,
]);
