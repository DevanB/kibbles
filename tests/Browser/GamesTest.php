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
        ->assertSee('Add Game')
        ->assertNoJavaScriptErrors();

    $page->click('@create-game-button')
        ->fill('title', 'Catan')
        ->click('@save-game-button')
        ->assertSee('Game created.')
        ->assertSee('Catan')
        ->assertNoJavaScriptErrors();

    $game = Game::query()->whereBelongsTo($user)->first();

    expect($game)->not->toBeNull();

    $page->assertPathIs('/games/'.$game->id)
        ->click('@edit-game-button')
        ->assertSee('Edit Game')
        ->assertValue('title', 'Catan')
        ->assertPathIs('/games/'.$game->id.'/edit')
        ->assertSee('Catan')
        ->screenshot(filename: 'game-edit-modal')
        ->assertNoJavaScriptErrors();

    $page->fill('title', 'Ticket to Ride')
        ->click('@save-game-button')
        ->assertSee('Game updated.')
        ->assertSee('Ticket to Ride')
        ->assertPathIs('/games/'.$game->id)
        ->assertNoJavaScriptErrors();

    $page->click('@game-actions-button')
        ->click('@delete-game-button')
        ->assertSee('Delete Ticket to Ride?')
        ->assertSee('This will permanently delete Ticket to Ride and its journal entries.')
        ->click('@cancel-delete-game-button')
        ->assertDontSee('Delete Ticket to Ride?')
        ->assertSee('Ticket to Ride')
        ->assertNoJavaScriptErrors();

    expect($game->fresh())->not->toBeNull();

    $page->click('@game-actions-button')
        ->click('@delete-game-button')
        ->click('@confirm-delete-game-button')
        ->assertSee('Game deleted.')
        ->assertSee('No games yet')
        ->assertNoJavaScriptErrors();

    expect($game->fresh())->toBeNull();
});
