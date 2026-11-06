<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SaveLink;
use App\Http\Requests\StoreLinkRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class StoreLinkController
{
    public function __invoke(
        StoreLinkRequest $request,
        #[CurrentUser] User $user,
        SaveLink $action,
    ): JsonResponse {
        $link = $action->handle($user, $request->linkUrl());

        if ($link->wasRecentlyCreated) {
            return response()->json(['message' => __('Saved to Kibbles.')], 201);
        }

        return response()->json(['message' => __('Already in Kibbles.')]);
    }
}
