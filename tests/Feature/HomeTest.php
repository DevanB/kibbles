<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests to the dashboard then login', function (): void {
    $this->get(route('home'))
        ->assertRedirectToRoute('dashboard');

    $this->followingRedirects()
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('session/create'));
});

it('redirects verified users to the dashboard', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirectToRoute('dashboard');

    $this->actingAs($user)
        ->followingRedirects()
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard'));
});
