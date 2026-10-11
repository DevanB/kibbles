# X (bookmarks)

Authenticated, verified catalog at `/bookmarks` (route `bookmarks.index`, Inertia `bookmarks/index`) inside `AppLayout`. Sidebar and mobile header label the item **X**; the page `<Head>`, heading, and breadcrumb say **Bookmarks**. Remove is a route-backed inertia-modal (`bookmarks/show` over index). Same `auth` + `verified` group as dashboard and games.

There is **no** HTTP or UI pagination on master — the index loads the owner’s full local collection (`newestFirst()->get()`). Sync walks X API pages; that is not a user-facing pager.

## Sub-features

- Disconnected empty state (`data-test="bookmarks-empty"`): **Connect X to see saved posts** / **Kibbles syncs your X bookmarks into a masonry grid here.** Header description **Connect X to sync your saved posts**. **Connect X** (`data-test="connect-x-button"`) is a top-level `<a>` to `x-connection.create` (not an Inertia visit).
- Connect: GET `x-connection.create` → Socialite `x` with scopes `tweet.read`, `users.read`, `bookmark.read`, `bookmark.write`, `offline.access` → `https://x.com/i/oauth2/authorize`. Callback `x-connection.store` stores the connection, queues incremental sync, toasts **X connected. Bookmarks are syncing.**, and redirects to **`dashboard`** (not the bookmarks index).
- Connected header: **Saved posts from @{username}** plus relative **Synced … ago** when `lastSyncedAt` is set. **Sync now** (`data-test="sync-bookmarks-button"`) POSTs `bookmark-syncs.store` → toast **Bookmark sync started.** **Disconnect X** (`data-test="disconnect-x-button"`) DELETE `x-connection.destroy` wipes the connection and all local bookmarks, toasts **X disconnected.**, redirects to `/bookmarks`.
- Masonry grid (`columns-1 sm:columns-2 xl:columns-3`): cards `{author, posted/saved dates, text, media, quoted post}`. **Open on X** is an icon link (`data-test="open-on-x-{id}"`, `aria-label="Open on X"`). Long text expands via `data-test="show-full-post-{id}"` (**Show full post** / **Show less**, 240 chars). Remove (`data-test="remove-bookmark-button-{id}"`, `aria-label="Remove bookmark"`) opens the show modal.
- Remove modal: **Remove this bookmark?** / **This removes the post from your X bookmarks and from Kibbles. This cannot be undone.** Cancel / **Remove bookmark**. Success toast **Bookmark removed.**; X API refusal → **Could not remove the bookmark from X.**
- Scheduled sync (production): incremental every six hours, full weekly (`routes/console.php`). `composer dev` includes `queue:listen` so a live **Sync now** can run.

## How to get to it (user POV)

Log in as a verified user, then click **X** in the sidebar or open `/bookmarks`. Guests go to `/login`. Unverified users go to `verification.notice`.

## Driving it with Pest Browser / Feature tests

Preconditions:

- `User::factory()->withoutTwoFactor()->create()` and `actingAs($user)`
- OAuth / API tests call `configureX()` (and `fakeXDriver()` / `Http::fake()` as needed). Pest Browser does **not** need live `X_CLIENT_*`
- Playwright browsers installed for the Browser file

- **Empty + Connect + grid + remove (Browser) →** `php artisan test --compact tests/Browser/BookmarksTest.php` → sidebar **X** → `/bookmarks`; empty **Connect X**; mocked authorize is a **top-level** navigation; factory grid shows media / quoted / **Open on X** / expand / remove modal (cancel keeps the row; confirm toasts **Bookmark removed.** and DELETEs `https://api.x.com/2/users/{id}/bookmarks/{post}`)
- **HTTP / Inertia / gates →** `php artisan test --compact tests/Feature/Controllers/BookmarkControllerTest.php tests/Feature/Controllers/BookmarkSyncControllerTest.php tests/Feature/Controllers/XConnectionControllerTest.php` → guest → login, unverified → `verification.notice`, list isolation, newest-first, cross-user 403, sync forbidden without a connection, connect scopes + Inertia `409` location, callback → dashboard, disconnect → `/bookmarks`
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-bookmarks`
- **Sync action/job (not a page) →** `php artisan test --compact tests/Feature/SyncXBookmarksTest.php`

## Gotchas

- Nav label is **X**; page title is **Bookmarks**. Do not look for a sidebar item named Bookmarks.
- Connect success lands on **`/dashboard`**. Drive the grid from `/bookmarks` after factories or after the queued job finishes.
- Completing a real x.com grant needs `X_CLIENT_ID` and `X_CLIENT_SECRET` (`.env`; no `TWITTER_*`). Empty credentials still 302 to `https://x.com/i/oauth2/authorize?client_id=` — that grant cannot finish. Treat live OAuth + live `api.x.com` pull/delete as **verified-unreachable** unless those secrets and a real X app are present. Tests mock Socialite / `Http::fake`.
- Callback URL is `{APP_URL}/x-connection/callback` (`X_REDIRECT_URI` optional).
- Disconnect deletes **all** local bookmarks for that user.
- Index is not paginated on master. Do not document draft UI pagination.
- `prove-bookmarks` (and the other prove helpers) stash `public/hot` around Pest Browser so Vite HMR does not break Inertia SSR under `Http::preventStrayRequests()`.
