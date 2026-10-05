# Games

Authenticated, verified catalog at `/games` (full resource, including `show`). Inertia pages `games/index` and `games/show` inside `AppLayout`. Create and edit are route-backed inertia-modals (`games/create` over index, `games/edit` over show). Sidebar + header nav include **Games** next to Dashboard.

**Show is the hub.** Create and update redirect to `games.show`. Edit is title-only in a modal. Index title and disclosure go to show. Journal create/view/edit are route-backed inertia-modal pages.

## Sub-features

- Index lists only the current user's games as `{id, title}`; empty copy **No games yet** / **Add Game**. The header **Add Game** button is hidden when the list is empty — only the empty-state CTA is shown. Create is a route-backed inertia-modal (`games/create` over `games.index`).
- Create via `data-test="create-game-button"` → title → `data-test="save-game-button"` → toast **Game created.** → **show** (`/games/{id}`)
- Show renders `{id, title}` plus a box-art slot (`data-test="game-art-slot"`, **No box art yet**) and a full-width dated journal list under **Journal Entries**. **Create Entry** opens the create modal (header when entries exist; empty-state card when they do not); a row opens the read-only show modal (Edit opens the edit modal; Delete stays on show)
- Edit via `data-test="edit-game-button"` on show → edit modal (`/games/{id}/edit` over show) → title → `data-test="save-game-button"` → toast **Game updated.** → **show**
- Delete on show (Browser happy path) via `data-test="game-actions-button"` then `data-test="delete-game-button"` → confirm dialog → `data-test="confirm-delete-game-button"` → **Game deleted.** → index. Cancel (`cancel-delete-game-button`) leaves the game. After delete, breadcrumbs are only **Games** (the deleted title must not remain). Index has no edit/delete. Journal delete confirm is warning copy only (no date/body) with Cancel / **Delete Entry** (`confirm-delete-journal-entry-button-{id}`).
- Unique title per owner (case-insensitive); guests and unverified users are gated like dashboard

## How to get to it (user POV)

Log in as a verified user, then open `/games` or click **Games** in the sidebar. Click a title to open that game's show hub.

## Driving it with Pest Browser / Feature tests

Preconditions:

- `User::factory()->withoutTwoFactor()->create()` and `actingAs($user)`
- Playwright browsers installed for the Browser file

- **CRUD in browser →** `php artisan test --compact tests/Browser/GamesTest.php` → create **Catan** (lands on show), **Edit** → rename to **Ticket to Ride** (back to show), delete from show; toasts + `assertNoJavaScriptErrors()`
- **Journal on show →** `php artisan test --compact tests/Browser/JournalEntriesTest.php` → empty-state create modal, list date, show modal → edit modal, delete; `assertDontSee('Journal entries will live here.')` and no **Up next**
- **HTTP / Inertia / gates →** `php artisan test --compact tests/Feature/Controllers/GameControllerTest.php tests/Feature/Controllers/JournalEntryControllerTest.php` → guest → login, unverified → `verification.notice`, list isolation, create/update redirect to `games.show`, unique-title validation, show/edit 404 for missing ids, journal create/show/edit/store/update/destroy, newest-first list, cascade delete
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-games`

## Gotchas

- `games.show` is the detail hub — do not expect create/update to land on edit
- Titles must be unique **per user**, not globally
- Index has no row delete. The Browser happy path deletes from **show** (`@game-actions-button` then `@delete-game-button` then `@confirm-delete-game-button`). Edit is a title-only modal.
- Same `auth` + `verified` middleware group as dashboard
- Local `DatabaseSeeder` calls `DemoSeeder` (`devan@localhost.test` + five titles) for reviewing show. Tests do not use that seeder
