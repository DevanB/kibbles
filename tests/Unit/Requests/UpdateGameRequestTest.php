<?php

declare(strict_types=1);

use App\Http\Requests\UpdateGameRequest;
use App\Models\Game;
use App\Models\User;
use Illuminate\Routing\Route;

it('authorizes the owner to update a game', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->recycle($user)->create();

    $request = updateGameRequestWithRoute($game);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue();
});

it('denies another user from updating a game', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $game = Game::factory()->recycle($owner)->create();

    $request = updateGameRequestWithRoute($game);
    $request->setUserResolver(fn () => $intruder);

    expect($request->authorize())->toBeFalse();
});

it('denies guests from updating a game', function (): void {
    $game = Game::factory()->create();

    $request = updateGameRequestWithRoute($game);
    $request->setUserResolver(fn (): null => null);

    expect($request->authorize())->toBeFalse();
});

it('denies updates when the route game is missing', function (): void {
    $user = User::factory()->create();

    $request = updateGameRequestWithRoute(null);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeFalse();
});

function updateGameRequestWithRoute(?Game $game): UpdateGameRequest
{
    $request = UpdateGameRequest::create('/games/'.$game?->id, 'PUT');
    $request->setContainer(app());

    $route = new Route('PUT', 'games/{game}', []);
    $route->bind($request);

    if ($game instanceof Game) {
        $route->setParameter('game', $game);
    }

    $request->setRouteResolver(fn (): Route => $route);

    return $request;
}
