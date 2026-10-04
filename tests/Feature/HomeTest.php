<?php

declare(strict_types=1);

it('redirects the home route to the dashboard', function (): void {
    $this->get(route('home'))
        ->assertRedirectToRoute('dashboard');
});
