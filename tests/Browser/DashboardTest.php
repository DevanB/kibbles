<?php

declare(strict_types=1);

use App\Models\User;

it('renders the dashboard for a verified user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertSee('Dashboard')
        ->assertSee('Kibbles')
        ->assertSee('Recent Journal Entries')
        ->assertSee('No journal entries yet')
        ->assertSee('Add Game')
        ->assertDontSee('Repository')
        ->assertDontSee('Documentation')
        ->assertNoJavaScriptErrors();

    $page->click('@sidebar-menu-button')
        ->assertSee('Log out')
        ->assertNoJavaScriptErrors();
});

it('keeps the Kibbles mark readable in dark mode', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('dashboard'))->inDarkMode();

    $page->assertSee('Kibbles')
        ->assertNoJavaScriptErrors()
        ->screenshot(filename: 'kibbles-sidebar-dark')
        ->screenshotElement('[data-test="app-logo"]', 'kibbles-mark-dark');

    $colors = $page->script(<<<'JS'
        (() => {
            const mark = document.querySelector('[data-test="app-logo-mark"]');
            const box = document.querySelector('[data-test="app-logo"]');

            if (!mark || !box) {
                return null;
            }

            return {
                color: getComputedStyle(mark).color,
                background: getComputedStyle(box).backgroundColor,
            };
        })()
    JS);

    expect($colors)->toBeArray()
        ->and($colors['color'])->not->toBe($colors['background']);
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
