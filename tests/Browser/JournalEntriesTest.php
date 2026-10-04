<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

it('may add, edit, and delete a journal entry on the game hub', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $this->actingAs($user);

    $page = visit(route('games.show', $game));

    $page->assertSee('Catan')
        ->assertDontSee('Journal entries will live here.')
        ->assertSee('No journal entries yet.')
        ->assertNoJavaScriptErrors();

    $page->fill('#compose-body', 'Settled on the ore port.')
        ->fill('#compose-next', 'Build a city next session.')
        ->click('@add-journal-entry-button')
        ->assertSee('Journal entry added.')
        ->assertSee('Settled on the ore port.')
        ->assertSee('Build a city next session.')
        ->assertSee('Up next')
        ->assertNoJavaScriptErrors();

    $entry = $game->journalEntries()->first();

    expect($entry)->not->toBeNull();

    $page->click('@edit-journal-entry-button-'.$entry->id)
        ->fill('#edit-'.$entry->id.'-body', 'Settled on the brick port.')
        ->click('@save-journal-entry-button-'.$entry->id)
        ->assertSee('Journal entry updated.')
        ->assertSee('Settled on the brick port.')
        ->assertNoJavaScriptErrors();

    $page->click('@delete-journal-entry-button-'.$entry->id)
        ->assertSee('Journal entry deleted.')
        ->assertDontSee('Settled on the brick port.')
        ->assertSee('No journal entries yet.')
        ->assertNoJavaScriptErrors();

    expect($entry->fresh())->toBeNull();
});
