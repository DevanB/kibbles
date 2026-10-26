<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ReviewXBookmark;
use App\Http\Requests\StoreBookmarkReviewRequest;
use App\Models\User;
use App\Models\XBookmark;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class BookmarkReviewController
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', XBookmark::class);

        $current = $user->xBookmarks()
            ->unreviewed()
            ->oldestFirst()
            ->with('tags')
            ->first();

        return Inertia::render('bookmark-reviews/index', [
            'bookmark' => $current?->toWire(),
            'remaining' => $user->xBookmarks()->unreviewed()->count(),
            'total' => $user->xBookmarks()->count(),
        ]);
    }

    public function store(StoreBookmarkReviewRequest $request, ReviewXBookmark $action): RedirectResponse
    {
        $action->handle($request->bookmark());

        return to_route('bookmark-reviews.index');
    }
}
