<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use App\Policies\TagPolicy;

it('allows the owner to view, update, and delete a tag', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tag = Tag::factory()->recycle($owner)->create();
    $policy = new TagPolicy;

    expect($policy->viewAny())->toBeTrue()
        ->and($policy->create())->toBeTrue()
        ->and($policy->view($owner, $tag))->toBeTrue()
        ->and($policy->update($owner, $tag))->toBeTrue()
        ->and($policy->delete($owner, $tag))->toBeTrue()
        ->and($policy->view($other, $tag))->toBeFalse()
        ->and($policy->update($other, $tag))->toBeFalse()
        ->and($policy->delete($other, $tag))->toBeFalse();
});
