<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * @extends Builder<Tag>
 */
final class TagBuilder extends Builder
{
    public function alphabetical(): static
    {
        return $this->orderBy('name')->orderBy('id');
    }

    public function named(string $name): static
    {
        return $this->where('name_normalized', Str::lower($name));
    }
}
