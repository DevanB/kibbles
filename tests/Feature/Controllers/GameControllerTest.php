<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Str;

it('redirects guests to login', function (string $method, string $route, array $parameters = []): void {
    $response = $this->{$method}(route($route, $parameters));

    $response->assertRedirectToRoute('login');
})->with([
    'index' => ['get', 'games.index'],
    'create' => ['get', 'games.create'],
    'store' => ['post', 'games.store'],
    'show' => ['get', 'games.show', ['game' => '00000000-0000-0000-0000-000000000001']],
    'edit' => ['get', 'games.edit', ['game' => '00000000-0000-0000-0000-000000000001']],
    'update' => ['put', 'games.update', ['game' => '00000000-0000-0000-0000-000000000001']],
    'destroy' => ['delete', 'games.destroy', ['game' => '00000000-0000-0000-0000-000000000001']],
]);

it('returns 404 for a missing game', function (string $route): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $missingId = Str::uuid()->toString();

    $this->actingAs($user)
        ->get(route($route, $missingId))
        ->assertNotFound();
})->with([
    'show' => ['games.show'],
    'edit' => ['games.edit'],
]);

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->get(route('games.index'));

    $response->assertRedirectToRoute('verification.notice');
});

it('lists only the authenticated user games as id, title, and status', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $owned = Game::factory()->recycle($user)->create(['title' => 'Owned Game']);
    Game::factory()->create(['title' => 'Someone Else Game']);

    $response = $this->actingAs($user)
        ->get(route('games.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/index')
            ->has('games', 1)
            ->has('games.0', fn ($game) => $game
                ->where('id', $owned->id)
                ->where('title', 'Owned Game')
                ->where('status', GameStatus::Backlog->value)
                ->where('statusLabel', 'Backlog')
                ->where('rawgId', null)
                ->where('imageUrl', null)
                ->where('description', null)));
});

it('requires a title when creating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), []);

    $response->assertRedirectToRoute('games.create')
        ->assertSessionHasErrors(['title' => 'A title is required.']);
});

it('requires a string title when creating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => ['Catan'],
        ]);

    $response->assertRedirectToRoute('games.create')
        ->assertSessionHasErrors(['title' => 'The title must be a string.']);
});

it('rejects titles longer than 255 characters when creating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => str_repeat('a', 256),
        ]);

    $response->assertRedirectToRoute('games.create')
        ->assertSessionHasErrors(['title' => 'The title may not be greater than 255 characters.']);
});

it('rejects a duplicate title for the same user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => 'Catan',
        ]);

    $response->assertRedirectToRoute('games.create')
        ->assertSessionHasErrors(['title' => 'You already have a game with this title.']);
});

it('rejects a case-insensitive duplicate title for the same user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => 'catan',
        ]);

    $response->assertRedirectToRoute('games.create')
        ->assertSessionHasErrors(['title' => 'You already have a game with this title.']);

    expect(Game::query()->whereBelongsTo($user)->count())->toBe(1)
        ->and(Game::query()->whereBelongsTo($user)->value('title'))->toBe('Catan');
});

it('allows the same title for different users', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $other = User::factory()->withoutTwoFactor()->create();
    Game::factory()->recycle($other)->create(['title' => 'Catan']);

    $response = $this->actingAs($owner)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => 'Catan',
        ]);

    $game = Game::query()->whereBelongsTo($owner)->where('title', 'Catan')->first();

    expect($game)->not->toBeNull();

    $response->assertRedirectToRoute('games.show', $game);
});

it('allows the same title in a different case for a different user', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $other = User::factory()->withoutTwoFactor()->create();
    Game::factory()->recycle($other)->create(['title' => 'Catan']);

    $response = $this->actingAs($owner)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => 'catan',
        ]);

    $game = Game::query()->whereBelongsTo($owner)->where('title', 'catan')->first();

    expect($game)->not->toBeNull();

    $response->assertRedirectToRoute('games.show', $game);
});

