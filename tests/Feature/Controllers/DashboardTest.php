<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests to login', function (): void {
    $response = $this->get(route('dashboard'));

    $response->assertRedirectToRoute('login');
});

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)
        ->get(route('dashboard'));

    $response->assertRedirectToRoute('verification.notice');
});
