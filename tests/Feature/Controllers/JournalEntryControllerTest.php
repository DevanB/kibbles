<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\User;

it('lets the owner add an entry and see body, next, newest first, and resume on show', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('games.show', $game)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Opened with a wood and brick settlement.',
            'next' => 'Contest the 8-wheat hex.',
        ])
        ->assertRedirectToRoute('games.show', $game)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Journal entry added.')]);

    $this->travel(1)->minute();

    $this->actingAs($user)
        ->fromRoute('games.show', $game)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Cities went down early.',
            'next' => '',
        ])
        ->assertRedirectToRoute('games.show', $game);

    $newer = $game->journalEntries()->where('body', 'Cities went down early.')->first();
    $older = $game->journalEntries()->where('body', 'Opened with a wood and brick settlement.')->first();

    expect($newer)->not->toBeNull()
        ->and($older)->not->toBeNull()
        ->and($newer->next)->toBeNull()
        ->and($older->next)->toBe('Contest the 8-wheat hex.');

    $this->actingAs($user)
        ->get(route('games.show', $game))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/show')
            ->has('journalEntries', 2)
            ->has('journalEntries.0', fn ($entry) => $entry
                ->where('id', $newer->id)
                ->where('body', 'Cities went down early.')
                ->where('next', null)
                ->has('createdAt')
                ->has('updatedAt'))
            ->has('journalEntries.1', fn ($entry) => $entry
                ->where('id', $older->id)
                ->where('body', 'Opened with a wood and brick settlement.')
                ->where('next', 'Contest the 8-wheat hex.')
                ->has('createdAt')
                ->has('updatedAt'))
            ->where('resume', 'Contest the 8-wheat hex.'));
});

it('stores and wires a blank next as null', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('games.show', $game)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Just a recap.',
            'next' => '',
        ])
        ->assertRedirectToRoute('games.show', $game);

    $entry = $game->journalEntries()->first();

    expect($entry)->not->toBeNull()
        ->and($entry->next)->toBeNull();

    $this->actingAs($user)
        ->get(route('games.show', $game))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/show')
            ->has('journalEntries', 1)
            ->where('journalEntries.0.next', null)
            ->where('resume', null));
});

it('lets the owner update a journal entry', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $entry = JournalEntry::factory()->recycle($game)->create([
        'body' => 'Original sitting',
        'next' => 'Original plan',
    ]);

    $this->actingAs($user)
        ->fromRoute('games.show', $game)
        ->put(route('games.journal-entries.update', [$game, $entry]), [
            'body' => 'Corrected sitting',
            'next' => 'Corrected plan',
        ])
        ->assertRedirectToRoute('games.show', $game)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Journal entry updated.')]);

    expect($entry->refresh()->body)->toBe('Corrected sitting')
        ->and($entry->next)->toBe('Corrected plan');
});

it('lets the owner delete a journal entry', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $entry = JournalEntry::factory()->recycle($game)->create();

    $this->actingAs($user)
        ->fromRoute('games.show', $game)
        ->delete(route('games.journal-entries.destroy', [$game, $entry]))
        ->assertRedirectToRoute('games.show', $game)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Journal entry deleted.')]);

    $this->assertModelMissing($entry);
});

it('forbids another user from storing a journal entry', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create();

    $this->actingAs($intruder)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Stolen note',
            'next' => 'Stolen plan',
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
        'next' => 'Owned plan',
    ]);

    $this->actingAs($intruder)
        ->put(route('games.journal-entries.update', [$game, $entry]), [
            'body' => 'Stolen sitting',
            'next' => 'Stolen plan',
        ])
        ->assertForbidden();

    expect($entry->refresh()->body)->toBe('Owned sitting')
        ->and($entry->next)->toBe('Owned plan');
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
        ->put(route('games.journal-entries.update', [$otherGame, $entry]), [
            'body' => 'Moved sitting',
            'next' => 'Moved plan',
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
