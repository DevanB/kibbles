<?php

declare(strict_types=1);

it('defines the nas production services without extra datastores', function (): void {
    $compose = file_get_contents(base_path('compose.yaml'));

    expect($compose)->not->toBeFalse();

    expect($compose)
        ->toContain("  app:\n")
        ->toContain("  queue:\n")
        ->toContain("  cloudflared:\n")
        ->toContain('php artisan queue:work')
        ->toContain('cloudflare/cloudflared')
        ->toContain('CLOUDFLARE_TUNNEL_TOKEN')
        ->toContain('sqlite:/app/database/data')
        ->toContain('storage:/app/storage')
        ->not->toContain('redis:')
        ->not->toContain('mysql:')
        ->not->toContain('postgres:')
        ->not->toContain('tailscale');
});

it('builds php and bun assets and listens on 8080', function (): void {
    $dockerfile = file_get_contents(base_path('Dockerfile'));

    expect($dockerfile)->not->toBeFalse();

    expect($dockerfile)
        ->toContain('dunglas/frankenphp:php8.5')
        ->toContain('oven/bun:1.4.2')
        ->toContain('bun install --frozen-lockfile')
        ->toContain('bun run build')
        ->toContain('EXPOSE 8080')
        ->toContain('SERVER_NAME=":8080"')
        ->not->toContain('npm run')
        ->not->toContain('pnpm');
});

it('keeps production env placeholders out of git secrets', function (): void {
    $example = file_get_contents(base_path('.env.production.example'));

    expect($example)->not->toBeFalse();

    expect($example)
        ->toContain('APP_ENV=production')
        ->toContain('APP_DEBUG=false')
        ->toContain('APP_URL=https://kibbles.devanb.us')
        ->toContain('DB_CONNECTION=sqlite')
        ->toContain('QUEUE_CONNECTION=database')
        ->toContain('CLOUDFLARE_TUNNEL_TOKEN=')
        ->toContain('supplied at deploy time');

    expect($example)->toMatch('/^APP_KEY=$/m');
    expect($example)->toMatch('/^CLOUDFLARE_TUNNEL_TOKEN=$/m');
});
