<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->strict()->ignoring('App\Models');
arch()->preset()->laravel();
arch()->preset()->security();

// Models may use protected casts(); the strict preset cannot ignore only that expectation.
arch()->expect('App\Models')->classes()->not->toBeAbstract();
arch()->expect('App\Models')->toUseStrictTypes();
arch()->expect('App\Models')->toUseStrictEquality();
arch()->expect('App\Models')->classes()->toBeFinal();

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();
