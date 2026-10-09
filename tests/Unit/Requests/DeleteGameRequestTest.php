<?php

declare(strict_types=1);

use App\Http\Requests\DeleteGameRequest;
use App\Models\Game;
use App\Models\User;
use Illuminate\Routing\Route;

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
