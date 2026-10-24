# X (bookmarks)

Authenticated, verified catalog at `/bookmarks` (route `bookmarks.index`, Inertia `bookmarks/index`) inside `AppLayout` (`AppSidebarLayout`). Sidebar nav labels the item **X**; the page `<Head>`, heading, and breadcrumb say **Bookmarks**. The mobile top bar is the sidebar trigger plus that **Bookmarks** breadcrumb (`AppSidebarHeader`) — it does not repeat an **X** label. Remove is a route-backed inertia-modal (`bookmarks/show` over index). Same `auth` + `verified` group as dashboard and games.

The index is **cursor-paginated** (20 newest-first on `(user_id, first_seen_at, id)`) and wrapped in `Inertia::scroll()` + `<InfiniteScroll data="bookmarks">`. Follow-up pages use `?cursor=…`. Sync walks X API pages; that is not the user-facing pager.

Sidebar **X** nests **All** (`/bookmarks`), **Untagged** (`/bookmarks?tag=untagged`), and one link per tag (`/bookmarks?tag={id}`), alphabetical. Filtering is one tag at a time and keeps cursor pages + lazy media. The heading stays **Bookmarks**; the active filter name is a badge (`data-test="active-tag-name"`). Tags are kibbles-only and never sync to or from X. Each card has a **Tags** control (`data-test="tag-bookmark-button-{id}"`, `aria-label="Tags"`) before Open / Remove; quoted inner cards do not. The popover toggles existing tags and creates a new one by typing. **Manage tags** (`/tags`) renames and deletes tags without deleting bookmarks. See `tags.md`.

## Sub-features

- Disconnected empty state (`data-test="bookmarks-empty"`): **Connect X to see saved posts** / **Kibbles syncs your X bookmarks into a masonry grid here.** Header description **Connect X to sync your saved posts**. **Connect X** (`data-test="connect-x-button"`) is a top-level `<a>` to `x-connection.create` (not an Inertia visit).
- Connected empty state (same `bookmarks-empty`): **No bookmarks yet** / **Sync now to pull in the latest bookmarks from X.**
- Connect: GET `x-connection.create` → Socialite `x` with scopes `tweet.read`, `users.read`, `bookmark.read`, `bookmark.write`, `offline.access` → `https://x.com/i/oauth2/authorize`. Callback `x-connection.store` stores the connection, queues incremental sync, toasts **X connected. Bookmarks are syncing.**, and redirects to **`dashboard`** (not the bookmarks index).
- Connected header: **Saved posts from @{username}** plus ** · Synced … ago** when `lastSyncedAt` is set. **Sync now** (`data-test="sync-bookmarks-button"`) POSTs `bookmark-syncs.store` with `preserveScroll` → toast **Bookmark sync started.** **Disconnect X** (`data-test="disconnect-x-button"`) DELETE `x-connection.destroy` wipes the connection and all local bookmarks, toasts **X disconnected.**, redirects to `/bookmarks`.
- Masonry (`data-test="bookmark-masonry"`, Masonic shortest-column, 1 / sm:2 / xl:3): cards `{author, posted/saved dates, text, media, quoted post}`. **Open on X** is an icon link (`data-test="open-on-x-{id}"`, `aria-label="Open on X"`). Quoted **Open on X** is `data-test="open-quoted-{id}"`. Long text expands via `data-test="show-full-post-{id}"` (**Show full post** / **Show less**, 240 chars). Remove (`data-test="remove-bookmark-button-{id}"`, `aria-label="Remove bookmark"`) opens the show modal.
- Infinite scroll: first visit loads one cursor page. Scrolling near the bottom fetches the next page (`buffer={400}`). Loading copy **Loading more…** (`data-test="bookmarks-load-more"`). There is no numbered pager and no dedicated sentinel `data-test`.
- Remove modal: **Remove this bookmark?** / **This removes the post from your X bookmarks and from Kibbles. This cannot be undone.** Cancel / **Remove bookmark**. Success toast **Bookmark removed.**; X API refusal → **Could not remove the bookmark from X.** Remove keeps scroll and already-loaded pages (`preserveScroll`, `except: ['bookmarks']`, `replaceProp` on success).
- Scheduled sync (production): incremental every six hours, full weekly (`routes/console.php`). `composer dev` includes `queue:listen` so a live **Sync now** can run.

