<?php

declare(strict_types=1);

use App\Models\User;

it('renders passkeys page', function (): void {
    $user = User::factory()->create();

    $user->passkeys()->create([
        'name' => 'MacBook Pro',
        'credential_id' => 'credential-id',
        'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
    ]);

    $this->actingAs($user)->session(['auth.password_confirmed_at' => time()]);

    $response = $this->fromRoute('dashboard')
        ->get(route('user-passkey.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-passkey/index')
            ->where('canManagePasskeys', true)
            ->has('passkeys', 1)
            ->where('passkeys.0.name', 'MacBook Pro')
            ->where('passkeys.0.created_at_diff', '0 seconds ago')
            ->where('passkeys.0.last_used_at_diff', null));
});

it('renders passkeys page without passkeys', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->session(['auth.password_confirmed_at' => time()]);

    $response = $this->fromRoute('dashboard')
        ->get(route('user-passkey.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-passkey/index')
            ->has('passkeys', 0));
});

it('requires password confirmation to render passkeys page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->get(route('user-passkey.index'));

    $response->assertRedirectToRoute('password.confirm');
});

it('requires verified email to render passkeys page', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->session(['auth.password_confirmed_at' => time()]);

    $response = $this->fromRoute('dashboard')
        ->get(route('user-passkey.index'));

    $response->assertRedirectToRoute('verification.notice');
});

it('exposes passkey endpoints', function (): void {
    $response = $this->get(route('well-known.passkeys'));

    $response->assertOk()
        ->assertExactJson([
            'enroll' => route('user-passkey.index'),
            'manage' => route('user-passkey.index'),
        ]);
});

it('binds passkey relying party and origins to the application url host', function (): void {
    $appUrl = config()->string('app.url');
    $appHost = parse_url($appUrl, PHP_URL_HOST);

    expect(config('fortify.passkeys.relying_party_id'))->toBe($appHost)
        ->and(config('fortify.passkeys.allowed_origins'))->toBe([$appUrl]);

    expect(parse_url(route('user-passkey.index'), PHP_URL_HOST))->toBe($appHost);
});

it('does not treat a foreign browse host as a valid passkey origin', function (): void {
    $appHost = parse_url(config()->string('app.url'), PHP_URL_HOST);
    $foreignHost = $appHost === 'kibbles.test' ? 'localhost' : 'kibbles.test';
    $foreignOrigin = 'http://'.$foreignHost;

    expect(config('fortify.passkeys.relying_party_id'))->not->toBe($foreignHost)
        ->and(config('fortify.passkeys.allowed_origins'))->not->toContain($foreignOrigin);
});
