<?php

declare(strict_types=1);

use App\Models\User;

it('renders the dashboard for a verified user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertSee('Dashboard')
        ->assertSee(config('app.name'))
        ->assertNoJavaScriptErrors();

    $page->click('@sidebar-menu-button')
        ->assertSee('Log out')
        ->assertNoJavaScriptErrors();
});

it('logs out from the dashboard user menu', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->click('@sidebar-menu-button')
        ->click('@logout-button')
        ->assertPathIs('/login')
        ->assertSee('Log in')
        ->assertNoJavaScriptErrors();

    $page->navigate(route('dashboard'))
        ->assertPathIs('/login')
        ->assertNoJavaScriptErrors();
});
