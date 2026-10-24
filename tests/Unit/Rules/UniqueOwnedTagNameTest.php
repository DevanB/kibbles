<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use App\Rules\UniqueOwnedTagName;

it('rejects a case-insensitive duplicate name for the same user', function (): void {
    $user = User::factory()->create();
    Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $failed = null;

    new UniqueOwnedTagName($user)->validate('name', 'laravel', function (string $message) use (&$failed): void {
        $failed = $message;
    });

    expect($failed)->toBe(UniqueOwnedTagName::message());
});

it('allows the same folded name when ignoring the current tag', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $failed = false;

    new UniqueOwnedTagName($user, $tag)->validate('name', 'laravel', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('allows another user to reuse the name', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Tag::factory()->recycle($other)->create(['name' => 'Laravel']);
    $failed = false;

    new UniqueOwnedTagName($owner)->validate('name', 'Laravel', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('skips values that are not strings', function (): void {
    $user = User::factory()->create();
    $failed = false;

    new UniqueOwnedTagName($user)->validate('name', 123, function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});
