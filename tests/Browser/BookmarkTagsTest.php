<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Models\User;
use App\Models\XBookmark;
use App\Models\XConnection;

it('tags a bookmark, creates a tag inline, and filters from the sidebar', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->synced()->create(['username' => 'devan']);
    $laravel = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $tagged = XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Taylor Otwell',
        'text' => 'Tagged with Laravel.',
        'first_seen_at' => now()->subMinute(),
    ]);
    $untagged = XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Nuno Maduro',
        'text' => 'Needs a tag.',
        'first_seen_at' => now()->subMinutes(2),
    ]);
    $loose = XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Ada',
        'text' => 'Still untagged.',
        'first_seen_at' => now()->subMinutes(3),
    ]);
    $tagged->tags()->attach($laravel);

    $this->actingAs($user);

    $page = visit(route('bookmarks.index'));

    $page->assertSee('Bookmarks')
        ->assertSee('Taylor Otwell')
        ->assertSee('Laravel')
        ->assertPresent('@sidebar-x-all')
        ->assertPresent('@sidebar-x-untagged')
        ->assertPresent('@sidebar-x-tag-'.$laravel->id)
        ->assertPresent('@tag-bookmark-button-'.$untagged->id)
        ->assertNoJavaScriptErrors();

    $page->click('@tag-bookmark-button-'.$untagged->id)
        ->assertPresent('@tag-combobox-'.$untagged->id)
        ->click('@tag-option-'.$laravel->id)
        ->assertSee('Laravel')
        ->assertPresent('@bookmark-tag-'.$untagged->id.'-'.$laravel->id)
        ->assertNoJavaScriptErrors();

    expect($untagged->tags()->whereKey($laravel)->exists())->toBeTrue();

    $page->assertPresent('@tag-combobox-'.$untagged->id)
        ->fill('@tag-combobox-input-'.$untagged->id, 'Pest')
        ->click('@create-tag-option-'.$untagged->id)
        ->assertSee('Pest')
        ->assertNoJavaScriptErrors();

    $pest = $user->tags()->named('Pest')->first();

    expect($pest)->not->toBeNull()
        ->and($untagged->tags()->whereKey($pest)->exists())->toBeTrue();

    $page->click('@sidebar-x-tag-'.$laravel->id)
        ->assertPathIs('/bookmarks')
        ->assertSee('Bookmarks')
        ->assertSee('Laravel')
        ->assertPresent('@active-tag-name')
        ->assertPresent('@bookmark-'.$tagged->id)
        ->assertPresent('@bookmark-'.$untagged->id)
        ->assertMissing('@bookmark-'.$loose->id)
        ->assertNoJavaScriptErrors();

    $page->click('@sidebar-x-untagged')
        ->assertSee('Untagged')
        ->assertPresent('@bookmark-'.$loose->id)
        ->assertMissing('@bookmark-'.$tagged->id)
        ->assertNoJavaScriptErrors();
});

it('renames and deletes a tag without deleting bookmarks', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    XConnection::factory()->recycle($user)->synced()->create(['username' => 'devan']);
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $bookmark = XBookmark::factory()->recycle($user)->withoutMedia()->create([
        'author_name' => 'Taylor Otwell',
        'text' => 'Keep this bookmark.',
    ]);
    $bookmark->tags()->attach($tag);

    $this->actingAs($user);

    $page = visit(route('bookmarks.index'));

    $page->click('@manage-tags-button')
        ->assertPathIs('/tags')
        ->assertSee('Tags')
        ->assertSee('Laravel')
        ->assertPresent('@tag-row-'.$tag->id)
        ->assertNoJavaScriptErrors();

    $page->click('@rename-tag-button-'.$tag->id)
        ->assertSee('Rename tag')
        ->fill('@tag-name-input-'.$tag->id, 'PHP')
        ->click('@save-tag-button-'.$tag->id)
        ->assertSee('Tag renamed.')
        ->assertSee('PHP')
        ->assertNoJavaScriptErrors();

    expect($tag->refresh()->name)->toBe('PHP')
        ->and($bookmark->fresh())->not->toBeNull();

    $page->click('@delete-tag-button-'.$tag->id)
        ->assertSee('Delete PHP?')
        ->click('@confirm-delete-tag-button-'.$tag->id)
        ->assertSee('Tag deleted.')
        ->assertPresent('@tags-empty')
        ->assertNoJavaScriptErrors();

    expect($tag->fresh())->toBeNull()
        ->and($bookmark->fresh())->not->toBeNull()
        ->and($bookmark->tags()->count())->toBe(0);
});
