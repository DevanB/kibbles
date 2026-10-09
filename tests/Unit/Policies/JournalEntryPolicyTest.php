<?php

declare(strict_types=1);

use App\Policies\JournalEntryPolicy;

it('allows authenticated users to view any journal entries', function (): void {
    $policy = new JournalEntryPolicy;

    expect($policy->viewAny())->toBeTrue();
});
