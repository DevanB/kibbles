<?php

declare(strict_types=1);

use App\Features\Registration;
use App\Models\User;
use Laravel\Pennant\Feature;

it('registers a new user from the registration form', function (): void {
    Feature::define(Registration::class, true);

    $page = visit(route('register'));

    $page->assertSee('Create an account')
        ->fill('name', 'Browser Registrant')
        ->fill('email', 'browser-register@example.com')
        ->fill('password', 'password1234')
        ->fill('password_confirmation', 'password1234')
        ->click('@register-user-button')
        ->assertPathIs('/verify-email')
        ->assertSee('Verify email')
        ->assertSee('Please verify your email address by clicking on the link we just emailed to you.')
        ->assertNoJavaScriptErrors();

    $user = User::query()->where('email', 'browser-register@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Browser Registrant')
        ->and($user->hasVerifiedEmail())->toBeFalse();
});
