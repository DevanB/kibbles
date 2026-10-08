<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

it('may add, view, edit, and delete a journal entry from hub modals', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $this->actingAs($user);

    $page = visit(route('games.index'));

    $page->assertSee('Catan')
        ->click('@game-open-'.$game->id)
        ->assertPathIs('/games/'.$game->id)
        ->assertSee('No play sessions yet')
        ->click('@game-tab-journal')
        ->assertQueryStringHas('tab', 'journal')
        ->assertSee('No journal entries yet')
        ->assertSee('Write what happened the last time you played.')
        ->refresh()
        ->assertQueryStringHas('tab', 'journal')
        ->assertSee('No journal entries yet')
        ->back()
        ->assertPathIs('/games')
        ->assertSee('Catan')
        ->click('@game-open-'.$game->id)
        ->assertPathIs('/games/'.$game->id)
        ->click('@game-tab-journal')
        ->assertQueryStringHas('tab', 'journal')
        ->click('@create-journal-entry-button')
        ->assertSee('Create Entry')
        ->click('.im-close-button')
        ->assertQueryStringHas('tab', 'journal')
        ->assertSee('No journal entries yet')
        ->assertNoJavaScriptErrors();

    $page = visit(route('games.show', ['game' => $game, 'tab' => 'journal']));

    $page->assertQueryStringHas('tab', 'journal')
        ->assertSee('No journal entries yet')
        ->click('@create-journal-entry-button')
        ->assertSee('Create Entry')
        ->fill('#compose-body', 'Settled on the ore port.')
        ->click('@add-journal-entry-button')
        ->assertSee('Journal entry added.')
        ->assertQueryStringHas('tab', 'journal')
        ->assertSee('Journal Entries')
        ->assertNoJavaScriptErrors();

    $older = $game->journalEntries()->first();

    expect($older)->not->toBeNull();

    $page->click('@create-journal-entry-button')
        ->assertSee('Create Entry')
        ->fill('#compose-body', 'Cities went down early.')
        ->click('@add-journal-entry-button')
        ->assertSee('Journal entry added.')
        ->assertNoJavaScriptErrors();

    $newer = $game->journalEntries()->where('body', 'Cities went down early.')->first();

    expect($newer)->not->toBeNull();

    $page = visit(route('games.show', ['game' => $game, 'tab' => 'journal']));

    $rowOrder = $page->script(<<<'JS'
        (() => [...document.querySelectorAll('[data-test^="journal-entry-"]')]
            .map((el) => el.getAttribute('data-test'))
            .filter((name) => /^journal-entry-[0-9a-f-]+$/i.test(name ?? '')))()
    JS);

    expect($rowOrder[0])->toBe('journal-entry-'.$newer->id)
        ->and($rowOrder[1])->toBe('journal-entry-'.$older->id);

    $page->click('@journal-entry-'.$older->id)
        ->assertSee('Settled on the ore port.')
        ->click('@edit-journal-entry-button-'.$older->id)
        ->assertSee('Edit Entry')
        ->fill('#edit-'.$older->id.'-body', 'Settled on the brick port.')
        ->click('@save-journal-entry-button-'.$older->id)
        ->assertSee('Journal entry updated.')
        ->assertNoJavaScriptErrors();

    $page = visit(route('games.show', ['game' => $game, 'tab' => 'journal']));

    $page->click('@journal-entry-'.$older->id)
        ->assertSee('Settled on the brick port.')
        ->click('@delete-journal-entry-button-'.$older->id)
        ->assertSee('Delete journal entry?')
        ->assertSee('This will permanently delete this journal entry from Catan.')
        ->screenshot(filename: 'journal-delete-confirm')
        ->click('@cancel-delete-journal-entry-button-'.$older->id)
        ->assertSee('Settled on the brick port.')
        ->assertNoJavaScriptErrors();

    expect($older->fresh())->not->toBeNull();

    $page->click('@delete-journal-entry-button-'.$older->id)
        ->click('@confirm-delete-journal-entry-button-'.$older->id)
        ->assertSee('Journal entry deleted.')
        ->assertNoJavaScriptErrors();

    expect($older->fresh())->toBeNull()
        ->and($newer->fresh())->not->toBeNull();

    $page->click('@journal-entry-'.$newer->id)
        ->click('@delete-journal-entry-button-'.$newer->id)
        ->click('@confirm-delete-journal-entry-button-'.$newer->id)
        ->assertSee('Journal entry deleted.')
        ->assertQueryStringHas('tab', 'journal')
        ->assertSee('No journal entries yet')
        ->assertNoJavaScriptErrors();

    expect($newer->fresh())->toBeNull();
});
