<?php

declare(strict_types=1);

use App\Models\User;

it('renders the dashboard for a verified user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();

    $page->click('@sidebar-menu-button')
        ->assertSee('Log out')
        ->assertNoJavaScriptErrors();
});
