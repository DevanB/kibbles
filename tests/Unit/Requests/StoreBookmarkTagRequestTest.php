<?php

declare(strict_types=1);

use App\Http\Requests\StoreBookmarkTagRequest;
use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;
use Illuminate\Routing\Route;

it('denies attach when the route bookmark is missing', function (): void {
    $user = User::factory()->create();

    $request = storeBookmarkTagRequestWithRoute(null, ['name' => 'Laravel']);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeFalse();
});

it('denies attach when the user is missing', function (): void {
    $bookmark = XBookmark::factory()->create();

    $request = storeBookmarkTagRequestWithRoute($bookmark, ['name' => 'Laravel']);
    $request->setUserResolver(fn (): null => null);

    expect($request->authorize())->toBeFalse();
});

it('denies attach when the tag id does not exist', function (): void {
    $user = User::factory()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $request = storeBookmarkTagRequestWithRoute($bookmark, [
        'tag_id' => '00000000-0000-0000-0000-000000000099',
    ]);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeFalse();
});

it('returns the typed name when no tag id is present', function (): void {
    $user = User::factory()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();

    $request = storeBookmarkTagRequestWithRoute($bookmark, ['name' => 'Pest']);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue()
        ->and($request->tag())->toBe('Pest');
});

it('returns the tag model when the tag id is present', function (): void {
    $user = User::factory()->create();
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    $request = storeBookmarkTagRequestWithRoute($bookmark, ['tag_id' => $tag->id]);
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue()
        ->and($request->tag()->is($tag))->toBeTrue();
});

it('throws when tag() cannot resolve a tag id', function (): void {
    $bookmark = XBookmark::factory()->create();

    $request = storeBookmarkTagRequestWithRoute($bookmark, [
        'tag_id' => '00000000-0000-0000-0000-000000000099',
    ]);

    expect(fn (): Tag|string => $request->tag())->toThrow(LogicException::class);
});

/**
 * @param  array<string, string>  $input
 */
function storeBookmarkTagRequestWithRoute(?XBookmark $bookmark, array $input): StoreBookmarkTagRequest
{
    $request = StoreBookmarkTagRequest::create('/bookmarks/'.$bookmark?->id.'/tags', 'POST', $input);
    $request->setContainer(app());

    $route = new Route('POST', 'bookmarks/{bookmark}/tags', []);
    $route->bind($request);

    if ($bookmark instanceof XBookmark) {
        $route->setParameter('bookmark', $bookmark);
    }

    $request->setRouteResolver(fn (): Route => $route);

    return $request;
}
