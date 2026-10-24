<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tag;
use App\Models\User;
use App\Rules\UniqueOwnedTagName;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class CreateTag
{
    public function handle(User $user, string $name): Tag
    {
        try {
            return $user->tags()->create([
                'name' => $name,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw UniqueOwnedTagName::conflict();
        }
    }

    public function findOrCreate(User $user, string $name): Tag
    {
        return $user->tags()->named($name)->first() ?? $this->handle($user, $name);
    }
}
