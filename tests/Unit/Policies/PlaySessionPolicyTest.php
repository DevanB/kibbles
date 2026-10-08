<?php

declare(strict_types=1);

use App\Policies\PlaySessionPolicy;

it('allows authenticated users to view any play sessions', function (): void {
    $policy = new PlaySessionPolicy;

    expect($policy->viewAny())->toBeTrue();
});
