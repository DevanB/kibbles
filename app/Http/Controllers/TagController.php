<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteTag;
use App\Actions\UpdateTag;
use App\Http\Requests\DeleteTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

final readonly class TagController
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Tag::class);

        return Inertia::render('tags/index', [
            'tags' => $user->tags()
                ->alphabetical()
                ->get()
                ->map(fn (Tag $tag): array => $tag->toWire())
                ->values()
                ->all(),
        ]);
    }

    public function show(Tag $tag): Modal
    {
        Gate::authorize('view', $tag);

        return Inertia::modal('tags/show', [
            'tag' => $tag->toWire(),
        ])->baseRoute('tags.index');
    }

    public function edit(Tag $tag): Modal
    {
        Gate::authorize('update', $tag);

        return Inertia::modal('tags/edit', [
            'tag' => $tag->toWire(),
        ])->baseRoute('tags.index');
    }

    public function update(UpdateTagRequest $request, Tag $tag, UpdateTag $action): RedirectResponse
    {
        $action->handle($tag, $request->string('name')->value());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Tag renamed.'),
        ]);

        return to_route('tags.index');
    }

    public function destroy(DeleteTagRequest $request, Tag $tag, DeleteTag $action): RedirectResponse
    {
        $action->handle($tag);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Tag deleted.'),
        ]);

        return to_route('tags.index');
    }
}
