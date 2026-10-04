# Games

Authenticated, verified catalog at `/games` (full resource, including `show`). Inertia pages `games/index`, `games/create`, `games/show`, `games/edit` inside `AppLayout`. Sidebar + header nav include **Games** next to Dashboard.

**Show is the hub.** Create and update redirect to `games.show`. Edit is title-only. Index title and disclosure go to show. Journal create/view/edit are route-backed inertia-modal pages.

## Sub-features

- Index lists only the current user's games as `{id, title}`; empty copy **No games yet** / **Add game**. The header **Add game** button is hidden when the list is empty — only the empty-state CTA is shown.
- Create via `data-test="create-game-button"` → title → `data-test="save-game-button"` → toast **Game created.** → **show** (`/games/{id}`)
- Show renders `{id, title}` plus a box-art slot (`data-test="game-art-slot"`, **No box art yet**) and a full-width dated journal list. **Create Entry** opens the create modal (header when entries exist; empty-state card when they do not); a row opens the read-only show modal (Edit opens the edit modal; Delete stays on show)
- Edit via `data-test="edit-game-button"` on show → title → `data-test="save-game-button"` → toast **Game updated.** → **show**
- Delete on show (Browser happy path) via `data-test="game-actions-button"` then `data-test="delete-game-button"` → **Game deleted.** → index. Index has no edit/delete.
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
- Index has no row delete. The Browser happy path deletes from **show** (`@game-actions-button` then `@delete-game-button`). Edit also has `@delete-game-button`
- Same `auth` + `verified` middleware group as dashboard
- Local `DatabaseSeeder` calls `DemoSeeder` (`devan@localhost.test` + five titles) for reviewing show. Tests do not use that seeder
