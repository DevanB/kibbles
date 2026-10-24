<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tag;
use App\Rules\UniqueOwnedTagName;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class UpdateTag
{
    public function handle(Tag $tag, string $name): Tag
    {
        try {
            $tag->update([
                'name' => $name,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw UniqueOwnedTagName::conflict();
        }

        return $tag;
    }
}
