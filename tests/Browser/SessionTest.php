<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a session from the login form', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'login-success@example.com',
        'password' => Hash::make('password'),
    ]);

    $page = visit(route('login'));

    $page->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('@login-button')
        ->assertPathIs('/dashboard')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
});

it('shows an error when login credentials are invalid', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'login-failure@example.com',
        'password' => Hash::make('password'),
    ]);

    $page = visit(route('login'));

    $page->fill('email', $user->email)
        ->fill('password', 'wrong-password')
        ->click('@login-button')
        ->assertPathIs('/login')
        ->assertSee(__('auth.failed'))
        ->assertNoJavaScriptErrors();
});

it('challenges a two-factor user and signs them in with a recovery code', function (): void {
    $recoveryCode = 'aaaaaa-bbbbbb';

    $user = User::factory()->create([
        'email' => 'two-factor@example.com',
        'password' => Hash::make('password'),
        'two_factor_recovery_codes' => encrypt(json_encode([$recoveryCode])),
    ]);

    $page = visit(route('login'));

    $page->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('@login-button')
        ->assertPathIs('/two-factor-challenge')
        ->assertSee('Authentication code')
        ->assertSee('Enter the authentication code provided by your authenticator application.')
        ->assertNoJavaScriptErrors();

    $page->click('login using a recovery code')
        ->assertSee('Recovery code')
        ->assertSee('Please confirm access to your account by entering one of your emergency recovery codes.')
        ->fill('recovery_code', $recoveryCode)
        ->click('Continue')
        ->assertPathIs('/dashboard')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
});
