<?php

declare(strict_types=1);

use App\Features\Registration;
use Laravel\Pennant\Feature;

it('does not offer registration on the welcome page when the feature is inactive', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('welcome')
            ->where('canRegister', false));
});

it('offers registration on the welcome page when the feature is active', function (): void {
    Feature::define(Registration::class, true);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('welcome')
            ->where('canRegister', true));
});
