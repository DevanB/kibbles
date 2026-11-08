<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildXBookmarkReviewQueue;
use App\Actions\ReviewXBookmark;
use App\Http\Requests\StoreBookmarkReviewRequest;
use App\Models\User;
use App\Models\XBookmark;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class XBookmarkReviewController
{
    public function index(#[CurrentUser] User $user, BuildXBookmarkReviewQueue $build): Response
    {
        Gate::authorize('viewAny', XBookmark::class);

        return Inertia::render('x-bookmark-reviews/index', $build->handle($user)->toWire());
    }

    public function store(StoreBookmarkReviewRequest $request, ReviewXBookmark $action): RedirectResponse
    {
        $action->handle($request->bookmark());

        return to_route('x-bookmark-reviews.index');
    }
}
