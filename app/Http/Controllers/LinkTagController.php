<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AttachLinkTag;
use App\Actions\DetachLinkTag;
use App\Http\Requests\DeleteLinkTagRequest;
use App\Http\Requests\StoreLinkTagRequest;
use App\Models\Link;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;

final readonly class LinkTagController
{
    public function store(
        StoreLinkTagRequest $request,
        Link $link,
        AttachLinkTag $action,
    ): RedirectResponse {
        $action->handle($link, $request->tag());

        return back();
    }

    public function destroy(
        DeleteLinkTagRequest $request,
        Link $link,
        Tag $tag,
        DetachLinkTag $action,
    ): RedirectResponse {
        $action->handle($link, $tag);

        return back();
    }
}
