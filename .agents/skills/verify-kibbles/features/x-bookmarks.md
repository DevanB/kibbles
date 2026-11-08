# X (bookmarks)

Authenticated, verified catalog at `/x` (route `x-bookmarks.index`, Inertia `x-bookmarks/index`) inside `AppLayout` (`AppSidebarLayout`). Sidebar nav, page `<Head>`, heading, and breadcrumb all say **X**. The mobile top bar is the sidebar trigger plus that **X** breadcrumb (`AppSidebarHeader`). Remove is a route-backed inertia-modal (`x-bookmarks/show` over index). Same `auth` + `verified` group as dashboard and games.

The index is **cursor-paginated** (20 newest-first on `(user_id, first_seen_at, id)`) and wrapped in `Inertia::scroll()` + `<InfiniteScroll data="bookmarks">`. Follow-up pages use `?cursor=…`. Sync walks X API pages; that is not the user-facing pager.

Sidebar **X** nests **All** (`/x`), **Review** (`/x-reviews`), **Untagged** (`/x?tag=untagged`), and one link per tag (`/x?tag={id}`), alphabetical. Filtering is one tag at a time and keeps cursor pages + lazy media. The heading stays **X**; the active filter name is a badge (`data-test="active-tag-name"`). Tags are kibbles-only and never sync to or from X. Each card has a **Tags** control (`data-test="tag-bookmark-button-{id}"`, `aria-label="Tags"`) before Open / Remove; quoted inner cards do not. The popover toggles existing tags and creates a new one by typing. **Manage tags** (`/tags`) renames and deletes tags without deleting bookmarks. See `tags.md`.

## Sub-features

- Disconnected empty state (`data-test="bookmarks-empty"`): **Connect X to see saved posts** / **Kibbles syncs your X bookmarks into a masonry grid here.** Header description **Connect X to sync your saved posts**. **Connect X** (`data-test="connect-x-button"`) is a top-level `<a>` to `x-connection.create` (not an Inertia visit).
- Connected empty state (same `bookmarks-empty`): **No bookmarks yet** / **Sync now to pull in the latest bookmarks from X.**
- Filtered empty states (same `bookmarks-empty`): untagged with no rows → **No untagged bookmarks**; another tag with no rows → **No bookmarks tagged {name}**. Both use subtitle **Try another tag or add one from a bookmark card.**
- Connect: GET `x-connection.create` → Socialite `x` with scopes `tweet.read`, `users.read`, `bookmark.read`, `bookmark.write`, `offline.access` → `https://x.com/i/oauth2/authorize`. Callback `x-connection.store` stores the connection, queues incremental sync, toasts **X connected. Bookmarks are syncing.**, and redirects to **`dashboard`** (not the X index). Socialite throw, empty tokens, or a user object with no OAuth tokens toast **Could not connect X. Please try again.** and still redirect to **`dashboard`**.
- Connected header: **Saved posts from @{username}** plus ** · Synced … ago** when `lastSyncedAt` is set. **Sync now** (`data-test="sync-bookmarks-button"`) POSTs `x-bookmark-syncs.store` with `preserveScroll` → toast **Bookmark sync started.** The ellipsis (`data-test="sync-bookmarks-actions-button"`) holds **Full sync** (`data-test="full-sync-bookmarks-button"`), which POSTs the same route with `full: true` → toast **Full bookmark sync started.** and also removes local posts X no longer returns. **Disconnect X** (`data-test="disconnect-x-button"`) DELETE `x-connection.destroy` wipes the connection and all local bookmarks, toasts **X disconnected.**, redirects to `x-bookmarks.index` (`/x`).
- Masonry (`data-test="bookmark-masonry"`, Masonic shortest-column, 1 / sm:2 / xl:3): cards `{author, posted/saved dates, text, media, quoted post}`. **Open on X** is an icon link (`data-test="open-on-x-{id}"`, `aria-label="Open on X"`). Quoted **Open on X** is `data-test="open-quoted-{id}"`. Long text expands via `data-test="show-full-post-{id}"` (**Show full post** / **Show less**, 240 chars). Remove (`data-test="remove-bookmark-button-{id}"`, `aria-label="Remove bookmark"`) opens the show modal.
- Infinite scroll: first visit loads one cursor page. Scrolling near the bottom fetches the next page (`buffer={400}`). Loading copy **Loading more…** (`data-test="bookmarks-load-more"`). There is no numbered pager and no dedicated sentinel `data-test`.
- Review queue at `/x-reviews` (route `x-bookmark-reviews.index`, Inertia `x-bookmark-reviews/index`, `data-test="bookmark-reviews-page"`). Heading **Review** / **Tag, keep, or remove one bookmark at a time.** Progress `data-test="bookmark-reviews-progress"` (`{remaining} left · {total} bookmarks`). One card at a time with Tags + Open on X; **Keep** (`data-test="keep-bookmark-button"`) and **Remove** (`data-test="remove-review-button"`) plus keyboard `t` / `k` / `x`. Empty: **All caught up** / **Every bookmark has been reviewed.** (`data-test="bookmark-reviews-empty"`).
- Remove modal: **Remove this bookmark?** / **This removes the post from your X bookmarks and from Kibbles. This cannot be undone.** Cancel / **Remove bookmark**. Success toast **Bookmark removed.**; X API refusal → **Could not remove the bookmark from X.** Remove keeps scroll and already-loaded pages (`preserveScroll`, `except: ['bookmarks']`, `replaceProp` on success).
- Scheduled sync (production): incremental every six hours, full weekly (`routes/console.php`). Incremental lists bookmark IDs with no expansions and hydrates only unknown IDs via `/tweets`. Full sync still lists every ID, deletes local posts X no longer returns, and hydrates the rest the same way. Those schedules stay as-is; **Full sync** is the on-demand path. `composer dev` includes `queue:listen` so a live **Sync now** / **Full sync** can run.

