<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\XBookmark;
use App\ValueObjects\XBookmarkReviewQueue;

final readonly class BuildXBookmarkReviewQueue
{
    private const int DAILY_LIMIT = 10;

    public function handle(User $user): XBookmarkReviewQueue
    {
        $next = $user->xBookmarks()->dueForReview()->inReviewOrder()->with('tags')->first();

        if (! $next instanceof XBookmark) {
            return XBookmarkReviewQueue::caughtUp();
        }

        $due = $user->xBookmarks()->dueForReview()->count();
        $leftToday = max(0, self::DAILY_LIMIT - $user->xBookmarks()->reviewedToday()->count());

        if ($leftToday === 0) {
            return XBookmarkReviewQueue::doneForToday($due);
        }

        return XBookmarkReviewQueue::reviewing($next, $due, $leftToday);
    }
}
