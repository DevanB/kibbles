<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

it('may create, update, and delete a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('games.index'));

    $page->assertSee('Games')
        ->assertSee('No games yet')
        ->assertSee('Add game')
        ->assertNoJavaScriptErrors();

    $page->click('@create-game-button')
        ->fill('title', 'Catan')
        ->click('@save-game-button')
        ->assertSee('Game created.')
        ->assertSee('Edit game')
        ->assertValue('title', 'Catan')
        ->assertNoJavaScriptErrors();

    $game = Game::query()->whereBelongsTo($user)->first();

    expect($game)->not->toBeNull();

    $page->assertPathIs('/games/'.$game->id.'/edit')
        ->fill('title', 'Ticket to Ride')
        ->click('@save-game-button')
        ->assertSee('Game updated.')
        ->assertValue('title', 'Ticket to Ride')
        ->assertPathIs('/games/'.$game->id.'/edit')
        ->assertNoJavaScriptErrors();

    $page->click('@delete-game-button')
        ->assertSee('Game deleted.')
        ->assertSee('No games yet')
        ->assertNoJavaScriptErrors();

    expect($game->fresh())->toBeNull();
});
