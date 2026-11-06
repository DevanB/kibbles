<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LinkPreviewStatus;
use App\Jobs\FetchLinkPreview;
use App\Models\Link;
use App\Models\User;
use App\ValueObjects\LinkUrl;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class SaveLink
{
    public function handle(User $user, string $rawUrl): Link
    {
        $url = LinkUrl::parse($rawUrl);

        try {
            $link = $user->links()->create([
                'url' => $url->value,
                'title' => $url->host,
                'preview_status' => $url->fetchable
                    ? LinkPreviewStatus::Pending
                    : LinkPreviewStatus::Ready,
            ]);
        } catch (UniqueConstraintViolationException) {
            return $user->links()->where('url', $url->value)->firstOrFail();
        }

        if ($url->fetchable) {
            dispatch(new FetchLinkPreview($link->id));
        }

        return $link;
    }
}
