<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class RandomBookmarkController
{
    public function __invoke(Request $request): JsonResponse
    {
        $owners = User::query()->limit(2)->get();
        $owner = $owners->count() === 1 ? $owners->first() : null;

        abort_unless($owner instanceof User, 500, 'owner_not_found');

        $tagName = $request->filled('tag') ? $request->string('tag')->value() : 'read';
        $tag = $owner->tags()->named($tagName)->first();

        if (! $tag instanceof Tag) {
            return response()->json(['error' => 'tag_not_found'], 404);
        }

        $bookmark = $owner->xBookmarks()
            ->tagged($tag)
            ->with('tags')
            ->inRandomOrder()
            ->first();

        return response()->json([
            'data' => $bookmark?->toWire(),
        ]);
    }
}