it('maps a unique constraint race to a validation error when creating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $dispatcher = Game::getEventDispatcher();
    Game::setEventDispatcher(clone $dispatcher);

    Game::creating(function () use ($user): void {
        static $seeded = false;

        if ($seeded) {
            return;
        }

        $seeded = true;

        Game::withoutEvents(function () use ($user): void {
            Game::factory()->recycle($user)->create([
                'id' => (string) Str::uuid(),
                'title' => 'Catan',
            ]);
        });
    });

    try {
        $response = $this->actingAs($user)
            ->fromRoute('games.create')
            ->post(route('games.store'), [
                'title' => 'Catan',
            ]);

        $response->assertRedirectToRoute('games.create')
            ->assertSessionHasErrors(['title' => 'You already have a game with this title.']);
    } finally {
        Game::setEventDispatcher($dispatcher);
    }
});

it('rejects a client-sent status when creating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => 'Catan',
            'status' => GameStatus::Finished->value,
        ]);

    $response->assertRedirectToRoute('games.create')
        ->assertSessionHasErrors(['status' => 'The status field is prohibited.']);

    expect(Game::query()->whereBelongsTo($user)->count())->toBe(0);
});

it('allows keeping the same title when updating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => 'Catan',
            'status' => $game->status->value,
        ]);

    $response->assertRedirectToRoute('games.show', $game);

    expect($game->refresh()->title)->toBe('Catan')
        ->and($game->status)->toBe(GameStatus::Backlog);
});

it('requires a title when updating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), []);

    $response->assertRedirectToRoute('games.edit', $game)
        ->assertSessionHasErrors(['title' => 'A title is required.']);
});

it('requires a status when updating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => 'Catan',
        ]);

    $response->assertRedirectToRoute('games.edit', $game)
        ->assertSessionHasErrors(['status' => 'A status is required.']);

    expect($game->refresh()->status)->toBe(GameStatus::Backlog);
});

it('rejects an invalid status when updating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => 'Catan',
            'status' => 'playing',
        ]);

    $response->assertRedirectToRoute('games.edit', $game)
        ->assertSessionHasErrors(['status' => 'The status must be Backlog, In Progress, Abandoned, or Finished.']);

    expect($game->refresh()->status)->toBe(GameStatus::Backlog);
});

it('requires a string title when updating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => ['Catan'],
            'status' => GameStatus::Backlog->value,
        ]);

    $response->assertRedirectToRoute('games.edit', $game)
        ->assertSessionHasErrors(['title' => 'The title must be a string.']);
});

it('rejects titles longer than 255 characters when updating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => str_repeat('a', 256),
            'status' => GameStatus::Backlog->value,
        ]);

    $response->assertRedirectToRoute('games.edit', $game)
        ->assertSessionHasErrors(['title' => 'The title may not be greater than 255 characters.']);
});

it('rejects updating to a duplicate title owned by the same user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    Game::factory()->recycle($user)->create(['title' => 'Catan']);
    $game = Game::factory()->recycle($user)->create(['title' => 'Ticket to Ride']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => 'Catan',
            'status' => GameStatus::Backlog->value,
        ]);

    $response->assertRedirectToRoute('games.edit', $game)
        ->assertSessionHasErrors(['title' => 'You already have a game with this title.']);
});

it('rejects updating to a case-insensitive duplicate title owned by the same user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    Game::factory()->recycle($user)->create(['title' => 'Catan']);
    $game = Game::factory()->recycle($user)->create(['title' => 'Ticket to Ride']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => 'catan',
            'status' => GameStatus::Backlog->value,
        ]);

    $response->assertRedirectToRoute('games.edit', $game)
        ->assertSessionHasErrors(['title' => 'You already have a game with this title.']);

    expect($game->refresh()->title)->toBe('Ticket to Ride');
});

it('allows changing the casing of a game title', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => 'CATAN',
            'status' => GameStatus::Backlog->value,
        ]);

    $response->assertRedirectToRoute('games.show', $game);

    expect($game->refresh()->title)->toBe('CATAN');
});

it('maps a unique constraint race to a validation error when updating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Ticket to Ride']);
    $dispatcher = Game::getEventDispatcher();
    Game::setEventDispatcher(clone $dispatcher);

    Game::updating(function () use ($user): void {
        static $seeded = false;

        if ($seeded) {
            return;
        }

        $seeded = true;

        Game::withoutEvents(function () use ($user): void {
            Game::factory()->recycle($user)->create([
                'id' => (string) Str::uuid(),
                'title' => 'Catan',
            ]);
        });
    });

    try {
        $response = $this->actingAs($user)
            ->fromRoute('games.edit', $game)
            ->put(route('games.update', $game), [
                'title' => 'Catan',
                'status' => GameStatus::Backlog->value,
            ]);

        $response->assertRedirectToRoute('games.edit', $game)
            ->assertSessionHasErrors(['title' => 'You already have a game with this title.']);
    } finally {
        Game::setEventDispatcher($dispatcher);
    }
});

