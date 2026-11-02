<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class RandomBookmarkController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $owner = User::query()->sole();
        } catch (ModelNotFoundException|MultipleRecordsFoundException) {
            return response()->json(['error' => 'owner_unresolved'], 500);
        }

        $tag = $owner->tags()->named($request->string('tag', 'read')->value())->first();

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
