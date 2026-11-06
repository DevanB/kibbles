<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LinkPreviewStatus;
use App\Models\Link;
use App\Models\User;

final class LinkPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, Link $link): bool
    {
        return $link->user()->is($user);
    }

    public function create(): bool
    {
        return true;
    }

    public function update(User $user, Link $link): bool
    {
        return $link->user()->is($user);
    }

    public function delete(User $user, Link $link): bool
    {
        return $link->user()->is($user);
    }

    public function retry(User $user, Link $link): bool
    {
        return $this->update($user, $link)
            && $link->preview_status === LinkPreviewStatus::Failed;
    }
}
