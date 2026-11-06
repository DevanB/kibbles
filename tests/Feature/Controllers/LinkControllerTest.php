<?php

declare(strict_types=1);

use App\Enums\LinkPreviewStatus;
use App\Jobs\FetchLinkPreview;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('redirects guests to login', function (string $method, string $route, array $parameters = []): void {
    $this->{$method}(route($route, $parameters))->assertRedirectToRoute('login');
})->with([
    'index' => ['get', 'bookmarks.index'],
    'create' => ['get', 'bookmarks.create'],
    'store' => ['post', 'bookmarks.store'],
    'show' => ['get', 'bookmarks.show', ['link' => '00000000-0000-0000-0000-000000000001']],
    'destroy' => ['delete', 'bookmarks.destroy', ['link' => '00000000-0000-0000-0000-000000000001']],
]);

it('redirects unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertRedirectToRoute('verification.notice');
});

it('returns 404 for a missing link', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('bookmarks.show', Str::uuid()->toString()))
        ->assertNotFound();
});

it('lists only the authenticated user links', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $owned = Link::factory()->recycle($user)->create([
        'url' => 'https://example.com/mine',
        'title' => 'Mine',
    ]);
    Link::factory()->create(['title' => 'Someone else']);

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bookmarks/index')
            ->has('links.data', 1)
            ->has('links.data.0', fn ($link) => $link
                ->where('id', $owned->id)
                ->where('url', 'https://example.com/mine')
                ->where('title', 'Mine')
                ->where('previewStatus', LinkPreviewStatus::Ready->value)
                ->where('tags', [])
                ->etc())
            ->where('activeTag', null));
});

it('lists links newest first', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $older = Link::factory()->recycle($user)->create(['created_at' => now()->subDay()]);
    $newer = Link::factory()->recycle($user)->create(['created_at' => now()]);

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('links.data.0.id', $newer->id)
            ->where('links.data.1.id', $older->id));
});

it('returns a cursor page of links and the following page in newest-first order', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $links = Link::factory()
        ->recycle($user)
        ->count(25)
        ->sequence(fn ($sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
        ])
        ->create();

    $first = $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertOk();

    $nextCursor = null;
    $firstPageSize = 0;

    $first->assertInertia(function ($page) use ($links, &$nextCursor, &$firstPageSize): void {
        $props = $page->toArray()['props']['links'];
        $firstPageSize = count($props['data']);
        $nextCursor = $props['next_cursor'];

        expect($firstPageSize)->toBeGreaterThan(0)
            ->and($firstPageSize)->toBeLessThan($links->count())
            ->and($props['data'][0]['id'])->toBe($links[0]->id)
            ->and($nextCursor)->not->toBeEmpty();
    });

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['cursor' => $nextCursor]))
        ->assertOk()
        ->assertInertia(function ($page) use ($links, $firstPageSize): void {
            $props = $page->toArray()['props']['links'];

            expect($props['data'][0]['id'])->toBe($links[$firstPageSize]->id)
                ->and($props['data'])->toHaveCount($links->count() - $firstPageSize)
                ->and($props['next_cursor'])->toBeNull();
        });
});

it('includes attached tags on the link payload', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $link->tags()->attach($tag);

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('links.data.0.tags.0.id', $tag->id)
            ->where('links.data.0.tags.0.name', 'Laravel'));

    $this->actingAs($user)
        ->withHeaders(['X-InertiaUI-Modal' => 'true'])
        ->get(route('bookmarks.show', $link))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('bookmarks/show')
            ->where('link.tags.0.id', $tag->id)
            ->where('link.tags.0.name', 'Laravel'));
});

it('filters links to one tag at a time', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $laravel = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $pest = Tag::factory()->recycle($user)->create(['name' => 'Pest']);
    $taggedLaravel = Link::factory()->recycle($user)->create(['title' => 'Laravel post']);
    $taggedPest = Link::factory()->recycle($user)->create(['title' => 'Pest post']);
    $untagged = Link::factory()->recycle($user)->create(['title' => 'Loose post']);
    $taggedLaravel->tags()->attach($laravel);
    $taggedPest->tags()->attach($pest);

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => $laravel->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('links.data', 1)
            ->where('links.data.0.id', $taggedLaravel->id)
            ->where('activeTag.id', $laravel->id)
            ->where('activeTag.name', 'Laravel'));

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => 'untagged']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('links.data', 1)
            ->where('links.data.0.id', $untagged->id)
            ->where('activeTag.id', 'untagged')
            ->where('activeTag.name', 'Untagged'));
});

it('returns 404 when filtering by another user tag', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $foreign = Tag::factory()->create(['name' => 'Secret']);

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => $foreign->id]))
        ->assertNotFound();
});

it('lists every link when the tag query is empty', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => '']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('links.data', 1)
            ->where('links.data.0.id', $link->id)
            ->where('activeTag', null));
});

it('ignores a non-string tag query and lists every link', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['tag' => ['laravel']]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('links.data', 1)
            ->where('links.data.0.id', $link->id)
            ->where('activeTag', null));
});

it('saves a public URL as pending and queues a preview fetch', function (): void {
    Queue::fake([FetchLinkPreview::class]);

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.store'), ['url' => 'https://Example.com/post?utm_source=x'])
        ->assertRedirectToRoute('bookmarks.index');

    $link = $user->links()->first();

    expect($link)->not->toBeNull()
        ->and($link->url)->toBe('https://example.com/post')
        ->and($link->preview_status)->toBe(LinkPreviewStatus::Pending);

    Queue::assertPushed(FetchLinkPreview::class, fn (FetchLinkPreview $job): bool => $job->linkId === $link->id);
});

it('rejects an invalid URL', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.create')
        ->post(route('bookmarks.store'), ['url' => 'javascript:alert(1)'])
        ->assertRedirectToRoute('bookmarks.create')
        ->assertSessionHasErrors(['url' => 'Enter a valid http or https URL.']);
});

it('requires a URL when saving a link', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.create')
        ->post(route('bookmarks.store'), [])
        ->assertRedirectToRoute('bookmarks.create')
        ->assertSessionHasErrors('url');
});

it('forbids another user from viewing or removing a link', function (): void {
    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($owner)->create();

    $this->actingAs($intruder)
        ->get(route('bookmarks.show', $link))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('bookmarks.destroy', $link))
        ->assertForbidden();

    expect($link->fresh())->not->toBeNull();
});

it('removes an owned link and keeps its tags', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->create();
    $tag = Tag::factory()->recycle($user)->create(['name' => 'Laravel']);
    $link->tags()->attach($tag);

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->delete(route('bookmarks.destroy', $link))
        ->assertRedirectToRoute('bookmarks.index');

    expect($link->fresh())->toBeNull()
        ->and($tag->fresh())->not->toBeNull()
        ->and($tag->links()->count())->toBe(0);
});
