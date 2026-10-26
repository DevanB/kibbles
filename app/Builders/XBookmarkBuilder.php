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
    public function newestFirst(): static
    {
        return $this->latest('first_seen_at')->orderByDesc('id');
    }

    public function oldestFirst(): static
    {
        return $this->oldest('first_seen_at')->orderBy('id');
    }

    public function unreviewed(): static
    {
        return $this->whereNull('reviewed_at');
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
