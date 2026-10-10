<?php

declare(strict_types=1);

use App\Actions\DispatchXBookmarkSyncs;
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => (new DispatchXBookmarkSyncs)->handle())
    ->everySixHours()
    ->name('x-bookmarks-incremental')
    ->withoutOverlapping();

Schedule::call(fn () => (new DispatchXBookmarkSyncs)->handle(true))
    ->weekly()
    ->name('x-bookmarks-full')
    ->withoutOverlapping();
