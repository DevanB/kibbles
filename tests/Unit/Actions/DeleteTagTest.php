<?php

declare(strict_types=1);

use App\Actions\DeleteTag;
use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;

it('deletes a tag and leaves the bookmarks', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $bookmark->tags()->attach($tag);

    resolve(DeleteTag::class)->handle($tag);

    expect($tag->fresh())->toBeNull()
        ->and($bookmark->fresh())->not->toBeNull()
        ->and($bookmark->tags()->count())->toBe(0);
});
