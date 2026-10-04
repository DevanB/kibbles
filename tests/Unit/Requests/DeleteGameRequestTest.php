<?php

declare(strict_types=1);

use App\Http\Requests\DeleteGameRequest;
use App\Models\Game;
use App\Models\User;
use Illuminate\Routing\Route;

it('authorizes the owner to delete a game', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->recycle($user)->create();

    $request = deleteGameRequestWithRoute($game);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue();
});

it('denies another user from deleting a game', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $game = Game::factory()->recycle($owner)->create();

    $request = deleteGameRequestWithRoute($game);
    $request->setUserResolver(fn () => $intruder);

    expect($request->authorize())->toBeFalse();
});

it('denies guests from deleting a game', function (): void {
    $game = Game::factory()->create();

    $request = deleteGameRequestWithRoute($game);
    $request->setUserResolver(fn (): null => null);

    expect($request->authorize())->toBeFalse();
});

it('denies deletes when the route game is missing', function (): void {
    $user = User::factory()->create();

    $request = deleteGameRequestWithRoute(null);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeFalse();
});

function deleteGameRequestWithRoute(?Game $game): DeleteGameRequest
{
    $request = DeleteGameRequest::create('/games/'.$game?->id, 'DELETE');
    $request->setContainer(app());

    $route = new Route('DELETE', 'games/{game}', []);
    $route->bind($request);

    if ($game instanceof Game) {
        $route->setParameter('game', $game);
    }

    $request->setRouteResolver(fn (): Route => $route);

    return $request;
}
