<?php

declare(strict_types=1);

use App\Http\Requests\CreateGameRequest;
use App\Models\User;

it('authorizes an authenticated user to create a game', function (): void {
    $user = User::factory()->create();

    $request = CreateGameRequest::create('/games', 'POST');
    $request->setUserResolver(fn () => $user);
    $request->setContainer(app());

    expect($request->authorize())->toBeTrue();
});

it('denies guests from creating a game', function (): void {
    $request = CreateGameRequest::create('/games', 'POST');
    $request->setUserResolver(fn (): null => null);
    $request->setContainer(app());

    expect($request->authorize())->toBeFalse();
});
