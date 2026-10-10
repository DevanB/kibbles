<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteXBookmark;
use App\Http\Requests\DeleteXBookmarkRequest;
use App\Models\User;
use App\Models\XBookmark;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

final readonly class BookmarkController
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', XBookmark::class);

        return Inertia::render('bookmarks/index', [
            'bookmarks' => $user->xBookmarks()
                ->newestFirst()
                ->get()
                ->map(fn (XBookmark $bookmark): array => $bookmark->toWire())
                ->values()
                ->all(),
            'xConnection' => $user->xConnection?->toWire(),
        ]);
    }

    public function show(XBookmark $bookmark): Modal
    {
        Gate::authorize('view', $bookmark);

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

            return to_route('bookmarks.index');
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Bookmark removed.'),
        ]);

        return to_route('bookmarks.index');
    }
}
