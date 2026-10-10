<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\XBookmark;
use App\Models\XConnection;
use App\Services\FetchedXBookmark;
use App\Services\XClient;
use Illuminate\Support\Facades\Date;

final readonly class SyncXBookmarks
{
    public function __construct(private XClient $x) {}

    public function handle(User $user, bool $full = false): void
    {
        $connection = $user->xConnection;

        if (! $connection instanceof XConnection) {
            return;
        }

        $known = XBookmark::query()->whereBelongsTo($user)->xPostIds();
        $seen = [];
        $paginationToken = null;

        do {
            $page = $this->x->bookmarks($connection, $paginationToken);

            foreach ($page->bookmarks as $bookmark) {
                if ($bookmark->xPostId === '') {
                    continue;
                }

                if (! $full && $known->contains($bookmark->xPostId)) {
                    $this->markSynced($connection, false);

                    return;
                }

                $this->upsert($user, $bookmark);
                $seen[] = $bookmark->xPostId;
            }

            $paginationToken = $page->nextToken;
        } while ($paginationToken !== null);

        if ($full) {
            XBookmark::query()
                ->whereBelongsTo($user)
                ->whereNotIn('x_post_id', $seen)
                ->delete();
        }

        $this->markSynced($connection, $full);
    }

    private function upsert(User $user, FetchedXBookmark $fetched): void
    {
        $bookmark = XBookmark::query()->firstOrNew([
            'user_id' => $user->id,
            'x_post_id' => $fetched->xPostId,
        ]);

        $bookmark->fill([
            'author_name' => $fetched->authorName,
            'author_username' => $fetched->authorUsername,
            'author_avatar_url' => $fetched->authorAvatarUrl,
            'text' => $fetched->text,
            'posted_at' => $fetched->postedAt,
            'first_seen_at' => $bookmark->exists ? $bookmark->first_seen_at : Date::now(),
            'media' => $fetched->media,
            'quoted_post' => $fetched->quotedPost,
        ])->save();
    }

    private function markSynced(XConnection $connection, bool $full): void
    {
        $connection->refresh();

        $attributes = [
            'last_synced_at' => Date::now(),
        ];

        if ($full) {
            $attributes['last_full_synced_at'] = Date::now();
        }

        $connection->forceFill($attributes)->save();
    }
}
