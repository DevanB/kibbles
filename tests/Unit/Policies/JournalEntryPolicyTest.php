<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\User;
use App\Policies\JournalEntryPolicy;

it('allows authenticated users to view any journal entries', function (): void {
    $policy = new JournalEntryPolicy;

    expect($policy->viewAny())->toBeTrue();
});

it('allows owners to create entries on their game and to view, update, and delete them', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->recycle($user)->create();
    $entry = JournalEntry::factory()->recycle($game)->create();
    $policy = new JournalEntryPolicy;

    expect($policy->create($user, $game))->toBeTrue()
        ->and($policy->view($user, $entry))->toBeTrue()
        ->and($policy->update($user, $entry))->toBeTrue()
        ->and($policy->delete($user, $entry))->toBeTrue();
});

it('denies other users from creating, viewing, updating, or deleting a journal entry', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $game = Game::factory()->recycle($owner)->create();
    $entry = JournalEntry::factory()->recycle($game)->create();
    $policy = new JournalEntryPolicy;

    expect($policy->create($intruder, $game))->toBeFalse()
        ->and($policy->view($intruder, $entry))->toBeFalse()
        ->and($policy->update($intruder, $entry))->toBeFalse()
        ->and($policy->delete($intruder, $entry))->toBeFalse();
});
