<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\Link;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Link>
 */
final class LinkBuilder extends Builder
{
    public function newestFirst(): static
    {
        return $this->latest('created_at')->orderByDesc('id');
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
}
