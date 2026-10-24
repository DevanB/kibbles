<?php

declare(strict_types=1);

use App\Actions\DetachBookmarkTag;
use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;

it('detaches a tag and keeps the tag and bookmark', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $bookmark = XBookmark::factory()->recycle($user)->create();
    $bookmark->tags()->attach($tag);

    resolve(DetachBookmarkTag::class)->handle($bookmark, $tag);

    expect($bookmark->tags()->count())->toBe(0)
        ->and($tag->fresh())->not->toBeNull()
        ->and($bookmark->fresh())->not->toBeNull();
});
