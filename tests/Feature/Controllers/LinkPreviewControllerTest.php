<?php

declare(strict_types=1);

use App\Enums\LinkPreviewStatus;
use App\Jobs\FetchLinkPreview;
use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('redirects guests to login', function (): void {
    $this->post(route('bookmarks.previews.store', '00000000-0000-0000-0000-000000000001'))
        ->assertRedirectToRoute('login');
});

it('retries a failed preview', function (): void {
    Queue::fake([FetchLinkPreview::class]);

    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->failed()->create();

    $this->actingAs($user)
        ->fromRoute('bookmarks.index')
        ->post(route('bookmarks.previews.store', $link))
        ->assertRedirectToRoute('bookmarks.index');

    expect($link->refresh()->preview_status)->toBe(LinkPreviewStatus::Pending);

    Queue::assertPushed(FetchLinkPreview::class, fn (FetchLinkPreview $job): bool => $job->linkId === $link->id);
});

it('forbids retrying a preview that has not failed', function (): void {
    Queue::fake([FetchLinkPreview::class]);

    $user = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($user)->pending()->create();

    $this->actingAs($user)
        ->post(route('bookmarks.previews.store', $link))
        ->assertForbidden();

    expect($link->refresh()->preview_status)->toBe(LinkPreviewStatus::Pending);

    Queue::assertNothingPushed();
});

it('forbids another user from retrying a preview', function (): void {
    Queue::fake([FetchLinkPreview::class]);

    $owner = User::factory()->withoutTwoFactor()->create();
    $intruder = User::factory()->withoutTwoFactor()->create();
    $link = Link::factory()->recycle($owner)->failed()->create();

    $this->actingAs($intruder)
        ->post(route('bookmarks.previews.store', $link))
        ->assertForbidden();

    expect($link->refresh()->preview_status)->toBe(LinkPreviewStatus::Failed);

    Queue::assertNothingPushed();
});
