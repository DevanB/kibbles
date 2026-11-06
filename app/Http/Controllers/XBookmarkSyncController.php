<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateBookmarkSync;
use App\Http\Requests\StoreBookmarkSyncRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class XBookmarkSyncController
{
    public function store(
        StoreBookmarkSyncRequest $request,
        #[CurrentUser] User $user,
        CreateBookmarkSync $action,
    ): RedirectResponse {
        $full = $request->boolean('full');

        $action->handle($user, $full);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $full ? __('Full bookmark sync started.') : __('Bookmark sync started.'),
        ]);

        return back();
    }
}
