# Bookmarks (saved links)

Authenticated, verified catalog at `/bookmarks` (route `bookmarks.index`, Inertia `bookmarks/index`) inside `AppLayout` (`AppSidebarLayout`). Sidebar nav, page `<Head>`, heading, and breadcrumb all say **Bookmarks**. Add is a route-backed inertia-modal (`bookmarks/create` over index). Remove is a route-backed inertia-modal (`bookmarks/show` over index). Same `auth` + `verified` group as dashboard and games. This is **not** the X catalog (that lives at `/x`).

The index is **cursor-paginated** (20 newest-first on `(user_id, created_at, id)`) and wrapped in `Inertia::scroll()` + `<InfiniteScroll data="links">`. Follow-up pages use `?cursor=…`.

Sidebar **Bookmarks** nests **All** (`/bookmarks`), **Untagged** (`/bookmarks?tag=untagged`), and one link per tag (`/bookmarks?tag={id}`), alphabetical. Filtering is one tag at a time. The heading stays **Bookmarks**; the active filter name is a badge (`data-test="active-tag-name"`). Tags are kibbles-only and are the same `tags` rows used on X. Each card has a **Tags** control (`data-test="tag-link-button-{id}"`, `aria-label="Tags"`) before Open / Remove.

Private and LAN URLs **save** but never fetch a preview (`preview_status` is `ready` with the host as the title). Public URLs save as `pending` and queue `FetchLinkPreview`. Cards show **Fetching preview…** while pending. Failed previews show **Retry** (`data-test="retry-link-preview-{id}"`).

## Sub-features

- Empty state (`data-test="links-empty"`): **No bookmarks yet** / **Paste a URL to keep it here.** Filtered: untagged with no rows → **No untagged bookmarks**; another tag with no rows → **No bookmarks tagged {name}**. Both use subtitle **Try another tag or add one from a bookmark card.**
- **Add link** (`data-test="add-link-button"`) opens the create modal. URL field `data-test="link-url-input"`. **Save** (`data-test="save-link-button"`) POSTs `bookmarks.store` → toast **Saved to Kibbles.** Duplicate normalized URLs reuse the existing row (still that toast on the web form).
- Cards (`data-test="link-{id}"`): title (or URL), site name, description, hotlinked `og:image`, tags, **Open** (`data-test="open-link-{id}"`, `aria-label="Open link"`), **Remove** (`data-test="remove-link-button-{id}"`, `aria-label="Remove"`). Pending copy `data-test="link-pending-{id}"`.
- Remove modal: **Remove this bookmark?** / **This removes the saved link from Kibbles. This cannot be undone.** Cancel / **Remove**. Success toast **Removed.**
- Retry: POSTs `bookmarks.previews.store` → toast **Retrying preview.** Only failed previews may retry.
- URL cleanup: strip `utm_*` and click ids, drop fragments, lowercase the host. `SaveLink` is the only writer; unique `(user_id, url)` violations return the existing row.
- Preview fetch: SSRF-locked (`PublicIpResolver` + DNS pinning, public IP only, at most 3 redirects, 1 MiB, 3s connect / 5s total). Private / loopback / link-local (including via redirect or DNS) never receive a request.

## How to get to it (user POV)

Log in as a verified user, then click **Bookmarks** in the sidebar or open `/bookmarks`. Guests go to `/login`. Unverified users go to `verification.notice`.

## Driving it with Pest Browser / Feature tests

Preconditions:

- `User::factory()->withoutTwoFactor()->create()` and `actingAs($user)`
- Playwright browsers installed for the Browser file
- Pagination cases factory **25** links so the first cursor page is incomplete
- Preview fetches in tests use `Http::fake()` (and public literals such as `http://1.1.1.1/…` so DNS is not required)

- **Empty + add + pending/retry/remove (Browser) →** `php artisan test --compact tests/Browser/LinksTest.php` → sidebar **Bookmarks** → `/bookmarks`; empty CTA; add-link modal toasts **Saved to Kibbles.**; pending copy; **Retry** on failed; remove cancel then confirm
- **Tags (Browser) →** same file, tagging test → card **Tags** toggles an existing tag and creates one inline; sidebar **Laravel** / **Untagged** filters
- **HTTP / Inertia / gates →** `php artisan test --compact tests/Feature/Controllers/LinkControllerTest.php tests/Feature/Controllers/LinkTagControllerTest.php tests/Feature/Controllers/LinkPreviewControllerTest.php` → guest → login, unverified → `verification.notice`, list isolation, newest-first, cursor page + `next_cursor`, tag filter + untagged + other-user tag 404, cross-user 403, invalid URL, LAN save without a job, duplicate normalized URL, retry only when failed
- **SSRF + cleanup →** `php artisan test --compact tests/Unit/ValueObjects/LinkUrlTest.php tests/Unit/Services/PublicIpResolverTest.php tests/Unit/Services/LinkPreviewClientTest.php tests/Unit/Actions/SaveLinkTest.php tests/Feature/FetchLinkPreviewTest.php`
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-bookmarks`

## Gotchas

- Nav label, heading, and breadcrumb are **Bookmarks**. The URL is `/bookmarks`. X is a separate nav item at `/x`.
- Sidebar order is Dashboard, Games, Bookmarks, X.
- Private / LAN / `.local` hosts save and skip `FetchLinkPreview`. A public hostname that later resolves to a private IP fails the fetch (card **Retry**), it does not unroll the save.
- Index is cursor-paginated (20). Do not expect the full collection in one Inertia payload. Drive later pages with `?cursor=` (Feature).
- `prove-bookmarks` (and the other prove helpers) stash `public/hot` around Pest so Vite HMR does not break Inertia SSR under `Http::preventStrayRequests()`.
