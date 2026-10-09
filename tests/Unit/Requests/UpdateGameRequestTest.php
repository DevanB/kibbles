<?php

declare(strict_types=1);

use App\Http\Requests\UpdateGameRequest;
use App\Models\Game;
use App\Models\User;
use Illuminate\Routing\Route;

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
