<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\LinkPreviewStatus;
use App\Exceptions\InvalidLinkUrl;
use App\Exceptions\LinkPreviewFailed;
use App\Models\Link;
use App\Services\LinkPreviewClient;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class FetchLinkPreview implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public string $linkId) {}

    public function uniqueId(): string
    {
        return $this->linkId;
    }

    public function handle(LinkPreviewClient $client): void
    {
        $link = Link::query()->find($this->linkId);

        if (! $link instanceof Link) {
            return;
        }

        try {
            $preview = $client->fetch($link->url);
        } catch (LinkPreviewFailed|InvalidLinkUrl) {
            $link->update([
                'preview_status' => LinkPreviewStatus::Failed,
            ]);

            return;
        }

        $link->update([
            'title' => $preview->title ?? $link->title,
            'site_name' => $preview->siteName,
            'description' => $preview->description,
            'image_url' => $preview->imageUrl,
            'preview_status' => LinkPreviewStatus::Ready,
        ]);
    }
}
