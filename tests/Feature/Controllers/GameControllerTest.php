<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

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

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->get(route('games.index'));

    $response->assertRedirectToRoute('verification.notice');
});

it('lists only the authenticated user games', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $owned = Game::factory()->recycle($user)->create(['title' => 'Owned Game']);
    Game::factory()->create(['title' => 'Someone Else Game']);

    $response = $this->actingAs($user)
        ->get(route('games.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/index')
            ->has('games', 1)
            ->where('games.0.id', $owned->id)
            ->where('games.0.title', 'Owned Game'));
});

it('renders the create page', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->get(route('games.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('games/create'));
});

it('may create a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => 'Catan',
        ]);

    $response->assertRedirectToRoute('games.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Game created.')]);

    $game = Game::query()->whereBelongsTo($user)->first();

    expect($game)->not->toBeNull()
        ->and($game->title)->toBe('Catan');
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

it('allows the same title for different users', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $other = User::factory()->withoutTwoFactor()->create();
    Game::factory()->recycle($other)->create(['title' => 'Catan']);

    $response = $this->actingAs($owner)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => 'Catan',
        ]);

    $response->assertRedirectToRoute('games.index');

    expect(Game::query()->whereBelongsTo($owner)->where('title', 'Catan')->exists())->toBeTrue();
});

it('redirects show to edit for the owner', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $response = $this->actingAs($user)
        ->get(route('games.show', $game));

    $response->assertRedirectToRoute('games.edit', $game);
});

it('renders the edit page for the owner', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $response = $this->actingAs($user)
        ->get(route('games.edit', $game));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/edit')
            ->where('game.id', $game->id)
            ->where('game.title', 'Catan'));
});

it('may update a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => 'Ticket to Ride',
        ]);

    $response->assertRedirectToRoute('games.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Game updated.')]);

    expect($game->refresh()->title)->toBe('Ticket to Ride');
});

it('allows keeping the same title when updating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create(['title' => 'Catan']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => 'Catan',
        ]);

    $response->assertRedirectToRoute('games.index')
        ->assertSessionDoesntHaveErrors();
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

it('requires a string title when updating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->put(route('games.update', $game), [
            'title' => ['Catan'],
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
        ]);

    $response->assertRedirectToRoute('games.edit', $game)
        ->assertSessionHasErrors(['title' => 'You already have a game with this title.']);
});

it('may delete a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($user)->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->delete(route('games.destroy', $game));

    $response->assertRedirectToRoute('games.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Game deleted.')]);

    $this->assertModelMissing($game);
});

it('forbids another user from viewing a game', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->recycle($owner)->create();

    $response = $this->actingAs($intruder)
        ->get(route('games.show', $game));

    $response->assertForbidden();
});

it('forbids another user from editing a game', function (): void {
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
