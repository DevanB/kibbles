<?php

declare(strict_types=1);

use App\Features\Registration;
use Laravel\Pennant\Feature;

it('resolves inactive when the registration config flag is off', function (): void {
    expect(config('features.registration'))->toBeFalse()
        ->and(Registration::enabled())->toBeFalse();
});

it('resolves active when the registration config flag is on', function (): void {
    config(['features.registration' => true]);
    Feature::flushCache();

    expect(Registration::enabled())->toBeTrue();
});
