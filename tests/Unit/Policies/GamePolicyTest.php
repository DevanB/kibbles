<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use App\Policies\GamePolicy;

it('allows authenticated users to view any and create games', function (): void {
    $user = User::factory()->create();
    $policy = new GamePolicy;

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->create($user))->toBeTrue();
});

it('allows owners to view, update, and delete their games', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->recycle($user)->create();
    $policy = new GamePolicy;

    expect($policy->view($user, $game))->toBeTrue()
        ->and($policy->update($user, $game))->toBeTrue()
        ->and($policy->delete($user, $game))->toBeTrue();
});

it('denies other users from viewing, updating, or deleting a game', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $game = Game::factory()->recycle($owner)->create();
    $policy = new GamePolicy;

    expect($policy->view($intruder, $game))->toBeFalse()
        ->and($policy->update($intruder, $game))->toBeFalse()
        ->and($policy->delete($intruder, $game))->toBeFalse();
});
