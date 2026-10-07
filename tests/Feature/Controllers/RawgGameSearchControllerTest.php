<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests to login', function (): void {
    $this->get(route('rawg.games.search', ['query' => 'hades']))
        ->assertRedirectToRoute('login');
});

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('rawg.games.search', ['query' => 'hades']))
        ->assertRedirectToRoute('verification.notice');
});

it('returns no results when no catalog key is configured', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->getJson(route('rawg.games.search', ['query' => 'hades']))
        ->assertOk()
        ->assertExactJson(['results' => []]);
});

it('returns faked catalog results when a key is configured', function (): void {
    fakeHadesCatalog();

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->getJson(route('rawg.games.search', ['query' => 'hades']))
        ->assertOk()
        ->assertExactJson([
            'results' => [
                [
                    'id' => HADES_RAWG_ID,
                    'name' => 'Hades',
                    'releasedYear' => 2020,
                    'backgroundImage' => HADES_IMAGE_URL,
                ],
            ],
        ]);
});

it('requires a search query', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->getJson(route('rawg.games.search'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['query' => 'A search query is required.']);
});
