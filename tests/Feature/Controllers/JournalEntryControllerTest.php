<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\User;

it('lets the owner add an entry and see body newest first on show', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('games.show', $game)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Opened with a wood and brick settlement.',
        ])
        ->assertRedirectToRoute('games.show', $game)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Journal entry added.')]);

    $this->travel(1)->minute();

    $this->actingAs($user)
        ->fromRoute('games.show', $game)
        ->post(route('games.journal-entries.store', $game), [
            'body' => 'Cities went down early.',
        ])
        ->assertRedirectToRoute('games.show', $game);

    $newer = $game->journalEntries()->where('body', 'Cities went down early.')->first();
    $older = $game->journalEntries()->where('body', 'Opened with a wood and brick settlement.')->first();

    expect($newer)->not->toBeNull()
        ->and($older)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('games.show', $game))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/show')
            ->has('journalEntries', 2)
            ->has('journalEntries.0', fn ($entry) => $entry
                ->where('id', $newer->id)
                ->where('body', 'Cities went down early.')
                ->missing('next')
                ->has('createdAt')
                ->has('updatedAt'))
            ->has('journalEntries.1', fn ($entry) => $entry
                ->where('id', $older->id)
                ->where('body', 'Opened with a wood and brick settlement.')
                ->missing('next')
                ->has('createdAt')
                ->has('updatedAt'))
            ->missing('resume'));
});

it('renders the create modal for the owner', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => '1'])
        ->get(route('games.journal-entries.create', $game))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/journal-entries/create')
            ->has('game', fn ($props) => $props
                ->where('id', $game->id)
                ->where('title', $game->title)));
});

it('renders the show modal for the owner', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $entry = JournalEntry::factory()->recycle($game)->create([
        'body' => 'Settled on the ore port.',
    ]);

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => '1'])
        ->get(route('games.journal-entries.show', [$game, $entry]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/journal-entries/show')
            ->has('journalEntry', fn ($props) => $props
                ->where('id', $entry->id)
                ->where('body', 'Settled on the ore port.')
                ->missing('next')
                ->has('createdAt')
                ->has('updatedAt')));
});

it('lets the owner update a journal entry', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $entry = JournalEntry::factory()->recycle($game)->create([
        'body' => 'Original sitting',
    ]);

    $this->actingAs($user)
        ->fromRoute('games.show', $game)
        ->put(route('games.journal-entries.update', [$game, $entry]), [
            'body' => 'Corrected sitting',
        ])
        ->assertRedirectToRoute('games.show', $game)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Journal entry updated.')]);

    expect($entry->refresh()->body)->toBe('Corrected sitting');
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
