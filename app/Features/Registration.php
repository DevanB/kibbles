<?php

declare(strict_types=1);

namespace App\Features;

use Laravel\Pennant\Feature;

final class Registration
{
    public static function enabled(): bool
    {
        return Feature::globally()->active(self::class);
    }

    public function resolve(): bool
    {
        return (bool) config('features.registration');
    }
}
