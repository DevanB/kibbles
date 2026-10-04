<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\User;

it('redirects guests to login', function (): void {
    $response = $this->get(route('dashboard'));

    $response->assertRedirectToRoute('login');
});

it('renders the dashboard for a verified user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('recentJournalEntries', 0));
});

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->get(route('dashboard'));

    $response->assertRedirectToRoute('verification.notice');
});

it('lists only the authenticated user journal entries newest first', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $other = User::factory()->withoutTwoFactor()->create();
    $ownedGame = Game::factory()->recycle($user)->create(['title' => 'Catan']);
    $otherGame = Game::factory()->recycle($other)->create(['title' => 'Azul']);

    $older = JournalEntry::factory()->recycle($ownedGame)->create([
        'body' => 'Opened with a wood and brick settlement.',
    ]);

    $this->travel(1)->minute();

    $newer = JournalEntry::factory()->recycle($ownedGame)->create([
        'body' => 'Cities went down early.',
    ]);

    JournalEntry::factory()->recycle($otherGame)->create([
        'body' => 'Someone else played.',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('recentJournalEntries', 2)
            ->has('recentJournalEntries.0', fn ($entry) => $entry
                ->where('id', $newer->id)
                ->where('preview', 'Cities went down early.')
                ->has('createdAt')
                ->has('game', fn ($game) => $game
                    ->where('id', $ownedGame->id)
                    ->where('title', 'Catan')))
            ->has('recentJournalEntries.1', fn ($entry) => $entry
                ->where('id', $older->id)
                ->where('preview', 'Opened with a wood and brick settlement.')
                ->has('createdAt')
                ->has('game', fn ($game) => $game
                    ->where('id', $ownedGame->id)
                    ->where('title', 'Catan'))));
});

it('limits recent journal entries to eight', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    JournalEntry::factory()->recycle($game)->count(10)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('recentJournalEntries', 8));
});
