<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

it('redirects guests to login from games routes', function (string $method, string $uri): void {
    $response = $this->{$method}($uri);

    $response->assertRedirectToRoute('login');
})->with([
    'index' => ['get', '/games'],
    'create' => ['get', '/games/create'],
    'store' => ['post', '/games'],
    'show' => ['get', '/games/00000000-0000-0000-0000-000000000001'],
    'edit' => ['get', '/games/00000000-0000-0000-0000-000000000001/edit'],
    'update' => ['patch', '/games/00000000-0000-0000-0000-000000000001'],
    'destroy' => ['delete', '/games/00000000-0000-0000-0000-000000000001'],
]);

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->get(route('games.index'));

    $response->assertRedirectToRoute('verification.notice');
});

it('renders the games index for a verified user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->for($user)->create(['title' => 'Chess']);

    $response = $this->actingAs($user)
        ->get(route('games.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/index')
            ->has('games', 1)
            ->where('games.0.id', $game->id)
            ->where('games.0.title', 'Chess'));
});

it('does not list another users games', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $otherUser = User::factory()->withoutTwoFactor()->create();
    Game::factory()->for($otherUser)->create(['title' => 'Secret']);

    $response = $this->actingAs($user)
        ->get(route('games.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/index')
            ->has('games', 0));
});

it('renders the create page for a verified user', function (): void {
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

    $game = Game::query()->where('title', 'Catan')->first();

    expect($game)->not->toBeNull()
        ->and($game->user_id)->toBe($user->id);
});

it('requires a title when creating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), [
            'title' => '',
        ]);

    $response->assertRedirectToRoute('games.create')
        ->assertSessionHasErrors('title');

    expect(Game::query()->count())->toBe(0);
});

it('requires a title when the field is missing', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.create')
        ->post(route('games.store'), []);

    $response->assertRedirectToRoute('games.create')
        ->assertSessionHasErrors('title');
});

it('redirects show to edit for an owned game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->get(route('games.show', $game));

    $response->assertRedirectToRoute('games.edit', $game);
});

it('renders the edit page for an owned game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->for($user)->create(['title' => 'Chess']);

    $response = $this->actingAs($user)
        ->get(route('games.edit', $game));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('games/edit')
            ->where('game.id', $game->id)
            ->where('game.title', 'Chess'));
});

it('may update an owned game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->for($user)->create(['title' => 'Old Title']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->patch(route('games.update', $game), [
            'title' => 'New Title',
        ]);

    $response->assertRedirectToRoute('games.edit', $game)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Game updated.')]);

    expect($game->refresh()->title)->toBe('New Title');
});

it('requires a title when updating a game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->for($user)->create(['title' => 'Chess']);

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->patch(route('games.update', $game), [
            'title' => '',
        ]);

    $response->assertRedirectToRoute('games.edit', $game)
        ->assertSessionHasErrors('title');

    expect($game->refresh()->title)->toBe('Chess');
});

it('may delete an owned game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.edit', $game)
        ->delete(route('games.destroy', $game));

    $response->assertRedirectToRoute('games.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => __('Game deleted.')]);

    expect($game->fresh())->toBeNull();
});

it('forbids showing another users game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('games.show', $game));

    $response->assertForbidden();
});

it('forbids viewing another users game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('games.edit', $game));

    $response->assertForbidden();
});

it('forbids updating another users game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->create(['title' => 'Secret']);

    $response = $this->actingAs($user)
        ->fromRoute('games.index')
        ->patch(route('games.update', $game), [
            'title' => 'Hijacked',
        ]);

    $response->assertForbidden();

    expect($game->refresh()->title)->toBe('Secret');
});

it('forbids deleting another users game', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $game = Game::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('games.index')
        ->delete(route('games.destroy', $game));

    $response->assertForbidden();

    expect($game->fresh())->not->toBeNull();
});
