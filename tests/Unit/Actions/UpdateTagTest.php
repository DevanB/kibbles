<?php

declare(strict_types=1);

use App\Actions\UpdateTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('renames a tag and keeps the typed casing', function (): void {
    $tag = Tag::factory()->create(['name' => 'Laravel']);

    $updated = resolve(UpdateTag::class)->handle($tag, 'PHP');

    expect($updated->name)->toBe('PHP')
        ->and($tag->refresh()->name)->toBe('PHP');
});

it('maps a unique constraint violation to a name validation error', function (): void {
    $user = User::factory()->create();
    Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Pest']);

    try {
        resolve(UpdateTag::class)->handle($tag, 'laravel');
        $this->fail('Expected a validation exception for a unique tag name conflict.');
    } catch (ValidationException $validationException) {
        expect($validationException->status)->toBe(422)
            ->and($validationException->errors())->toBe([
                'name' => ['You already have a tag with this name.'],
            ])
            ->and($tag->refresh()->name)->toBe('Pest');
    }
});
