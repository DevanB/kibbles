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
        $userId = config('services.kibbles_api.user_id');
        $user = User::query()->findOrFail(is_string($userId) ? $userId : '');
        $tag = $user->tags()->named($request->string('tag', 'read')->value())->first();

        if (! $tag instanceof Tag) {
            return response()->json(['error' => 'tag_not_found'], 404);
        }

        $bookmark = $user->xBookmarks()
            ->tagged($tag)
            ->with('tags')
            ->inRandomOrder()
            ->first();

        return response()->json([
            'data' => $bookmark?->toWire(),
        ]);
    }
}
