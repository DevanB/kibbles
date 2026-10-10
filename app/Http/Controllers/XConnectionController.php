<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateBookmarkSync;
use App\Actions\CreateXConnection;
use App\Actions\DeleteXConnection;
use App\Http\Requests\DeleteXConnectionRequest;
use App\Http\Requests\StoreXConnectionRequest;
use App\Models\User;
use App\Models\XConnection;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as XOAuthUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

final readonly class XConnectionController
{
    /**
     * @var list<string>
     */
    private const array SCOPES = [
        'tweet.read',
        'users.read',
        'bookmark.read',
        'bookmark.write',
        'offline.access',
    ];

    public function create(): SymfonyRedirectResponse
    {
        Gate::authorize('create', XConnection::class);

        return $this->xDriver()->redirect();
    }

    public function store(
        StoreXConnectionRequest $request,
        #[CurrentUser] User $user,
        CreateXConnection $create,
        CreateBookmarkSync $sync,
    ): RedirectResponse {
        try {
            $xUser = $this->xDriver()->user();
        } catch (Throwable) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Could not connect X. Please try again.'),
            ]);

            return to_route('dashboard');
        }

        if (! $xUser instanceof XOAuthUser || $xUser->refreshToken === '' || $xUser->token === '') {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Could not connect X. Please try again.'),
            ]);

            return to_route('dashboard');
        }

        $create->handle(
            $user,
            (string) $xUser->getId(),
            (string) $xUser->getNickname(),
            $xUser->token,
            $xUser->refreshToken,
            Date::now()->addSeconds($xUser->expiresIn > 0 ? $xUser->expiresIn : 7200),
        );

        $sync->handle($user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('X connected. Bookmarks are syncing.'),
        ]);

        return to_route('dashboard');
    }

    public function destroy(
        DeleteXConnectionRequest $request,
        #[CurrentUser] User $user,
        DeleteXConnection $action,
    ): RedirectResponse {
        $connection = $user->xConnection;

        if ($connection instanceof XConnection) {
            $action->handle($connection);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('X disconnected.'),
        ]);

        return to_route('dashboard');
    }

    private function xDriver(): Provider
    {
        $callback = url('/x-connection/callback');
        config(['services.x.redirect' => $callback]);

        $driver = Socialite::driver('x');

        if ($driver instanceof AbstractProvider) {
            $driver->setScopes(self::SCOPES);
            $driver->redirectUrl($callback);
        }

        return $driver;
    }
}
