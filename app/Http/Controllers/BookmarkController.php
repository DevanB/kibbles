<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteXBookmark;
use App\Http\Requests\DeleteXBookmarkRequest;
use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ScrollMetadata;
use InertiaUI\Modal\Modal;

final readonly class BookmarkController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', XBookmark::class);

        $raw = $request->query('tag');
        $tagFilter = is_string($raw) ? $raw : null;
        $activeTag = null;

        $query = $user->xBookmarks()
            ->newestFirst()
            ->with('tags');

        if ($tagFilter === 'untagged') {
            $query->untagged();
        } elseif (is_string($tagFilter) && $tagFilter !== '') {
            $activeTag = $user->tags()->whereKey($tagFilter)->first();

            abort_unless($activeTag instanceof Tag, 404);

            $query->tagged($activeTag);
        }

        /** @var CursorPaginator<int, XBookmark> $paginator */
        $paginator = $query->cursorPaginate(20);

        return Inertia::render('bookmarks/index', [
            'bookmarks' => Inertia::scroll(
                [
                    ...$paginator->toArray(),
                    'data' => $paginator
                        ->getCollection()
                        ->map(fn (XBookmark $bookmark): array => $bookmark->toWire())
                        ->values()
                        ->all(),
                ],
                metadata: ScrollMetadata::fromPaginator($paginator),
            ),
            'xConnection' => $user->xConnection?->toWire(),
            'activeTag' => $tagFilter === 'untagged'
                ? ['id' => 'untagged', 'name' => 'Untagged']
                : $activeTag?->toWire(),
        ]);
    }

    public function show(XBookmark $bookmark): Modal
    {
        Gate::authorize('view', $bookmark);

        $bookmark->load('tags');

        return Inertia::modal('bookmarks/show', [
            'bookmark' => $bookmark->toWire(),
        ])->baseRoute('bookmarks.index');
    }

    public function destroy(
        DeleteXBookmarkRequest $request,
        XBookmark $bookmark,
        DeleteXBookmark $action,
    ): RedirectResponse {
        if (! $action->handle($bookmark)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Could not remove the bookmark from X.'),
            ]);

            return back(fallback: route('bookmarks.index'))->withErrors([
                'bookmark' => __('Could not remove the bookmark from X.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Bookmark removed.'),
        ]);

        return back(fallback: route('bookmarks.index'));
    }
}
