<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

it('may add, view, edit, and delete a journal entry from hub modals', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $this->actingAs($user);

    $page = visit(route('games.show', $game));

    $page->assertSee('Catan')
        ->assertDontSee('Journal entries will live here.')
        ->assertDontSee('Up next')
        ->assertSee('No journal entries yet')
        ->assertSee('Write what happened the last time you played.')
        ->assertNoJavaScriptErrors();

    $page->click('@create-journal-entry-button')
        ->assertSee('Create Entry')
        ->fill('#compose-body', 'Settled on the ore port.')
        ->click('@add-journal-entry-button')
        ->assertSee('Journal entry added.')
        ->assertDontSee('Settled on the ore port.')
        ->assertDontSee('No journal entries yet')
        ->assertNoJavaScriptErrors();

    $entry = $game->journalEntries()->first();

    expect($entry)->not->toBeNull();

    $page->click('@journal-entry-'.$entry->id)
        ->assertSee('Settled on the ore port.')
        ->click('@edit-journal-entry-button-'.$entry->id)
        ->fill('#edit-'.$entry->id.'-body', 'Settled on the brick port.')
        ->click('@save-journal-entry-button-'.$entry->id)
        ->assertSee('Journal entry updated.')
        ->assertNoJavaScriptErrors();

    $page = visit(route('games.show', $game));

    $page->click('@journal-entry-'.$entry->id)
        ->assertSee('Settled on the brick port.')
        ->click('@delete-journal-entry-button-'.$entry->id)
        ->assertSee('Journal entry deleted.')
        ->assertDontSee('Settled on the brick port.')
        ->assertSee('No journal entries yet')
        ->assertNoJavaScriptErrors();

    expect($entry->fresh())->toBeNull();
});
