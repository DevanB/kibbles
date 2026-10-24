<?php

declare(strict_types=1);

use App\Http\Requests\DeleteTagRequest;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Routing\Route;

it('denies deletes when the route tag is missing', function (): void {
    $user = User::factory()->create();

    $request = deleteTagRequestWithRoute(null);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeFalse();
});

function deleteTagRequestWithRoute(?Tag $tag): DeleteTagRequest
{
    $request = DeleteTagRequest::create('/tags/'.$tag?->id, 'DELETE');
    $request->setContainer(app());

    $route = new Route('DELETE', 'tags/{tag}', []);
    $route->bind($request);

    if ($tag instanceof Tag) {
        $route->setParameter('tag', $tag);
    }

    $request->setRouteResolver(fn (): Route => $route);

    return $request;
}
