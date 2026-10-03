<?php

declare(strict_types=1);

use App\Models\User;

it('creates a game from the games pages', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('games.index'));

    $page->assertSee('Games')
        ->assertSee('No games yet')
        ->assertSee('Create game')
        ->assertNoJavaScriptErrors();

    $page->click('Create game')
        ->fill('title', 'Catan')
        ->click('@create-game-button')
        ->assertSee('Catan')
        ->assertSee('Game created.')
        ->assertNoJavaScriptErrors();
});
