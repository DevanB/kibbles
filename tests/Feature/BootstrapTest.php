<?php

declare(strict_types=1);

it('exposes a health check endpoint', function (): void {
    $response = $this->get('/up');

    $response->assertOk();
});

it('trusts forwarded https from a reverse proxy', function (): void {
    $this->withServerVariables([
        'HTTPS' => 'off',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
    ])->get('/up');

    expect(request()->secure())->toBeTrue()
        ->and(request()->ip())->toBe('203.0.113.10');
});

it('renders api exceptions as json', function (): void {
    $response = $this->get('/api/missing');

    $response->assertNotFound()
        ->assertHeader('Content-Type', 'application/json');
});
