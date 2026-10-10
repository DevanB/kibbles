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

        if ($full) {
            $this->reconcile($user, $connection);
        } else {
            $this->pullNew($user, $connection);
        }

        $this->markSynced($connection, $full);
    }

    private function pullNew(User $user, XConnection $connection): void
    {
        $known = XBookmark::query()->whereBelongsTo($user)->xPostIds();
        $paginationToken = null;

        do {
            $page = $this->x->bookmarks($connection, $paginationToken);

            foreach ($page->bookmarks as $bookmark) {
                if ($bookmark->xPostId === '') {
                    continue;
                }

                if ($known->contains($bookmark->xPostId)) {
                    return;
                }

                $this->upsert($user, $bookmark);
            }

            $paginationToken = $page->nextToken;
        } while ($paginationToken !== null);
    }

    private function reconcile(User $user, XConnection $connection): void
    {
        $known = XBookmark::query()->whereBelongsTo($user)->xPostIds();
        $seen = [];
        $paginationToken = null;

        do {
            $page = $this->x->bookmarkIds($connection, $paginationToken);

            foreach ($page->ids as $id) {
                $seen[] = $id;
            }

            $paginationToken = $page->nextToken;
        } while ($paginationToken !== null);

        $seen = array_values(array_unique($seen));
        $local = XBookmark::query()->whereBelongsTo($user);

        if ($seen === []) {
            $local->delete();
        } else {
            $local->whereNotIn('x_post_id', $seen)->delete();
        }

        $unknown = array_values(array_filter(
            $seen,
            fn (string $id): bool => $known->doesntContain($id),
        ));

        if ($unknown !== []) {
            $this->hydrate($user, $connection, $unknown);
        }
    }

    /**
     * @param  list<string>  $unknown
     */
    private function hydrate(User $user, XConnection $connection, array $unknown): void
    {
        $this->pullNew($user, $connection);

        $stored = XBookmark::query()->whereBelongsTo($user)->xPostIds();
        $missing = array_values(array_filter(
            $unknown,
            fn (string $id): bool => $stored->doesntContain($id),
        ));

        foreach (array_chunk($missing, 100) as $chunk) {
            foreach ($this->x->tweets($connection, $chunk) as $bookmark) {
                if ($bookmark->xPostId === '') {
                    continue;
                }

                $this->upsert($user, $bookmark);
            }
        }
    }

    private function upsert(User $user, FetchedXBookmark $fetched): void
    {
        $bookmark = XBookmark::query()->firstOrNew([
            'user_id' => $user->id,
            'x_post_id' => $fetched->xPostId,
        ]);

        if ($bookmark->exists) {
            return;
        }

        $bookmark->fill([
            'author_name' => $fetched->authorName,
            'author_username' => $fetched->authorUsername,
            'author_avatar_url' => $fetched->authorAvatarUrl,
            'text' => $fetched->text,
            'posted_at' => $fetched->postedAt,
            'first_seen_at' => Date::now(),
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
