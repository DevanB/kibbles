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
    config([
        'app.revision' => 'cafebabedeadbeefcafebabedeadbeefcafebabe',
        'app.key' => 'base64:this-must-not-appear',
        'app.debug' => true,
        'app.env' => 'production',
    ]);

    $response = $this->get(route('version'));

    $response->assertOk();

    expect($response->getContent())
        ->toBe('cafebabedeadbeefcafebabedeadbeefcafebabe')
        ->not->toContain('base64:')
        ->not->toContain('this-must-not-appear')
        ->not->toContain('APP_')
        ->not->toContain('production');
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