## How to get to it (user POV)

Log in as a verified user, then click **X** in the sidebar or open `/x`. Guests go to `/login`. Unverified users go to `verification.notice`.

## Driving it with Pest Browser / Feature tests

Preconditions:

- `User::factory()->withoutTwoFactor()->create()` and `actingAs($user)`
- OAuth / API tests call `configureX()` (and `fakeXDriver()` / `Http::fake()` as needed). Pest Browser does **not** need live `X_CLIENT_*`
- Playwright browsers installed for the Browser file
- Pagination cases factory **25** bookmarks so the first cursor page is incomplete

- **Empty + Connect + grid + remove (Browser) →** `php artisan test --compact tests/Browser/XBookmarksTest.php` → sidebar **X** → `/x`; empty **Connect X**; mocked authorize is a **top-level** navigation; factory grid shows media / quoted / **Open on X** / expand / remove modal (cancel keeps the row; confirm toasts **Bookmark removed.** and DELETEs `https://api.x.com/2/users/{id}/bookmarks/{post}`); **Sync now** keeps a local post X no longer returns, **Full sync** removes it
- **Tags (Browser) →** `php artisan test --compact tests/Browser/XBookmarkTagsTest.php` → card **Tags** toggles an existing tag and creates one inline; sidebar **Laravel** / **Untagged** filters; **Manage tags** renames and deletes without removing bookmarks
- **Cursor page + infinite scroll (Browser) →** same file `--filter="loads the next page of bookmarks on scroll"` → iPhone SE viewport; first page shows newest; `window.scrollTo(0, document.body.scrollHeight)` loads page two; remove on that page keeps a sibling; scroll-to-top still has the first page (Masonic virtualizes off-screen cards)
- **Review queue (Browser) →** `php artisan test --compact tests/Browser/XBookmarkReviewsTest.php` → `/x-reviews` shows one card; tag / keep / remove (keyboard `t` `k` `x`) walk the queue to **All caught up**
- **HTTP / Inertia / gates →** `php artisan test --compact tests/Feature/Controllers/XBookmarkControllerTest.php tests/Feature/Controllers/XBookmarkSyncControllerTest.php tests/Feature/Controllers/XBookmarkReviewControllerTest.php tests/Feature/Controllers/XBookmarkTagControllerTest.php tests/Feature/Controllers/TagControllerTest.php tests/Feature/Controllers/XConnectionControllerTest.php` → guest → login, unverified → `verification.notice`, list isolation, newest-first, cursor page + `next_cursor` then following page, tag filter + untagged + other-user tag 404, cross-user 403, review queue oldest-unreviewed-first + other-user review 403, sync forbidden without a connection, connect scopes + Inertia `409` location, callback → dashboard, disconnect → `x-bookmarks.index`
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-x-bookmarks`
- **Sync action/job (not a page) →** `php artisan test --compact tests/Feature/SyncXBookmarksTest.php`

## Gotchas

- Nav label, heading, and breadcrumb are **X**. The URL is `/x`, not `/bookmarks`.
- Connect success lands on **`/dashboard`**. Drive the grid from `/x` after factories or after the queued job finishes.
- Completing a real x.com grant needs `X_CLIENT_ID` and `X_CLIENT_SECRET` (`.env`; no `TWITTER_*`). Empty credentials still 302 to `https://x.com/i/oauth2/authorize?client_id=` — that grant cannot finish. Treat live OAuth + live `api.x.com` pull/delete as **verified-unreachable** unless those secrets and a real X app are present. Tests mock Socialite / `Http::fake`.
- Callback URL is `url('/x-connection/callback')`. `XConnectionController::xDriver()` overwrites `services.x.redirect` and Socialite `redirectUrl` on every connect and callback, so `X_REDIRECT_URI` does not change the URL the app sends to X.
- Disconnect deletes **all** local bookmarks for that user and redirects to `/x`.
- Index is cursor-paginated (20). Do not expect the full collection in one Inertia payload. Drive later pages with scroll (Browser) or `?cursor=` (Feature). Masonic virtualizes off-screen cards — assert by `data-test="bookmark-{id}"`, not by counting visible tiles.
- `prove-x-bookmarks` (and the other prove helpers) stash `public/hot` around Pest so Vite HMR does not break Inertia SSR under `Http::preventStrayRequests()`.
