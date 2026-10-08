<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

it('shows catalog results and saves the picked game with art and description', function (): void {
    fakeHadesCatalog();

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('games.index'));

    $page->assertSee('Add Game')
        ->click('@create-game-button')
        ->fill('title', 'hades')
        ->assertSee('Hades')
        ->assertSee('2020')
        ->assertVisible('@game-catalog-results')
        ->screenshot(filename: 'game-create-catalog-results')
        ->click('@game-catalog-result-274755')
        ->assertValue('title', 'Hades')
        ->click('@save-game-button')
        ->assertSee('Game created.')
        ->assertSee('Hades')
        ->assertSee(HADES_DESCRIPTION)
        ->assertAttribute('@game-art', 'src', HADES_IMAGE_URL)
        ->screenshot(filename: 'game-show-catalog-art')
        ->assertNoJavaScriptErrors();

    $game = Game::query()->whereBelongsTo($user)->first();

    expect($game)->not->toBeNull()
        ->and($game->rawg_id)->toBe(HADES_RAWG_ID)
        ->and($game->image_url)->toBe(HADES_IMAGE_URL)
        ->and($game->description)->toBe(HADES_DESCRIPTION);
});

it('saves a typed title with no catalog pick as an unlinked game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('games.index'));

    $page->click('@create-game-button')
        ->fill('title', 'My Homebrew Campaign')
        ->click('@save-game-button')
        ->assertSee('Game created.')
        ->assertSee('My Homebrew Campaign')
        ->assertSee('No box art yet')
        ->screenshot(filename: 'game-show-unlinked')
        ->assertNoJavaScriptErrors();

    $game = Game::query()->whereBelongsTo($user)->first();

    expect($game)->not->toBeNull()
        ->and($game->rawg_id)->toBeNull()
        ->and($game->image_url)->toBeNull()
        ->and($game->description)->toBeNull();
});

it('rejects picking a catalog game the user already linked under another title', function (): void {
    fakeHadesCatalog();

    $user = User::factory()->withoutTwoFactor()->create();
    Game::factory()->recycle($user)->catalogLinked()->create(['title' => 'Supergiant Rerun']);

    $this->actingAs($user);

    $page = visit(route('games.index'));

    $page->click('@create-game-button')
        ->fill('title', 'hades')
        ->assertSee('Hades')
        ->click('@game-catalog-result-274755')
        ->click('@save-game-button')
        ->assertSee('You already have this game.')
        ->assertNoJavaScriptErrors();

    expect(Game::query()->whereBelongsTo($user)->count())->toBe(1);
});

it('links an existing game from the edit modal', function (): void {
    fakeHadesCatalog();

    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Hades']);

    $this->actingAs($user);

    $page = visit(route('games.show', $game));

    $page->assertSee('No box art yet')
        ->click('@edit-game-button')
        ->assertSee('Edit Game')
        ->fill('title', 'hades')
        ->assertSee('2020')
        ->screenshot(filename: 'game-edit-catalog-results')
        ->click('@game-catalog-result-274755')
        ->assertValue('title', 'Hades')
        ->click('@save-game-button')
        ->assertSee('Game updated.')
        ->assertSee(HADES_DESCRIPTION)
        ->assertAttribute('@game-art', 'src', HADES_IMAGE_URL)
        ->assertNoJavaScriptErrors();

    expect($game->refresh()->rawg_id)->toBe(HADES_RAWG_ID)
        ->and($game->image_url)->toBe(HADES_IMAGE_URL)
        ->and($game->description)->toBe(HADES_DESCRIPTION);
});
