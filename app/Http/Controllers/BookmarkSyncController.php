<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateBookmarkSync;
use App\Http\Requests\StoreBookmarkSyncRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class BookmarkSyncController
{
    public function store(
        StoreBookmarkSyncRequest $request,
        #[CurrentUser] User $user,
        CreateBookmarkSync $action,
    ): RedirectResponse {
        $action->handle($user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Bookmark sync started.'),
        ]);

        return back();
    }
}
