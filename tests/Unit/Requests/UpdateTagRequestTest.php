<?php

declare(strict_types=1);

use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Routing\Route;

it('denies updates when the route tag is missing', function (): void {
    $user = User::factory()->create();

    $request = updateTagRequestWithRoute(null);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeFalse();
});

it('denies updates when the user is missing', function (): void {
    $tag = Tag::factory()->create();

    $request = updateTagRequestWithRoute($tag);
    $request->setUserResolver(fn (): null => null);

    expect($request->authorize())->toBeFalse();
});

it('requires an authenticated user before validating a rename', function (): void {
    $tag = Tag::factory()->create();
    $request = updateTagRequestWithRoute($tag);
    $request->setUserResolver(fn (): null => null);

    expect(fn (): array => $request->rules())->toThrow(LogicException::class);
});

it('requires a tag route parameter before validating a rename', function (): void {
    $user = User::factory()->create();
    $request = updateTagRequestWithRoute(null);
    $request->setUserResolver(fn () => $user);

    expect(fn (): array => $request->rules())->toThrow(LogicException::class);
});

function updateTagRequestWithRoute(?Tag $tag): UpdateTagRequest
{
    $request = UpdateTagRequest::create('/tags/'.$tag?->id, 'PUT');
    $request->setContainer(app());

    $route = new Route('PUT', 'tags/{tag}', []);
    $route->bind($request);

    if ($tag instanceof Tag) {
        $route->setParameter('tag', $tag);
    }

    $request->setRouteResolver(fn (): Route => $route);

    return $request;
}
