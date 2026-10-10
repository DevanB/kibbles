<?php

declare(strict_types=1);

use App\Jobs\SyncXBookmarks;
use App\Models\User;
use App\Models\XBookmark;
use App\Models\XConnection;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as XOAuthUser;

it('registers the X callback at /x-connection/callback', function (): void {
    expect(route('x-connection.store', absolute: false))->toBe('/x-connection/callback');
});

it('redirects guests to login', function (string $method, string $route): void {
    $this->{$method}(route($route))->assertRedirectToRoute('login');
})->with([
    'create' => ['get', 'x-connection.create'],
    'store' => ['get', 'x-connection.store'],
    'destroy' => ['delete', 'x-connection.destroy'],
]);

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('x-connection.create'))
        ->assertRedirectToRoute('verification.notice');
});

it('redirects to X to connect', function (): void {
    configureX();
    Socialite::fake('x');

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('x-connection.create'))
        ->assertRedirect('https://socialite.fake/x/authorize');
});

it('sends X the registered callback and bookmark scopes', function (): void {
    configureX();

    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->get(route('x-connection.create'));

    $response->assertRedirect();

    $location = (string) $response->headers->get('Location');

    expect($location)->toContain('https://x.com/i/oauth2/authorize')
        ->and($location)->toContain(urlencode(url('/x-connection/callback')))
        ->and($location)->toContain('bookmark.read')
        ->and($location)->toContain('bookmark.write')
        ->and($location)->toContain('offline.access');
});

it('stores the X connection from the callback and dispatches a sync', function (): void {
    configureX();
    Queue::fake([SyncXBookmarks::class]);
    Socialite::fake('x', XOAuthUser::fake([
        'id' => '2244994945',
        'nickname' => 'devan',
        'token' => 'access-from-x',
        'refreshToken' => 'refresh-from-x',
        'expiresIn' => 7200,
    ]));

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('x-connection.store'))
        ->assertRedirectToRoute('dashboard');

    $connection = $user->xConnection;

    expect($connection)->not->toBeNull()
        ->and($connection->x_user_id)->toBe('2244994945')
        ->and($connection->username)->toBe('devan')
        ->and($connection->access_token)->toBe('access-from-x')
        ->and($connection->refresh_token)->toBe('refresh-from-x')
        ->and($connection->expires_at->toDateTimeString())->toBe(now()->addSeconds(7200)->toDateTimeString());

    Queue::assertPushed(SyncXBookmarks::class, fn (SyncXBookmarks $job): bool => $job->userId === $user->id && $job->full === false);
});

it('updates an existing X connection on reconnect', function (): void {
    configureX();
    Queue::fake([SyncXBookmarks::class]);
    Socialite::fake('x', XOAuthUser::fake([
        'id' => '99',
        'nickname' => 'newhandle',
        'token' => 'new-access',
        'refreshToken' => 'new-refresh',
        'expiresIn' => 3600,
    ]));

    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->create([
        'x_user_id' => '1',
        'username' => 'oldhandle',
    ]);

    $this->actingAs($user)
        ->get(route('x-connection.store'))
        ->assertRedirectToRoute('dashboard');

    expect($user->xConnection()->count())->toBe(1)
        ->and($user->xConnection?->username)->toBe('newhandle')
        ->and($user->xConnection?->access_token)->toBe('new-access');
});

it('defaults the token lifetime when X omits expiresIn', function (): void {
    configureX();
    Queue::fake([SyncXBookmarks::class]);
    Socialite::fake('x', XOAuthUser::fake([
        'id' => '7',
        'nickname' => 'devan',
        'token' => 'access',
        'refreshToken' => 'refresh',
        'expiresIn' => null,
    ]));

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('x-connection.store'))
        ->assertRedirectToRoute('dashboard');

    expect($user->xConnection?->expires_at->toDateTimeString())->toBe(now()->addSeconds(7200)->toDateTimeString());
});

it('keeps the user disconnected when X denies the grant', function (): void {
    configureX();
    Socialite::fake('x', fn () => throw new RuntimeException('denied'));

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('x-connection.store'))
        ->assertRedirectToRoute('dashboard');

    expect($user->xConnection)->toBeNull();
});

it('keeps the user disconnected when X omits tokens', function (): void {
    configureX();
    Socialite::fake('x', XOAuthUser::fake([
        'id' => '7',
        'nickname' => 'devan',
        'token' => '',
        'refreshToken' => '',
    ]));

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('x-connection.store'))
        ->assertRedirectToRoute('dashboard');

    expect($user->xConnection)->toBeNull();
});

it('keeps the user disconnected when Socialite returns a user without OAuth tokens', function (): void {
    configureX();
    Socialite::fake('x', new class implements SocialiteUser
    {
        public function getId(): string
        {
            return '7';
        }

        public function getNickname(): string
        {
            return 'devan';
        }

        public function getName(): string
        {
            return 'Devan';
        }

        public function getEmail(): ?string
        {
            return null;
        }

        public function getAvatar(): ?string
        {
            return null;
        }
    });

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('x-connection.store'))
        ->assertRedirectToRoute('dashboard');

    expect($user->xConnection)->toBeNull();
});

it('forbids disconnecting when X is not connected', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->delete(route('x-connection.destroy'))
        ->assertForbidden();
});

it('disconnects X and deletes local bookmarks', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->create();
    XBookmark::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->delete(route('x-connection.destroy'))
        ->assertRedirectToRoute('dashboard');

    expect($user->fresh()->xConnection)->toBeNull()
        ->and($user->xBookmarks()->count())->toBe(0);
});
