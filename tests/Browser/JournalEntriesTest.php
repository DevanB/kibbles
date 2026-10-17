<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\PlaySession;
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

it('may link, change, and unlink a play session from journal entry modals', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Hades']);
    $first = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'started_at' => '2026-01-15 15:30:00',
        'ended_at' => '2026-01-15 17:44:00',
    ]);
    $second = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'started_at' => '2026-01-16 15:00:00',
        'ended_at' => '2026-01-16 16:00:00',
    ]);
    $alreadyLinked = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'started_at' => '2026-01-17 15:00:00',
        'ended_at' => '2026-01-17 16:00:00',
    ]);
    JournalEntry::factory()->recycle($game)->create([
        'body' => 'Already attached.',
        'play_session_id' => $alreadyLinked->id,
    ]);

    $this->actingAs($user);

    $page = visit(route('games.show', ['game' => $game, 'tab' => 'journal']))
        ->withTimezone('America/New_York');

    $page->click('@create-journal-entry-button')
        ->assertSee('Add Entry')
        ->assertVisible('@journal-entry-play-session')
        ->assertSee('Play session')
        ->assertNoJavaScriptErrors();

    $createOptions = $page->script(<<<'JS'
        (() => [...document.querySelectorAll('#play_session_id option')].map((option) => option.value))()
    JS);

    expect($createOptions)->toContain('none', $first->id, $second->id)
        ->and($createOptions)->not->toContain($alreadyLinked->id);

    $page->fill('#compose-body', 'Cleared Tartarus.')
        ->select('play_session_id', $first->id)
        ->click('@add-journal-entry-button')
        ->assertSee('Journal entry added.')
        ->assertSee('Jan 15, 2026, 10:30 AM – 12:44 PM')
        ->assertNoJavaScriptErrors();

    $entry = $game->journalEntries()->where('body', 'Cleared Tartarus.')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->play_session_id)->toBe($first->id);

    $page->click('@game-tab-sessions')
        ->assertVisible('@play-session-journal-'.$first->id)
        ->click('@play-session-journal-'.$first->id)
        ->assertSee('Cleared Tartarus.')
        ->assertNoJavaScriptErrors();

    $page = visit(route('games.show', ['game' => $game, 'tab' => 'journal']))
        ->withTimezone('America/New_York');

    $page->click('@journal-entry-'.$entry->id)
        ->click('@edit-journal-entry-button-'.$entry->id)
        ->assertSee('Save Entry')
        ->assertVisible('@journal-entry-play-session')
        ->assertNoJavaScriptErrors();

    $editOptions = $page->script(<<<'JS'
        (() => [...document.querySelectorAll('#play_session_id option')].map((option) => option.value))()
    JS);

    expect($editOptions)->toContain('none', $first->id, $second->id)
        ->and($editOptions)->not->toContain($alreadyLinked->id);

    $page->select('play_session_id', $second->id)
        ->click('@save-journal-entry-button-'.$entry->id)
        ->assertSee('Journal entry updated.')
        ->assertSee('Jan 16, 2026, 10:00 – 11:00 AM')
        ->assertNoJavaScriptErrors();

    expect($entry->refresh()->play_session_id)->toBe($second->id);

    $page->click('@game-tab-sessions')
        ->assertVisible('@play-session-journal-'.$second->id)
        ->assertNoJavaScriptErrors();

    $page = visit(route('games.show', ['game' => $game, 'tab' => 'journal']))
        ->withTimezone('America/New_York');

    $page->click('@journal-entry-'.$entry->id)
        ->click('@edit-journal-entry-button-'.$entry->id)
        ->select('play_session_id', 'none')
        ->click('@save-journal-entry-button-'.$entry->id)
        ->assertSee('Journal entry updated.')
        ->assertNoJavaScriptErrors();

    expect($entry->refresh()->play_session_id)->toBeNull();

    $page->click('@game-tab-sessions')
        ->assertVisible('@edit-play-session-button-'.$second->id)
        ->assertNoJavaScriptErrors();

    expect($page->script(<<<JS
        (() => document.querySelector('[data-test="play-session-journal-{$second->id}"]') === null)()
    JS))->toBeTrue();
});
