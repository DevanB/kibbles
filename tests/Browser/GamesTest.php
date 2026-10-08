<?php

declare(strict_types=1);

use App\Enums\GameStatus;
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
        ->assertSee('Backlog')
        ->assertSee('No box art yet')
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
        ->assertSee('Ticket to Ride')
        ->assertNoJavaScriptErrors();

    expect($game->fresh())->not->toBeNull();

    $page->click('@game-actions-button')
        ->click('@delete-game-button')
        ->click('@confirm-delete-game-button')
        ->assertSee('Game deleted.')
        ->assertSee('No games yet')
        ->assertPathIs('/games')
        ->screenshot(filename: 'games-index-after-delete')
        ->assertNoJavaScriptErrors();

    expect($game->fresh())->toBeNull();
});

it('persists a status change from the edit modal', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $this->actingAs($user);

    $page = visit(route('games.show', $game));

    $page->assertSee('Catan')
        ->assertSee('Backlog')
        ->assertNoJavaScriptErrors();

    $page->click('@edit-game-button')
        ->assertSee('Edit Game')
        ->select('status', GameStatus::Finished->value)
        ->click('@save-game-button')
        ->assertSee('Game updated.')
        ->assertSee('Finished')
        ->assertPathIs('/games/'.$game->id)
        ->screenshot(filename: 'game-show-finished-status')
        ->assertNoJavaScriptErrors();

    expect($game->refresh()->status)->toBe(GameStatus::Finished);

    $index = visit(route('games.index'));

    $index->assertSee('Catan')
        ->assertSee('Finished')
        ->screenshot(filename: 'games-index-finished-status')
        ->assertNoJavaScriptErrors();
});

it('shows the games list as artwork tiles with status on the art', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $linked = Game::factory()->recycle($user)->catalogLinked()->create(['title' => 'Hades']);
    $unlinked = Game::factory()->recycle($user)->create(['title' => 'Celeste']);

    $this->actingAs($user);

    $page = visit(route('games.index'));

    $page->assertSee('Hades')
        ->assertSee('Celeste')
        ->assertSee('Backlog')
        ->assertAttribute('@game-art-'.$linked->id, 'src', HADES_IMAGE_URL)
        ->assertVisible('@game-art-slot-'.$unlinked->id)
        ->assertVisible('@game-status-'.$linked->id)
        ->assertVisible('@game-title-'.$unlinked->id)
        ->screenshot(filename: 'games-index-artwork-grid')
        ->assertNoJavaScriptErrors();

    $page->click('@game-open-'.$linked->id)
        ->assertPathIs('/games/'.$linked->id)
        ->assertSee('Hades')
        ->assertNoJavaScriptErrors();
});
