<?php

declare(strict_types=1);

use App\Models\XConnection;

it('hides tokens from the serialized model and the wire payload', function (): void {
    $connection = XConnection::factory()->synced()->create([
        'username' => 'devan',
        'access_token' => 'secret-access',
        'refresh_token' => 'secret-refresh',
    ])->refresh();

    $serialized = json_encode($connection->toArray());
    $wire = json_encode($connection->toWire());

    expect($serialized)->not->toContain('secret-access')
        ->and($serialized)->not->toContain('secret-refresh')
        ->and($wire)->not->toContain('secret-access')
        ->and($wire)->not->toContain('secret-refresh');
});
