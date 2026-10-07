<?php

declare(strict_types=1);

use App\Http\Requests\SearchRawgGamesRequest;
use App\Models\User;

it('authorizes an authenticated user to search the catalog', function (): void {
    $user = User::factory()->create();

    $request = SearchRawgGamesRequest::create('/rawg/games', 'GET');
    $request->setUserResolver(fn () => $user);
    $request->setContainer(app());

    expect($request->authorize())->toBeTrue();
});

it('denies guests from searching the catalog', function (): void {
    $request = SearchRawgGamesRequest::create('/rawg/games', 'GET');
    $request->setUserResolver(fn (): null => null);
    $request->setContainer(app());

    expect($request->authorize())->toBeFalse();
});
