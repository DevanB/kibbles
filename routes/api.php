<?php

declare(strict_types=1);

use App\Http\Controllers\RandomBookmarkController;
use App\Http\Middleware\AuthenticateKibblesApi;
use Illuminate\Support\Facades\Route;

Route::middleware([AuthenticateKibblesApi::class, 'throttle:60,1'])
    ->get('/bookmarks/random', RandomBookmarkController::class)
    ->name('bookmarks.random');
