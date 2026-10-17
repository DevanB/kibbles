<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\PlaySession;
use App\Models\User;

it('redirects guests to login', function (string $method, string $route, array $parameters = []): void {
    $this->{$method}(route($route, $parameters))->assertRedirectToRoute('login');
})->with([
    'create' => ['get', 'games.journal-entries.create', ['game' => '00000000-0000-0000-0000-000000000001']],
    'store' => ['post', 'games.journal-entries.store', ['game' => '00000000-0000-0000-0000-000000000001']],
    'show' => ['get', 'games.journal-entries.show', ['game' => '00000000-0000-0000-0000-000000000001', 'journal_entry' => '00000000-0000-0000-0000-000000000002']],
    'edit' => ['get', 'games.journal-entries.edit', ['game' => '00000000-0000-0000-0000-000000000001', 'journal_entry' => '00000000-0000-0000-0000-000000000002']],
    'update' => ['put', 'games.journal-entries.update', ['game' => '00000000-0000-0000-0000-000000000001', 'journal_entry' => '00000000-0000-0000-0000-000000000002']],
    'destroy' => ['delete', 'games.journal-entries.destroy', ['game' => '00000000-0000-0000-0000-000000000001', 'journal_entry' => '00000000-0000-0000-0000-000000000002']],
]);

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->get(route('games.journal-entries.create', $game))
        ->assertRedirectToRoute('verification.notice');
});

it('forbids another user from creating or viewing journal entry modals', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create();
    $entry = JournalEntry::factory()->recycle($game)->create();

    $this->actingAs($intruder)
        ->get(route('games.journal-entries.create', $game))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->get(route('games.journal-entries.show', [$game, $entry]))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->get(route('games.journal-entries.edit', [$game, $entry]))
        ->assertForbidden();
});

it('forbids another user from storing a journal entry', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create();

    $this->actingAs($intruder)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Stolen note',
        ])
        ->assertForbidden();

    expect($game->journalEntries()->count())->toBe(0);
});

it('forbids another user from updating a journal entry', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create();
    $entry = JournalEntry::factory()->recycle($game)->create([
        'body' => 'Owned sitting',
    ]);

    $this->actingAs($intruder)
        ->put(route('games.journal-entries.update', [$game, $entry]), [
            'body' => 'Stolen sitting',
        ])
        ->assertForbidden();

    expect($entry->refresh()->body)->toBe('Owned sitting');
});

it('forbids another user from deleting a journal entry', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create();
    $entry = JournalEntry::factory()->recycle($game)->create();

    $this->actingAs($intruder)
        ->delete(route('games.journal-entries.destroy', [$game, $entry]))
        ->assertForbidden();

    $this->assertModelExists($entry);
});

it('returns 404 when an entry is addressed under a different owned game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $otherGame = Game::factory()->recycle($user)->create();
    $entry = JournalEntry::factory()->recycle($game)->create();

    $this->actingAs($user)
        ->get(route('games.journal-entries.show', [$otherGame, $entry]))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('games.journal-entries.edit', [$otherGame, $entry]))
        ->assertNotFound();

    $this->actingAs($user)
        ->put(route('games.journal-entries.update', [$otherGame, $entry]), [
            'body' => 'Moved sitting',
        ])
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('games.journal-entries.destroy', [$otherGame, $entry]))
        ->assertNotFound();

    $this->assertModelExists($entry);
    expect($entry->refresh()->game()->is($game))->toBeTrue();
});

it('removes journal entries when the game is deleted', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $entry = JournalEntry::factory()->recycle($game)->create();

    $this->actingAs($user)
        ->delete(route('games.destroy', $game))
        ->assertRedirectToRoute('games.index');

    $this->assertModelMissing($game);
    $this->assertModelMissing($entry);
});

it('rejects a body longer than 10000 characters', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('games.show', $game)
        ->post(route('games.journal-entries.store', $game), [
            'body' => str_repeat('a', 10001),
        ])
        ->assertRedirectToRoute('games.show', $game)
        ->assertSessionHasErrors(['body' => 'The body may not be greater than 10000 characters.']);

    expect($game->journalEntries()->count())->toBe(0);
});

