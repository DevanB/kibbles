<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SyncXBookmarks as SyncXBookmarksAction;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\UniqueFor;

#[UniqueFor(3600)]
final class SyncXBookmarks implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $userId,
        public bool $full = false,
    ) {}

    public function uniqueId(): string
    {
        return $this->userId.':'.($this->full ? 'full' : 'incremental');
    }

    public function handle(SyncXBookmarksAction $sync): void
    {
        $user = User::query()->find($this->userId);

        if (! $user instanceof User) {
            return;
        }

        $sync->handle($user, $this->full);
    }
}
