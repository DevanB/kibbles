<?php

declare(strict_types=1);

use App\Models\JournalEntry;
use App\Models\PlaySession;

arch()->preset()->php();
arch()->preset()->strict()->ignoring([
    JournalEntry::class,
    PlaySession::class,
]);
arch()->preset()->laravel();
arch()->preset()->security()->ignoring([
    'assert',
]);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

arch('eloquent scope models stay strict except Rector scopes')
    ->expect([
        JournalEntry::class,
        PlaySession::class,
    ])
    ->toBeFinal()
    ->toUseStrictTypes()
    ->toUseStrictEquality();

//