## How to get to it (user POV)

Log in as a verified user, then click **X** in the sidebar or open `/bookmarks`. Guests go to `/login`. Unverified users go to `verification.notice`.

## Driving it with Pest Browser / Feature tests

Preconditions:

- `User::factory()->withoutTwoFactor()->create()` and `actingAs($user)`
- OAuth / API tests call `configureX()` (and `fakeXDriver()` / `Http::fake()` as needed). Pest Browser does **not** need live `X_CLIENT_*`
- Playwright browsers installed for the Browser file
- Pagination cases factory **25** bookmarks so the first cursor page is incomplete

- **Empty + Connect + grid + remove (Browser) →** `php artisan test --compact tests/Browser/BookmarksTest.php` → sidebar **X** → `/bookmarks`; empty **Connect X**; mocked authorize is a **top-level** navigation; factory grid shows media / quoted / **Open on X** / expand / remove modal (cancel keeps the row; confirm toasts **Bookmark removed.** and DELETEs `https://api.x.com/2/users/{id}/bookmarks/{post}`)
- **Tags (Browser) →** `php artisan test --compact tests/Browser/BookmarkTagsTest.php` → card **Tags** toggles an existing tag and creates one inline; sidebar **Laravel** / **Untagged** filters; **Manage tags** renames and deletes without removing bookmarks
- **Cursor page + infinite scroll (Browser) →** same file `--filter="loads the next page of bookmarks on scroll"` → iPhone SE viewport; first page shows newest; `window.scrollTo(0, document.body.scrollHeight)` loads page two; remove on that page keeps a sibling; scroll-to-top still has the first page (Masonic virtualizes off-screen cards)
- **HTTP / Inertia / gates →** `php artisan test --compact tests/Feature/Controllers/BookmarkControllerTest.php tests/Feature/Controllers/BookmarkSyncControllerTest.php tests/Feature/Controllers/BookmarkTagControllerTest.php tests/Feature/Controllers/TagControllerTest.php tests/Feature/Controllers/XConnectionControllerTest.php` → guest → login, unverified → `verification.notice`, list isolation, newest-first, cursor page + `next_cursor` then following page, tag filter + untagged + other-user tag 404, cross-user 403, sync forbidden without a connection, connect scopes + Inertia `409` location, callback → dashboard, disconnect → `/bookmarks`
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-bookmarks`
- **Sync action/job (not a page) →** `php artisan test --compact tests/Feature/SyncXBookmarksTest.php`

## Gotchas

- Nav label is **X**; page title is **Bookmarks**. Do not look for a sidebar item named Bookmarks.
- Connect success lands on **`/dashboard`**. Drive the grid from `/bookmarks` after factories or after the queued job finishes.
- Completing a real x.com grant needs `X_CLIENT_ID` and `X_CLIENT_SECRET` (`.env`; no `TWITTER_*`). Empty credentials still 302 to `https://x.com/i/oauth2/authorize?client_id=` — that grant cannot finish. Treat live OAuth + live `api.x.com` pull/delete as **verified-unreachable** unless those secrets and a real X app are present. Tests mock Socialite / `Http::fake`.
- Callback URL is `{APP_URL}/x-connection/callback` (`X_REDIRECT_URI` optional).
- Disconnect deletes **all** local bookmarks for that user.
- Index is cursor-paginated (20). Do not expect the full collection in one Inertia payload. Drive later pages with scroll (Browser) or `?cursor=` (Feature). Masonic virtualizes off-screen cards — assert by `data-test="bookmark-{id}"`, not by counting visible tiles.
- `prove-bookmarks` (and the other prove helpers) stash `public/hot` around Pest so Vite HMR does not break Inertia SSR under `Http::preventStrayRequests()`.