it('forbids another user from viewing the show page', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create();

    $response = $this->actingAs($intruder)
        ->get(route('games.show', $game));

    $response->assertForbidden();
});

it('forbids another user from viewing the edit page', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create();

    $response = $this->actingAs($intruder)
        ->get(route('games.edit', $game));

    $response->assertForbidden();
});

it('forbids another user from updating a game', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create(['title' => 'Catan']);

    $response = $this->actingAs($intruder)
        ->fromRoute('games.index')
        ->put(route('games.update', $game), [
            'title' => 'Stolen Title',
            'status' => GameStatus::Finished->value,
        ]);

    $response->assertForbidden();

    expect($game->refresh()->title)->toBe('Catan');
});

it('forbids another user from deleting a game', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create();

    $response = $this->actingAs($intruder)
        ->delete(route('games.destroy', $game));

    $response->assertForbidden();

    $this->assertModelExists($game);
});

it('rejects a non-integer rawg id when creating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => 'Hades',
            'rawg_id' => 'not-an-id',
        ]);

    $response->assertRedirectToRoute('games.create')
        ->assertSessionHasErrors(['rawg_id' => 'The RAWG id must be an integer.']);

    expect(Game::query()->whereBelongsTo($user)->count())->toBe(0);
});

it('rejects a non-integer rawg id when updating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Hades']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => 'Hades',
            'status' => GameStatus::Backlog->value,
            'rawg_id' => 'not-an-id',
        ]);

    $response->assertRedirectToRoute('games.edit', $game)
        ->assertSessionHasErrors(['rawg_id' => 'The RAWG id must be an integer.']);

    expect($game->refresh()->rawg_id)->toBeNull();
});

it('saves a linked game with catalog details fetched on the server', function (): void {
    fakeHadesCatalog();

    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => 'Hades',
            'rawg_id' => HADES_RAWG_ID,
        ]);

    $game = Game::query()->whereBelongsTo($user)->first();

    expect($game)->not->toBeNull()
        ->and($game->rawg_id)->toBe(HADES_RAWG_ID)
        ->and($game->image_url)->toBe(HADES_IMAGE_URL)
        ->and($game->description)->toBe(HADES_DESCRIPTION);

    $response->assertRedirectToRoute('games.show', $game);
});

it('still saves the game when the catalog lookup fails', function (): void {
    config(['services.rawg.key' => 'testing']);

    Illuminate\Support\Facades\Http::fake([
        'https://api.rawg.io/api/games/*' => Illuminate\Support\Facades\Http::failedConnection(),
    ]);

    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => 'Hades',
            'rawg_id' => HADES_RAWG_ID,
        ]);

    $game = Game::query()->whereBelongsTo($user)->first();

    expect($game)->not->toBeNull()
        ->and($game->title)->toBe('Hades')
        ->and($game->rawg_id)->toBe(HADES_RAWG_ID)
        ->and($game->image_url)->toBeNull()
        ->and($game->description)->toBeNull();

    $response->assertRedirectToRoute('games.show', $game);
});

it('links an existing game and pulls catalog details', function (): void {
    fakeHadesCatalog();

    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Hades']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => 'Hades',
            'status' => GameStatus::Backlog->value,
            'rawg_id' => HADES_RAWG_ID,
        ]);

    $response->assertRedirectToRoute('games.show', $game);

    expect($game->refresh()->rawg_id)->toBe(HADES_RAWG_ID)
        ->and($game->image_url)->toBe(HADES_IMAGE_URL)
        ->and($game->description)->toBe(HADES_DESCRIPTION);
});

it('clears catalog details when a game is unlinked', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->catalogLinked()->create(['title' => 'Hades']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => 'Hades',
            'status' => GameStatus::Backlog->value,
        ]);

    $response->assertRedirectToRoute('games.show', $game);

    expect($game->refresh()->rawg_id)->toBeNull()
        ->and($game->image_url)->toBeNull()
        ->and($game->description)->toBeNull();
});
