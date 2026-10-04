<?php

declare(strict_types=1);

use App\Models\User;

it('shows the verify email notice when an unverified user opens the dashboard', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertPathIs('/verify-email')
        ->assertSee('Verify email')
        ->assertSee('Please verify your email address by clicking on the link we just emailed to you.')
        ->assertSee('Resend verification email')
        ->assertSee('Log out')
        ->assertNoJavaScriptErrors();
});
