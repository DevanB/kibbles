<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'tags' => $user instanceof User
                ? $user->tags()
                    ->alphabetical()
                    ->get()
                    ->map(fn (Tag $tag): array => $tag->toWire())
                    ->values()
                    ->all()
                : [],
        ];
    }
}
