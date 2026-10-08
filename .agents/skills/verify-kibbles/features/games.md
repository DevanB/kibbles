# Games

Authenticated, verified catalog at `/games` (full resource, including `show`). Inertia pages `games/index` and `games/show` inside `AppLayout` (sidebar shell). Create and edit are route-backed inertia-modals (`games/create` over index, `games/edit` over show). Sidebar nav includes **Games** next to Dashboard.

**Show is the hub.** Create and update redirect to `games.show`. Edit is title + status in a modal. Index title and disclosure go to show. Journal create/view/edit are route-backed inertia-modal pages.

## Sub-features

- Index lists only the current user's games as `{id, title, status, statusLabel, rawgId, imageUrl, description}` in a 3:4 artwork grid (`grid-cols-3 sm:grid-cols-4 xl:grid-cols-5`, about 75% of the prior tile width). Art uses `imageUrl`; unlinked games get a muted placeholder slot. The GameStatus badge sits on the bottom-right of the art; the title sits under the tile. Empty copy **No games yet** / **Add Game**. The header **Add Game** button is hidden when the list is empty — only the empty-state CTA is shown. Create is a route-backed inertia-modal (`games/create` over `games.index`). The whole tile (`data-test="game-open-{id}"`) goes to show.
- Create via `data-test="create-game-button"` → title (`data-test="game-title-input"`) can search RAWG (`data-test="game-catalog-results"` / `game-catalog-result-{id}`) or be typed as a custom title → `data-test="save-game-button"` → toast **Game created.** → **show** (`/games/{id}`) as **Backlog**. Create does not offer a status picker. Client sends `title` and optional `rawg_id` only.
- Show renders `{id, title, status, statusLabel, rawgId, imageUrl, description}` plus box art (`data-test="game-art"` when linked, otherwise `data-test="game-art-slot"` **No box art yet**) and description (`data-test="game-description"`) when present, and a full-width dated journal list under **Journal Entries**. **Create Entry** opens the create modal (header when entries exist; empty-state card when they do not); a row opens the read-only show modal (Edit opens the edit modal; Delete stays on show)
- Edit via `data-test="edit-game-button"` on show → edit modal (`/games/{id}/edit` over show) → same catalog picker + status (`data-test="game-status-select"`) → `data-test="save-game-button"` → toast **Game updated.** → **show**. Status options: Backlog, In Progress, Abandoned, Finished. `data-test="clear-catalog-link-button"` unlinks.
- Delete on show (Browser happy path) via `data-test="game-actions-button"` then `data-test="delete-game-button"` → confirm dialog → `data-test="confirm-delete-game-button"` → **Game deleted.** → index. Cancel (`cancel-delete-game-button`) leaves the game. After delete, breadcrumbs are only **Games** (the deleted title must not remain). Index has no edit/delete. Journal delete confirm is warning copy only (no date/body) with Cancel / **Delete Entry** (`confirm-delete-journal-entry-button-{id}`).
- Unique title per owner (case-insensitive); guests and unverified users are gated like dashboard

## How to get to it (user POV)

Log in as a verified user, then open `/games` or click **Games** in the sidebar. Click a title to open that game's show hub.

## Driving it with Pest Browser / Feature tests

Preconditions:

- `User::factory()->withoutTwoFactor()->create()` and `actingAs($user)`
- Playwright browsers installed for the Browser file

- **CRUD in browser →** `php artisan test --compact tests/Browser/GamesTest.php` → create **Catan** (lands on show), **Edit** → rename to **Ticket to Ride** (back to show), delete from show; toasts + `assertNoJavaScriptErrors()`
- **Catalog picker →** `php artisan test --compact tests/Browser/GameCatalogTest.php` → type **hades** in create, pick faked RAWG result, show has art + `description_raw`; typed custom title stays unlinked; edit linking pulls details. Feature `RawgGameSearchControllerTest` + catalog cases in `GameControllerTest` cover empty key, failed lookup, and non-integer `rawg_id`.
- **Journal on show →** `php artisan test --compact tests/Browser/JournalEntriesTest.php` → empty-state create modal, list date, show modal → edit modal, delete; empty copy **No journal entries yet** / **Journal Entries**
- **HTTP / Inertia / gates →** `php artisan test --compact tests/Feature/Controllers/GameControllerTest.php tests/Feature/Controllers/JournalEntryControllerTest.php tests/Feature/Controllers/PlaySessionControllerTest.php tests/Feature/Controllers/RawgGameSearchControllerTest.php` → guest → login, unverified → `verification.notice`, list isolation, create/update redirect to `games.show`, unique-title validation, show/edit 404 for missing ids, journal guest/ownership/cascade/body-limit. Journal store/update/destroy and newest-first list are Browser `JournalEntriesTest`. Play sessions: `tests/Browser/PlaySessionsTest.php` + [play-sessions.md](./play-sessions.md).
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-games`

## Gotchas

- `games.show` is the detail hub — do not expect create/update to land on edit
- Titles must be unique **per user**, not globally. A linked RAWG id is also unique per user (`You already have this game.`); unlinked games may share a null `rawg_id`.
- Index has no row delete. The Browser happy path deletes from **show** (`@game-actions-button` then `@delete-game-button` then `@confirm-delete-game-button`). Edit is title + status in a modal.
- Same `auth` + `verified` middleware group as dashboard
- Local `DatabaseSeeder` calls `DemoSeeder` (`devan@localhost.test` + five video-game titles; Hades, Stardew Valley, Hollow Knight, and Baldur's Gate 3 have hardcoded RAWG art; Celeste stays unlinked for the placeholder tile). Tests do not use that seeder
- Catalog search is `GET /rawg/games?query=` (auth + verified + throttle). No key configured → empty results; the modal still saves a typed title. `RAWG_API_KEY` must be set locally and on the NAS. No RAWG attribution in the UI.
