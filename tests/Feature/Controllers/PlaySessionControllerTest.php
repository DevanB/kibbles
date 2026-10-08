<?php

declare(strict_types=1);

use App\Actions\StartPlaySession;
use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\PlaySession;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

it('redirects guests to login', function (string $method, string $route, array $parameters = []): void {
    $this->{$method}(route($route, $parameters))->assertRedirectToRoute('login');
})->with([
    'start' => ['post', 'games.play-sessions.start', ['game' => '00000000-0000-0000-0000-000000000001']],
    'create' => ['get', 'games.play-sessions.create', ['game' => '00000000-0000-0000-0000-000000000001']],
    'store' => ['post', 'games.play-sessions.store', ['game' => '00000000-0000-0000-0000-000000000001']],
    'stop' => ['get', 'games.play-sessions.stop', ['game' => '00000000-0000-0000-0000-000000000001', 'play_session' => '00000000-0000-0000-0000-000000000002']],
    'finish' => ['post', 'games.play-sessions.finish', ['game' => '00000000-0000-0000-0000-000000000001', 'play_session' => '00000000-0000-0000-0000-000000000002']],
    'edit' => ['get', 'games.play-sessions.edit', ['game' => '00000000-0000-0000-0000-000000000001', 'play_session' => '00000000-0000-0000-0000-000000000002']],
    'update' => ['put', 'games.play-sessions.update', ['game' => '00000000-0000-0000-0000-000000000001', 'play_session' => '00000000-0000-0000-0000-000000000002']],
    'destroy' => ['delete', 'games.play-sessions.destroy', ['game' => '00000000-0000-0000-0000-000000000001', 'play_session' => '00000000-0000-0000-0000-000000000002']],
]);

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->post(route('games.play-sessions.start', $game))
        ->assertRedirectToRoute('verification.notice');
});

it('forbids another user from starting or viewing play session modals', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create();
    $session = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $owner->id,
    ]);

    $this->actingAs($intruder)
        ->post(route('games.play-sessions.start', $game))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->get(route('games.play-sessions.create', $game))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->get(route('games.play-sessions.stop', [$game, $session]))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->get(route('games.play-sessions.edit', [$game, $session]))
        ->assertForbidden();
});

it('forbids another user from storing, updating, or deleting a play session', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create();
    $session = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $owner->id,
        'started_at' => '2026-01-15 15:30:00',
        'ended_at' => '2026-01-15 16:30:00',
    ]);

    $this->actingAs($intruder)
        ->post(route('games.play-sessions.store', $game), [
            'started_at' => '2026-01-15T10:30:00',
            'ended_at' => '2026-01-15T11:30:00',
            'timezone' => 'America/New_York',
        ])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->put(route('games.play-sessions.update', [$game, $session]), [
            'started_at' => '2026-01-15T10:30:00',
            'ended_at' => '2026-01-15T11:30:00',
            'timezone' => 'America/New_York',
        ])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('games.play-sessions.destroy', [$game, $session]))
        ->assertForbidden();

    $this->assertModelExists($session);
});

it('returns 404 when a session is addressed under a different owned game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $otherGame = Game::factory()->recycle($user)->create();
    $session = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('games.play-sessions.stop', [$otherGame, $session]))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('games.play-sessions.edit', [$otherGame, $session]))
        ->assertNotFound();

    $this->actingAs($user)
        ->put(route('games.play-sessions.update', [$otherGame, $session]), [
            'started_at' => '2026-01-15T10:30:00',
            'ended_at' => '2026-01-15T11:30:00',
            'timezone' => 'America/New_York',
        ])
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('games.play-sessions.destroy', [$otherGame, $session]))
        ->assertNotFound();

    $this->assertModelExists($session);
    expect($session->refresh()->game()->is($game))->toBeTrue();
});

it('returns 404 for a missing play session', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->get(route('games.play-sessions.edit', [$game, Str::uuid()->toString()]))
        ->assertNotFound();
});

