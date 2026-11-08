<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\Tag;
use App\Models\XBookmark;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @extends Builder<XBookmark>
 */
final class XBookmarkBuilder extends Builder
{
    private const int REVIEW_INTERVAL_DAYS = 30;

    public function newestFirst(): static
    {
        return $this->latest('first_seen_at')->orderByDesc('id');
    }

    public function dueForReview(): static
    {
        return $this->where(function (Builder $query): void {
            $query->whereNull('reviewed_at')
                ->orWhere('reviewed_at', '<', now()->subDays(self::REVIEW_INTERVAL_DAYS));
        });
    }

    /**
     * Never-reviewed rows lead on every driver, then the oldest review, then first seen, then id.
     */
    public function inReviewOrder(): static
    {
        return $this->orderByRaw('case when reviewed_at is null then 0 else 1 end')
            ->oldest('reviewed_at')
            ->oldest('first_seen_at')
            ->orderBy('id');
    }

    public function reviewedToday(): static
    {
        return $this->where('reviewed_at', '>=', today());
    }

    public function tagged(Tag $tag): static
    {
        return $this->whereHas('tags', function (Builder $query) use ($tag): void {
            $query->whereKey($tag->getKey());
        });
    }

    public function untagged(): static
    {
        return $this->whereDoesntHave('tags');
    }

    /**
     * @return Collection<int, string>
     */
    public function xPostIds(): Collection
    {
        $ids = [];

        foreach ($this->pluck('x_post_id') as $id) {
            if (is_string($id)) {
                $ids[] = $id;
            }
        }

        return collect($ids);
    }
}
