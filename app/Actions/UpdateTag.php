<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tag;

final readonly class UpdateTag
{
    public function handle(Tag $tag, string $name): Tag
    {
        $tag->update([
            'name' => $name,
        ]);

        return $tag;
    }
}
