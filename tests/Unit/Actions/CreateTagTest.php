<?php

declare(strict_types=1);

use App\Actions\CreateTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('creates a tag with the typed casing', function (): void {
    $user = User::factory()->create();

    $tag = resolve(CreateTag::class)->handle($user, 'Laravel');

    expect($tag)->toBeInstanceOf(Tag::class)
        ->and($tag->name)->toBe('Laravel')
        ->and($tag->user()->is($user))->toBeTrue();
});

it('maps a unique constraint violation to a name validation error', function (): void {
    $user = User::factory()->create();
    Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    try {
        resolve(CreateTag::class)->handle($user, 'laravel');
        $this->fail('Expected a validation exception for a unique tag name conflict.');
    } catch (ValidationException $validationException) {
        expect($validationException->status)->toBe(422)
            ->and($validationException->errors())->toBe([
                'name' => ['You already have a tag with this name.'],
            ]);
    }
});

it('returns the existing tag when finding or creating a folded name', function (): void {
    $user = User::factory()->create();
    $existing = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);

    $tag = resolve(CreateTag::class)->findOrCreate($user, 'laravel');

    expect($tag->is($existing))->toBeTrue()
        ->and($user->tags()->count())->toBe(1)
        ->and($existing->refresh()->name)->toBe('Laravel');
});

it('creates a tag when finding or creating a new name', function (): void {
    $user = User::factory()->create();

    $tag = resolve(CreateTag::class)->findOrCreate($user, 'Pest');

    expect($tag->name)->toBe('Pest')
        ->and($user->tags()->count())->toBe(1);
});
