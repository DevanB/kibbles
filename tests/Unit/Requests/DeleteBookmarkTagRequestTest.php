<?php

declare(strict_types=1);

use App\Http\Requests\DeleteBookmarkTagRequest;
use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;
use Illuminate\Routing\Route;

it('denies detach when the route bookmark or tag is missing', function (): void {
    $user = User::factory()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create();

    $missingBookmark = deleteBookmarkTagRequestWithRoute(null, $tag);
    $missingBookmark->setUserResolver(fn () => $user);

    $missingTag = deleteBookmarkTagRequestWithRoute($bookmark, null);
    $missingTag->setUserResolver(fn () => $user);

    expect($missingBookmark->authorize())->toBeFalse()
        ->and($missingTag->authorize())->toBeFalse();
});

it('denies detach when the user is missing', function (): void {
    $user = User::factory()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create();

    $request = deleteBookmarkTagRequestWithRoute($bookmark, $tag);
    $request->setUserResolver(fn (): null => null);

    expect($request->authorize())->toBeFalse();
});

function deleteBookmarkTagRequestWithRoute(?XBookmark $bookmark, ?Tag $tag): DeleteBookmarkTagRequest
{
    $request = DeleteBookmarkTagRequest::create(
        '/bookmarks/'.$bookmark?->id.'/tags/'.$tag?->id,
        'DELETE',
    );
    $request->setContainer(app());

    $route = new Route('DELETE', 'bookmarks/{bookmark}/tags/{tag}', []);
    $route->bind($request);

    if ($bookmark instanceof XBookmark) {
        $route->setParameter('bookmark', $bookmark);
    }

    if ($tag instanceof Tag) {
        $route->setParameter('tag', $tag);
    }

    $request->setRouteResolver(fn (): Route => $route);

    return $request;
}
