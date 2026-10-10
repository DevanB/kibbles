<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\XConnection;
use App\Policies\XConnectionPolicy;

it('allows any authenticated user to create a connection', function (): void {
    expect((new XConnectionPolicy)->create())->toBeTrue();
});

it('allows the owner to update or delete the connection', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $connection = XConnection::factory()->recycle($owner)->create();
    $policy = new XConnectionPolicy;

    expect($policy->update($owner, $connection))->toBeTrue()
        ->and($policy->delete($owner, $connection))->toBeTrue()
        ->and($policy->update($other, $connection))->toBeFalse()
        ->and($policy->delete($other, $connection))->toBeFalse();
});
