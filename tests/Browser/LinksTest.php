<?php

declare(strict_types=1);

use App\Enums\LinkPreviewStatus;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('renders the empty bookmarks page and saves a link from the modal', function (): void {
    Queue::fake();
    Http::fake();

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertSee('Dashboard')
        ->assertSee('Games')
        ->assertSee('Bookmarks')
        ->assertSee('X')
        ->click('@sidebar-bookmarks')
        ->assertPathIs('/bookmarks')
        ->assertSee('No bookmarks yet')
        ->assertSee('Paste a URL to keep it here.')
        ->assertPresent('@add-link-button')
        ->assertNoJavaScriptErrors();

    $page->click('@add-link-button')
        ->fill('@link-url-input', 'https://example.com/from-ui')
        ->click('@save-link-button')
        ->assertSee('Saved to Kibbles.')
        ->assertSee('example.com')
        ->assertNoJavaScriptErrors();

    $link = $user->links()->first();

    expect($link)->not->toBeNull()
        ->and($link->url)->toBe('https://example.com/from-ui')
        ->and($link->preview_status)->toBe(LinkPreviewStatus::Pending);
});

it('shows a pending preview, retries a failed one, and removes a link', function (): void {
    Http::fake([
        'http://1.1.1.1/*' => Http::response(
            '<html><head><title>Recovered</title></head></html>',
            200,
        ),
    ]);

    $user = User::factory()->withoutTwoFactor()->create();
    $pending = Link::factory()->recycle($user)->pending()->create([
        'url' => 'http://1.1.1.1/pending',
        'title' => '1.1.1.1',
        'created_at' => now(),
    ]);
    $failed = Link::factory()->recycle($user)->failed()->create([
        'url' => 'http://1.1.1.1/failed',
        'title' => 'Failed fetch',
        'created_at' => now()->subMinute(),
    ]);
    $ready = Link::factory()->recycle($user)->create([
        'url' => 'https://example.com/keep',
        'title' => 'Keep me',
        'created_at' => now()->subMinutes(2),
    ]);

    $this->actingAs($user);

    $page = visit(route('bookmarks.index'));

    $page->assertPresent('@link-'.$pending->id)
        ->assertSee('Fetching preview…')
        ->assertPresent('@retry-link-preview-'.$failed->id)
        ->assertPresent('@remove-link-button-'.$ready->id)
        ->assertNoJavaScriptErrors();

    $page->click('@retry-link-preview-'.$failed->id)
        ->assertSee('Retrying preview.')
        ->assertNoJavaScriptErrors();

    expect($failed->refresh()->preview_status)->toBe(LinkPreviewStatus::Ready)
        ->and($failed->title)->toBe('Recovered');

    $page->click('@remove-link-button-'.$ready->id)
        ->assertSee('Remove this bookmark?')
        ->click('@cancel-remove-link-button-'.$ready->id)
        ->assertPresent('@link-'.$ready->id)
        ->assertNoJavaScriptErrors();

    $page->click('@remove-link-button-'.$ready->id)
        ->click('@confirm-remove-link-button-'.$ready->id)
        ->assertSee('Removed.')
        ->assertNoJavaScriptErrors();

    expect($ready->fresh())->toBeNull();
});

it('tags a link, creates a tag inline, and filters from the sidebar', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $laravel = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $tagged = Link::factory()->recycle($user)->create([
        'title' => 'Tagged with Laravel.',
        'created_at' => now()->subMinute(),
    ]);
    $untagged = Link::factory()->recycle($user)->create([
        'title' => 'Needs a tag.',
        'created_at' => now()->subMinutes(2),
    ]);
    $loose = Link::factory()->recycle($user)->create([
        'title' => 'Still untagged.',
        'created_at' => now()->subMinutes(3),
    ]);
    $tagged->tags()->attach($laravel);

    $this->actingAs($user);

    $page = visit(route('bookmarks.index'));

    $page->assertSee('Tagged with Laravel.')
        ->assertSee('Laravel')
        ->assertPresent('@sidebar-bookmarks-all')
        ->assertPresent('@sidebar-bookmarks-untagged')
        ->assertPresent('@sidebar-bookmarks-tag-'.$laravel->id)
        ->assertPresent('@tag-link-button-'.$untagged->id)
        ->assertNoJavaScriptErrors();

    $page->click('@tag-link-button-'.$untagged->id)
        ->assertPresent('@link-tag-combobox-'.$untagged->id)
        ->click('@link-tag-option-'.$laravel->id)
        ->assertPresent('@link-tag-'.$untagged->id.'-'.$laravel->id)
        ->assertNoJavaScriptErrors();

    expect($untagged->tags()->whereKey($laravel)->exists())->toBeTrue();

    $page->assertPresent('@link-tag-combobox-'.$untagged->id)
        ->fill('@link-tag-combobox-input-'.$untagged->id, 'Pest')
        ->click('@create-link-tag-option-'.$untagged->id)
        ->assertSee('Pest')
        ->assertNoJavaScriptErrors();

    $pest = $user->tags()->named('Pest')->first();

    expect($pest)->not->toBeNull()
        ->and($untagged->tags()->whereKey($pest)->exists())->toBeTrue();

    $page->click('@sidebar-bookmarks-tag-'.$laravel->id)
        ->assertPathIs('/bookmarks')
        ->assertPresent('@active-tag-name')
        ->assertPresent('@link-'.$tagged->id)
        ->assertPresent('@link-'.$untagged->id)
        ->assertMissing('@link-'.$loose->id)
        ->assertNoJavaScriptErrors();

    $page->click('@sidebar-bookmarks-untagged')
        ->assertSee('Untagged')
        ->assertPresent('@link-'.$loose->id)
        ->assertMissing('@link-'.$tagged->id)
        ->assertNoJavaScriptErrors();
});