it('rejects linking a play session from another game when creating an entry', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $otherGame = Game::factory()->recycle($user)->create();
    $foreignSession = PlaySession::factory()->create([
        'game_id' => $otherGame->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->fromRoute('games.journal-entries.create', $game)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Wrong table.',
            'play_session_id' => $foreignSession->id,
        ])
        ->assertRedirectToRoute('games.journal-entries.create', $game)
        ->assertSessionHasErrors(['play_session_id' => 'The play session must belong to this game.']);

    expect($game->journalEntries()->count())->toBe(0)
        ->and($foreignSession->refresh()->journalEntry)->toBeNull();
});

it('rejects linking a play session that already has a journal entry when creating an entry', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $session = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
    ]);
    $existing = JournalEntry::factory()->recycle($game)->create([
        'body' => 'Already attached.',
        'play_session_id' => $session->id,
    ]);

    $this->actingAs($user)
        ->fromRoute('games.journal-entries.create', $game)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Second note.',
            'play_session_id' => $session->id,
        ])
        ->assertRedirectToRoute('games.journal-entries.create', $game)
        ->assertSessionHasErrors(['play_session_id' => 'The play session is already linked to a journal entry.']);

    expect($game->journalEntries()->count())->toBe(1)
        ->and($existing->refresh()->play_session_id)->toBe($session->id);
});

it('rejects linking a play session from another game when updating an entry', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $otherGame = Game::factory()->recycle($user)->create();
    $entry = JournalEntry::factory()->recycle($game)->create([
        'body' => 'Owned sitting',
    ]);
    $foreignSession = PlaySession::factory()->create([
        'game_id' => $otherGame->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->fromRoute('games.journal-entries.edit', [$game, $entry])
        ->put(route('games.journal-entries.update', [$game, $entry]), [
            'body' => 'Owned sitting',
            'play_session_id' => $foreignSession->id,
        ])
        ->assertRedirectToRoute('games.journal-entries.edit', [$game, $entry])
        ->assertSessionHasErrors(['play_session_id' => 'The play session must belong to this game.']);

    expect($entry->refresh()->play_session_id)->toBeNull()
        ->and($entry->body)->toBe('Owned sitting');
});

it('rejects linking a play session that already has a journal entry when updating an entry', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $taken = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
    ]);
    JournalEntry::factory()->recycle($game)->create([
        'body' => 'Keeps this session.',
        'play_session_id' => $taken->id,
    ]);
    $entry = JournalEntry::factory()->recycle($game)->create([
        'body' => 'Looking for a session.',
    ]);

    $this->actingAs($user)
        ->fromRoute('games.journal-entries.edit', [$game, $entry])
        ->put(route('games.journal-entries.update', [$game, $entry]), [
            'body' => 'Looking for a session.',
            'play_session_id' => $taken->id,
        ])
        ->assertRedirectToRoute('games.journal-entries.edit', [$game, $entry])
        ->assertSessionHasErrors(['play_session_id' => 'The play session is already linked to a journal entry.']);

    expect($entry->refresh()->play_session_id)->toBeNull();
});

it('rejects linking a play session that belongs to another user when creating an entry', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $foreignSession = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $intruder->id,
    ]);

    $this->actingAs($user)
        ->fromRoute('games.journal-entries.create', $game)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Stolen clock.',
            'play_session_id' => $foreignSession->id,
        ])
        ->assertRedirectToRoute('games.journal-entries.create', $game)
        ->assertSessionHasErrors(['play_session_id' => 'The play session must belong to this game.']);

    expect($game->journalEntries()->count())->toBe(0);
});

it('rejects an invalid play session id when creating an entry', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('games.journal-entries.create', $game)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Bad id.',
            'play_session_id' => 'not-a-uuid',
        ])
        ->assertRedirectToRoute('games.journal-entries.create', $game)
        ->assertSessionHasErrors(['play_session_id' => 'The play session is invalid.']);

    expect($game->journalEntries()->count())->toBe(0);
});

it('rejects a missing play session when creating an entry', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('games.journal-entries.create', $game)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Ghost session.',
            'play_session_id' => '00000000-0000-0000-0000-000000000099',
        ])
        ->assertRedirectToRoute('games.journal-entries.create', $game)
        ->assertSessionHasErrors(['play_session_id' => 'The play session must belong to this game.']);

    expect($game->journalEntries()->count())->toBe(0);
});
