<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\PlaySession;
use App\Models\User;

it('starts, stops, and journals a session from the show page', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Hades']);

    $this->actingAs($user);

    $page = visit(route('games.show', $game));

    $page->assertSee('No play sessions yet')
        ->assertSee('No time logged')
        ->assertSee('Log a session after you play.')
        ->assertNoJavaScriptErrors();

    $page->click('@start-play-session-button')
        ->assertSee('Session started.')
        ->assertSee('In Progress')
        ->assertSee('now · Open')
        ->screenshot(filename: 'pr-b-show-open-session')
        ->assertNoJavaScriptErrors();

    expect($game->refresh()->status)->toBe(GameStatus::InProgress);

    $session = $game->playSessions()->open()->first();

    expect($session)->not->toBeNull();

    $page->click('@stop-play-session-button')
        ->assertSee('Stop Session')
        ->screenshot(filename: 'pr-b-stop-modal')
        ->click('@save-play-session-button')
        ->assertSee('Session saved.')
        ->assertSee('0m')
        ->assertVisible('@play-session-'.$session->id)
        ->assertNoJavaScriptErrors();

    expect($session->refresh()->ended_at)->not->toBeNull()
        ->and($game->journalEntries()->count())->toBe(0);

    $page->click('@start-play-session-button')
        ->assertSee('Session started.')
        ->assertSee('now · Open')
        ->assertNoJavaScriptErrors();

    $open = $game->playSessions()->open()->first();

    expect($open)->not->toBeNull();

    $page->click('@stop-play-session-button')
        ->assertSee('Stop Session')
        ->fill('#stop-session-body', 'Cleared Tartarus.')
        ->click('@save-play-session-button')
        ->assertSee('Session saved.')
        ->assertQueryStringHas('tab', 'journal')
        ->assertSee('Journal Entries')
        ->assertNoJavaScriptErrors();

    $entry = $game->journalEntries()->first();

    expect($entry)->not->toBeNull()
        ->and($entry->play_session_id)->toBe($open->id)
        ->and($entry->playSession?->is($open))->toBeTrue()
        ->and($open->refresh()->ended_at)->not->toBeNull();

    $page->assertVisible('@journal-entry-'.$entry->id)
        ->refresh()
        ->assertQueryStringHas('tab', 'journal')
        ->assertVisible('@journal-entry-'.$entry->id)
        ->screenshot(filename: 'pr-b-show-journal-tab')
        ->click('@game-tab-sessions')
        ->assertVisible('@play-session-journal-'.$open->id)
        ->screenshot(filename: 'pr-b-show-history-journal-chip')
        ->assertNoJavaScriptErrors();
});

it('adds, edits, and deletes a past session', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Celeste']);

    $this->actingAs($user);

    $page = visit(route('games.show', $game))->withTimezone('America/New_York');

    $page->click('@add-play-session-button')
        ->assertSee('Add Session')
        ->click('.im-close-button')
        ->assertSee('No play sessions yet')
        ->assertQueryStringMissing('tab')
        ->click('@add-play-session-button')
        ->assertSee('Add Session')
        ->assertVisible('@play-session-started-at')
        ->assertVisible('@play-session-ended-at')
        ->screenshot(filename: 'pr-b-add-session-modal')
        ->fill('#play-session-started-at', '2026-01-15T10:30')
        ->fill('#play-session-ended-at', '2026-01-15T12:44')
        ->click('@save-play-session-button')
        ->assertSee('Session saved.')
        ->assertSee('Jan 15, 10:30 AM – 12:44 PM · 2h 14m')
        ->assertQueryStringMissing('tab')
        ->assertNoJavaScriptErrors();

    $session = $game->playSessions()->first();

    expect($session)->not->toBeNull()
        ->and($session->started_at->toDateTimeString())->toBe('2026-01-15 15:30:00')
        ->and($game->refresh()->status)->toBe(GameStatus::Backlog);

    $page->click('@edit-play-session-button-'.$session->id)
        ->assertSee('Edit Session')
        ->assertValue('#play-session-started-at', '2026-01-15T10:30')
        ->assertValue('#play-session-ended-at', '2026-01-15T12:44')
        ->screenshot(filename: 'pr-b-edit-session-modal')
        ->fill('#play-session-ended-at', '2026-01-15T10:44')
        ->click('@save-play-session-changes-button')
        ->assertSee('Session updated.')
        ->assertSee('Jan 15, 10:30 – 10:44 AM · 14m')
        ->assertNoJavaScriptErrors();

    $page->click('@play-session-actions-button-'.$session->id)
        ->click('@delete-play-session-button-'.$session->id)
        ->assertSee('Delete Session?')
        ->assertSee('This will permanently delete this play session. Linked journal entries are kept.')
        ->screenshot(filename: 'pr-b-delete-session-confirm')
        ->click('@confirm-delete-play-session-button-'.$session->id)
        ->assertSee('Session deleted.')
        ->assertSee('No play sessions yet')
        ->assertNoJavaScriptErrors();

    expect($session->fresh())->toBeNull();
});

