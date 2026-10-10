<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\XConnection;

it('hides tokens and exposes a wire payload without them', function (): void {
    $connection = XConnection::factory()->synced()->create([
        'username' => 'devan',
        'access_token' => 'secret-access',
        'refresh_token' => 'secret-refresh',
    ])->refresh();

    expect($connection->access_token)->toBe('secret-access')
        ->and($connection->toArray())->not->toHaveKey('access_token')
        ->and($connection->toWire())->toBe([
            'username' => 'devan',
            'lastSyncedAt' => $connection->last_synced_at?->toIso8601String(),
            'lastFullSyncedAt' => $connection->last_full_synced_at?->toIso8601String(),
        ])
        ->and($connection->user)->toBeInstanceOf(User::class);
});
