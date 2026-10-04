<?php

declare(strict_types=1);

use App\Models\User;

it('sends guests from home to login', function (): void {
    $page = visit('/');

    $page->assertPathIs('/login')
        ->assertSee('Log in')
        ->assertNoJavaScriptErrors();
});

it('sends a verified user from home to the dashboard', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit('/');

    $page->assertPathIs('/dashboard')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
});
