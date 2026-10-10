<?php

declare(strict_types=1);

use App\Jobs\SyncXBookmarks;
use App\Models\User;
use App\Models\XConnection;
use Illuminate\Support\Facades\Queue;

it('redirects guests to login', function (): void {
    $this->post(route('bookmark-syncs.store'))
        ->assertRedirectToRoute('login');
});

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->post(route('bookmark-syncs.store'))
        ->assertRedirectToRoute('verification.notice');
});

it('forbids syncing without an X connection', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->fromRoute('dashboard')
        ->post(route('bookmark-syncs.store'))
        ->assertForbidden();
});

it('dispatches an incremental bookmark sync', function (): void {
    Queue::fake([SyncXBookmarks::class]);

    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->fromRoute('dashboard')
        ->post(route('bookmark-syncs.store'))
        ->assertRedirectToRoute('dashboard');

    Queue::assertPushed(SyncXBookmarks::class, fn (SyncXBookmarks $job): bool => $job->userId === $user->id && $job->full === false);
});
