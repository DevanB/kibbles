<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AttachBookmarkTag;
use App\Actions\DetachBookmarkTag;
use App\Http\Requests\DeleteBookmarkTagRequest;
use App\Http\Requests\StoreBookmarkTagRequest;
use App\Models\Tag;
use App\Models\XBookmark;
use Illuminate\Http\RedirectResponse;

final readonly class BookmarkTagController
{
    public function store(
        StoreBookmarkTagRequest $request,
        XBookmark $bookmark,
        AttachBookmarkTag $action,
    ): RedirectResponse {
        $action->handle($bookmark, $request->tag());

        return back();
    }

    public function destroy(
        DeleteBookmarkTagRequest $request,
        XBookmark $bookmark,
        Tag $tag,
        DetachBookmarkTag $action,
    ): RedirectResponse {
        $action->handle($bookmark, $tag);

        return back();
    }
}
