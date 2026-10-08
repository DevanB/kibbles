<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\PlaySession;
use App\Models\User;
use App\Policies\PlaySessionPolicy;

it('allows authenticated users to view any play sessions', function (): void {
    $policy = new PlaySessionPolicy;

    expect($policy->viewAny())->toBeTrue();
});

it('denies other users from creating, viewing, updating, or deleting a play session', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $game = Game::factory()->recycle($owner)->create();
    $session = PlaySession::factory()->create([
        'game_id' => $game->id,
        'user_id' => $owner->id,
    ]);
    $policy = new PlaySessionPolicy;

    expect($policy->create($intruder, $game))->toBeFalse()
        ->and($policy->view($intruder, $session))->toBeFalse()
        ->and($policy->update($intruder, $session))->toBeFalse()
        ->and($policy->delete($intruder, $session))->toBeFalse();
});
