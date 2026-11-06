<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LinkPreviewStatus;
use App\Http\Requests\RetryLinkPreviewRequest;
use App\Jobs\FetchLinkPreview;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class LinkPreviewController
{
    public function store(RetryLinkPreviewRequest $request, Link $link): RedirectResponse
    {
        $link->update([
            'preview_status' => LinkPreviewStatus::Pending,
        ]);

        dispatch(new FetchLinkPreview($link->id));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Retrying preview.'),
        ]);

        return back(fallback: route('bookmarks.index'));
    }
}
