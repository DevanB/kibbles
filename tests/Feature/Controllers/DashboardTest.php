<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests to login', function (): void {
    $response = $this->get(route('dashboard'));

    $response->assertRedirectToRoute('login');
});

it('renders the dashboard for a verified user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard'));
});

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->get(route('dashboard'));

    $response->assertRedirectToRoute('verification.notice');
});
