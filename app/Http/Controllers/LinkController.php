<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteLink;
use App\Actions\SaveLink;
use App\Http\Requests\DeleteLinkRequest;
use App\Http\Requests\StoreLinkRequest;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ScrollMetadata;
use InertiaUI\Modal\Modal;

final readonly class LinkController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Link::class);

        $raw = $request->query('tag');
        $tagFilter = is_string($raw) ? $raw : null;
        $activeTag = null;

        $query = $user->links()
            ->newestFirst()
            ->with('tags');

        if ($tagFilter === 'untagged') {
            $query->untagged();
        } elseif (is_string($tagFilter) && $tagFilter !== '') {
            $activeTag = $user->tags()->whereKey($tagFilter)->first();

            abort_unless($activeTag instanceof Tag, 404);

            $query->tagged($activeTag);
        }

        /** @var CursorPaginator<int, Link> $paginator */
        $paginator = $query->cursorPaginate(20);

        return Inertia::render('bookmarks/index', [
            'links' => Inertia::scroll(
                [
                    ...$paginator->toArray(),
                    'data' => $paginator
                        ->getCollection()
                        ->map(fn (Link $link): array => $link->toWire())
                        ->values()
                        ->all(),
                ],
                metadata: ScrollMetadata::fromPaginator($paginator),
            ),
            'activeTag' => $tagFilter === 'untagged'
                ? ['id' => 'untagged', 'name' => 'Untagged']
                : $activeTag?->toWire(),
        ]);
    }

    public function create(): Modal
    {
        Gate::authorize('create', Link::class);

        return Inertia::modal('bookmarks/create')
            ->baseRoute('bookmarks.index');
    }

    public function store(StoreLinkRequest $request, #[CurrentUser] User $user, SaveLink $action): RedirectResponse
    {
        $action->handle($user, $request->linkUrl());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Saved to Kibbles.'),
        ]);

        return to_route('bookmarks.index');
    }

    public function show(Link $link): Modal
    {
        Gate::authorize('view', $link);

        $link->load('tags');

        return Inertia::modal('bookmarks/show', [
            'link' => $link->toWire(),
        ])->baseRoute('bookmarks.index');
    }

    public function destroy(DeleteLinkRequest $request, Link $link, DeleteLink $action): RedirectResponse
    {
        $action->handle($link);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Removed.'),
        ]);

        return back(fallback: route('bookmarks.index'));
    }
}
