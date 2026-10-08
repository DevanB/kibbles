<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinishPlaySessionController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\PlaySessionController;
use App\Http\Controllers\RawgGameSearchController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StartPlaySessionController;
use App\Http\Controllers\StopPlaySessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasskeyController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('rawg/games', RawgGameSearchController::class)
        ->middleware('throttle:30,1')
        ->name('rawg.games.search');
    Route::resource('games', GameController::class);
    Route::resource('games.journal-entries', JournalEntryController::class)
        ->only(['create', 'store', 'show', 'edit', 'update', 'destroy'])
        ->scoped();
    Route::scopeBindings()->group(function (): void {
        Route::post('games/{game}/play-sessions/start', StartPlaySessionController::class)
            ->name('games.play-sessions.start');
        Route::get('games/{game}/play-sessions/create', [PlaySessionController::class, 'create'])
            ->name('games.play-sessions.create');
        Route::post('games/{game}/play-sessions', [PlaySessionController::class, 'store'])
            ->name('games.play-sessions.store');
        Route::get('games/{game}/play-sessions/{play_session}/stop', StopPlaySessionController::class)
            ->name('games.play-sessions.stop');
        Route::post('games/{game}/play-sessions/{play_session}/stop', FinishPlaySessionController::class)
            ->name('games.play-sessions.finish');
        Route::get('games/{game}/play-sessions/{play_session}/edit', [PlaySessionController::class, 'edit'])
            ->name('games.play-sessions.edit');
        Route::put('games/{game}/play-sessions/{play_session}', [PlaySessionController::class, 'update'])
            ->name('games.play-sessions.update');
        Route::delete('games/{game}/play-sessions/{play_session}', [PlaySessionController::class, 'destroy'])
            ->name('games.play-sessions.destroy');
    });
});

Route::middleware('auth')->group(function (): void {
    // User Profile...
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    // User...
    Route::delete('user', [UserController::class, 'destroy'])->name('user.destroy');

    // User Password...
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');

    // User Two-Factor Authentication...
    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // User Passkeys...
    Route::get('settings/passkeys', [UserPasskeyController::class, 'index'])
        ->name('user-passkey.index');
});

Route::middleware('guest')->group(function (): void {
    // User...
    Route::get('register', [UserController::class, 'create'])
        ->name('register');
    Route::post('register', [UserController::class, 'store'])
        ->name('register.store');

    // User Password...
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');

    // User Email Reset Notification...
    Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])
        ->name('password.email');

    // Session...
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    // User Email Verification...
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // User Email Verification...
    Route::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Session...
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});

// Passkey Endpoints...
Route::get('.well-known/passkey-endpoints', fn () => response()->json([
    'enroll' => route('user-passkey.index'),
    'manage' => route('user-passkey.index'),
]))->name('well-known.passkeys');
