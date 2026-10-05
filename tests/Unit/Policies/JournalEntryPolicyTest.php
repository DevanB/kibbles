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