it('rejects starting a second session while another game is open', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $openGame = Game::factory()->recycle($user)->create(['title' => 'Hades']);
    $other = Game::factory()->recycle($user)->create(['title' => 'Celeste']);
    PlaySession::factory()->create([
        'game_id' => $openGame->id,
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    $this->actingAs($user)
        ->fromRoute('games.show', $other)
        ->post(route('games.play-sessions.start', $other))
        ->assertRedirectToRoute('games.show', $other)
        ->assertSessionHasErrors(['play_session' => 'Stop your session on Hades first.']);

    expect($other->playSessions()->count())->toBe(0);
});

it('maps a unique open-session race to a validation error', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $openGame = Game::factory()->recycle($user)->create(['title' => 'Hades']);
    $other = Game::factory()->recycle($user)->create(['title' => 'Celeste']);
    PlaySession::factory()->create([
        'game_id' => $openGame->id,
        'user_id' => $user->id,
        'ended_at' => null,
    ]);

    try {
        resolve(StartPlaySession::class)->handle($other);
        $this->fail('Expected a validation exception for an open-session race.');
    } catch (ValidationException $validationException) {
        expect($validationException->status)->toBe(422)
            ->and($validationException->errors())->toBe([
                'play_session' => ['Stop your session on Hades first.'],
            ]);
    }
});

it('requires start and end times when storing a past session', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('games.play-sessions.create', $game)
        ->post(route('games.play-sessions.store', $game), [])
        ->assertRedirectToRoute('games.play-sessions.create', $game)
        ->assertSessionHasErrors([
            'started_at' => 'A start time is required.',
            'ended_at' => 'An end time is required.',
            'timezone' => 'A timezone is required.',
        ]);
});

it('rejects an end time before the start time', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('games.play-sessions.create', $game)
        ->post(route('games.play-sessions.store', $game), [
            'started_at' => '2026-01-15T12:00:00',
            'ended_at' => '2026-01-15T11:00:00',
            'timezone' => 'UTC',
        ])
        ->assertRedirectToRoute('games.play-sessions.create', $game)
        ->assertSessionHasErrors(['ended_at' => 'The end time must be at or after the start time.']);
});

it('rejects stopping an already closed session', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $session = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'started_at' => now()->subHours(2),
        'ended_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->fromRoute('games.show', $game)
        ->post(route('games.play-sessions.finish', [$game, $session]))
        ->assertRedirectToRoute('games.show', $game)
        ->assertSessionHasErrors(['play_session' => 'This session is already stopped.']);
});

it('rejects reopening a session on update', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $session = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'started_at' => '2026-01-15 15:30:00',
        'ended_at' => '2026-01-15 16:30:00',
    ]);

    $this->actingAs($user)
        ->fromRoute('games.play-sessions.edit', [$game, $session])
        ->put(route('games.play-sessions.update', [$game, $session]), [
            'started_at' => '2026-01-15T10:30:00',
            'timezone' => 'America/New_York',
        ])
        ->assertRedirectToRoute('games.play-sessions.edit', [$game, $session])
        ->assertSessionHasErrors(['ended_at' => 'An end time is required.']);

    expect($session->refresh()->ended_at)->not->toBeNull();
});

it('keeps the journal entry when a play session is deleted', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $session = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
    ]);
    $entry = JournalEntry::factory()->recycle($game)->create([
        'play_session_id' => $session->id,
    ]);

    $this->actingAs($user)
        ->delete(route('games.play-sessions.destroy', [$game, $session]))
        ->assertRedirectToRoute('games.show', $game);

    $this->assertModelMissing($session);
    expect($entry->refresh()->play_session_id)->toBeNull();
});

it('removes play sessions when the game is deleted', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $session = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->delete(route('games.destroy', $game))
        ->assertRedirectToRoute('games.index');

    $this->assertModelMissing($game);
    $this->assertModelMissing($session);
});

it('rejects a stop journal body longer than 10000 characters', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();
    $session = PlaySession::factory()->open()->create([
        'game_id' => $game->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->fromRoute('games.show', $game)
        ->post(route('games.play-sessions.finish', [$game, $session]), [
            'body' => str_repeat('a', 10001),
        ])
        ->assertRedirectToRoute('games.show', $game)
        ->assertSessionHasErrors(['body' => 'The body may not be greater than 10000 characters.']);

    expect($session->refresh()->ended_at)->toBeNull()
        ->and($game->journalEntries()->count())->toBe(0);
});
