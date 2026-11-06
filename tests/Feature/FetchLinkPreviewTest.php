<?php

declare(strict_types=1);

use App\Enums\LinkPreviewStatus;
use App\Jobs\FetchLinkPreview;
use App\Models\Link;
use App\Models\User;
use App\Services\LinkPreviewClient;
use Illuminate\Support\Facades\Http;

it('stores title, site name, description, and image from a public fetch', function (): void {
    Http::fake([
        'http://1.1.1.1/*' => Http::response(
            '<html><head><meta property="og:title" content="Fetched"><meta property="og:site_name" content="News"><meta property="og:description" content="Body"><meta property="og:image" content="https://example.com/cover.jpg"></head></html>',
            200,
        ),
    ]);

    $user = User::factory()->create();
    $link = Link::factory()->recycle($user)->pending()->create([
        'url' => 'http://1.1.1.1/story',
        'title' => '1.1.1.1',
    ]);

    $job = new FetchLinkPreview($link->id);
    $job->handle(resolve(LinkPreviewClient::class));

    expect($job->uniqueId())
        ->toBe(new FetchLinkPreview($link->id)->uniqueId())
        ->and($job->uniqueId())->not->toBe(new FetchLinkPreview('00000000-0000-0000-0000-000000000099')->uniqueId())
        ->and($link->refresh()->preview_status)->toBe(LinkPreviewStatus::Ready)
        ->and($link->title)->toBe('Fetched')
        ->and($link->site_name)->toBe('News')
        ->and($link->description)->toBe('Body')
        ->and($link->image_url)->toBe('https://example.com/cover.jpg');
});

it('marks the preview failed when the fetch is refused', function (): void {
    Http::fake();

    $user = User::factory()->create();
    $link = Link::factory()->recycle($user)->pending()->create([
        'url' => 'http://127.0.0.1/admin',
    ]);

    new FetchLinkPreview($link->id)->handle(resolve(LinkPreviewClient::class));

    expect($link->refresh()->preview_status)->toBe(LinkPreviewStatus::Failed);

    Http::assertNothingSent();
});

it('marks the preview failed when the stored URL is not fetchable as a link', function (): void {
    Http::fake();

    $user = User::factory()->create();
    $link = Link::factory()->recycle($user)->pending()->create([
        'url' => 'not-a-url',
    ]);

    new FetchLinkPreview($link->id)->handle(resolve(LinkPreviewClient::class));

    expect($link->refresh()->preview_status)->toBe(LinkPreviewStatus::Failed);

    Http::assertNothingSent();
});

it('ignores a job whose link was removed', function (): void {
    Http::fake();

    new FetchLinkPreview('00000000-0000-0000-0000-000000000099')->handle(resolve(LinkPreviewClient::class));

    Http::assertNothingSent();
});