it('shows a playing badge and an other-game banner', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $hades = Game::factory()->recycle($user)->catalogLinked()->create(['title' => 'Hades']);
    $celeste = Game::factory()->recycle($user)->create(['title' => 'Celeste']);
    PlaySession::factory()->create([
        'game_id' => $hades->id,
        'user_id' => $user->id,
        'started_at' => '2026-01-15 15:00:00',
        'ended_at' => '2026-01-15 17:00:00',
    ]);
    PlaySession::factory()->open()->create([
        'game_id' => $hades->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    $index = visit(route('games.index'));

    $index->assertSee('Playing')
        ->assertVisible('@game-playing-'.$hades->id)
        ->screenshot(filename: 'pr-b-index-playing-badge')
        ->assertNoJavaScriptErrors();

    $show = visit(route('games.show', $celeste));

    $show->assertSee("You're playing Hades.")
        ->assertVisible('@open-session-game-link')
        ->screenshot(filename: 'pr-b-other-game-banner')
        ->click('@open-session-game-link')
        ->assertPathIs('/games/'.$hades->id)
        ->assertSee('Hades')
        ->assertSee('now · Open')
        ->assertSeeIn('@play-time-total', '2h')
        ->assertNoJavaScriptErrors();
});

it('keeps a linked journal after the session is deleted', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Hades']);
    $session = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'started_at' => now()->subHours(2),
        'ended_at' => now(),
    ]);
    $entry = JournalEntry::factory()->recycle($game)->create([
        'body' => 'Duo boon finally clicked.',
        'play_session_id' => $session->id,
    ]);

    $this->actingAs($user);

    $page = visit(route('games.show', $game));

    $page->assertSee('Journal')
        ->click('@play-session-journal-'.$session->id)
        ->assertSee('Duo boon finally clicked.')
        ->assertNoJavaScriptErrors();

    $page = visit(route('games.show', $game));

    $page->click('@play-session-actions-button-'.$session->id)
        ->click('@delete-play-session-button-'.$session->id)
        ->assertSee('Delete Session?')
        ->click('@confirm-delete-play-session-button-'.$session->id)
        ->assertSee('Session deleted.')
        ->assertSee('No play sessions yet')
        ->click('@game-tab-journal')
        ->assertVisible('@journal-entry-'.$entry->id)
        ->click('@journal-entry-'.$entry->id)
        ->assertSee('Duo boon finally clicked.')
        ->assertNoJavaScriptErrors();

    expect($entry->fresh())->not->toBeNull()
        ->and($entry->refresh()->play_session_id)->toBeNull();
});

it('formats session ranges by local day and year', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Celeste']);
    PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'started_at' => '2026-01-15 15:30:00',
        'ended_at' => '2026-01-15 17:44:00',
    ]);
    PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'started_at' => '2026-01-15 04:00:00',
        'ended_at' => '2026-01-15 06:12:00',
    ]);
    PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'started_at' => '2025-10-06 03:00:00',
        'ended_at' => '2025-10-06 05:14:00',
    ]);

    $this->actingAs($user);

    visit(route('games.show', $game))
        ->withTimezone('America/New_York')
        ->assertSee('Jan 15, 10:30 AM – 12:44 PM · 2h 14m')
        ->assertSee('Jan 14, 11:00 PM – Jan 15, 1:12 AM · 2h 12m')
        ->assertSee('Oct 5, 2025, 11:00 PM – Oct 6, 2025, 1:14 AM · 2h 14m')
        ->assertNoJavaScriptErrors();
});
