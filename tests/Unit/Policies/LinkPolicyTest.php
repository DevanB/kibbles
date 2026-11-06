<?php

declare(strict_types=1);

use App\Models\Link;
use App\Models\User;
use App\Policies\LinkPolicy;

it('allows the owner to view, update, delete, and retry a failed preview', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $failed = Link::factory()->recycle($owner)->failed()->create();
    $ready = Link::factory()->recycle($owner)->create();
    $policy = new LinkPolicy;

    expect($policy->viewAny())->toBeTrue()
        ->and($policy->create())->toBeTrue()
        ->and($policy->view($owner, $failed))->toBeTrue()
        ->and($policy->update($owner, $failed))->toBeTrue()
        ->and($policy->delete($owner, $failed))->toBeTrue()
        ->and($policy->retry($owner, $failed))->toBeTrue()
        ->and($policy->retry($owner, $ready))->toBeFalse()
        ->and($policy->view($other, $failed))->toBeFalse()
        ->and($policy->update($other, $failed))->toBeFalse()
        ->and($policy->delete($other, $failed))->toBeFalse()
        ->and($policy->retry($other, $failed))->toBeFalse();
});
