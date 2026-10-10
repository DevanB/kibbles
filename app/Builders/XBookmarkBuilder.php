<?php

declare(strict_types=1);

namespace App\Builders;

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
