<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\XBookmark;
use App\Policies\XBookmarkPolicy;
use Illuminate\Support\Facades\Gate;

it('allows the owner to view or delete a bookmark', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $bookmark = XBookmark::factory()->recycle($owner)->create();
    $policy = new XBookmarkPolicy;

    $this->actingAs($owner);
    Gate::authorize('viewAny', XBookmark::class);

    expect($policy->view($owner, $bookmark))->toBeTrue()
        ->and($policy->delete($owner, $bookmark))->toBeTrue()
        ->and($policy->view($other, $bookmark))->toBeFalse()
        ->and($policy->delete($other, $bookmark))->toBeFalse();
});
