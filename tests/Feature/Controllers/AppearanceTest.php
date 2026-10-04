<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests to login', function (): void {
    $response = $this->get(route('appearance.edit'));

    $response->assertRedirectToRoute('login');
});

it('renders appearance settings for a verified user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->fromRoute('dashboard')
        ->get(route('appearance.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('appearance/update'));
});

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->get(route('appearance.edit'));

    $response->assertRedirectToRoute('verification.notice');
});
