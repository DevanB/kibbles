<?php

declare(strict_types=1);

use App\Actions\SaveLink;
use App\Enums\LinkPreviewStatus;
use App\Jobs\FetchLinkPreview;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('creates a pending link and queues a preview fetch', function (): void {
    Queue::fake([FetchLinkPreview::class]);

    $user = User::factory()->create();
    $link = resolve(SaveLink::class)->handle($user, 'https://Example.com/post?utm_source=x');

    expect($link->url)->toBe('https://example.com/post')
        ->and($link->preview_status)->toBe(LinkPreviewStatus::Pending)
        ->and($user->links()->count())->toBe(1);

    Queue::assertPushed(FetchLinkPreview::class, fn (FetchLinkPreview $job): bool => $job->linkId === $link->id);
});

it('returns the existing row when the normalized URL is already saved', function (): void {
    Queue::fake([FetchLinkPreview::class]);

    $user = User::factory()->create();
    $first = resolve(SaveLink::class)->handle($user, 'https://example.com/post');
    $second = resolve(SaveLink::class)->handle($user, 'https://example.com/post?utm_campaign=later');

    expect($second->is($first))->toBeTrue()
        ->and($user->links()->count())->toBe(1);

    Queue::assertPushed(FetchLinkPreview::class, 1);
});

it('saves a LAN URL as ready and does not fetch a preview', function (): void {
    Queue::fake([FetchLinkPreview::class]);

    $user = User::factory()->create();
    $link = resolve(SaveLink::class)->handle($user, 'http://192.168.1.20/queue');

    expect($link->url)->toBe('http://192.168.1.20/queue')
        ->and($link->preview_status)->toBe(LinkPreviewStatus::Ready)
        ->and($link->title)->toBe('192.168.1.20');

    Queue::assertNothingPushed();
});
