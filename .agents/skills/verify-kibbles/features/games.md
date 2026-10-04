# Games

Authenticated, verified catalog at `/games` (resource `games` except `show`). Inertia pages `games/index`, `games/create`, `games/edit` inside `AppLayout`. Sidebar + header nav include **Games** next to Dashboard.

## Sub-features

- Index lists only the current user's games as `{id, title}`; empty copy **No games yet** / **Add game**
- Create via `data-test="create-game-button"` → title → `data-test="save-game-button"` → toast **Game created.** → edit page
- Update title → **Game updated.**
- Delete on edit via `data-test="delete-game-button"` → **Game deleted.** → index
- Unique title per owner (case-insensitive); guests and unverified users are gated like dashboard

## How to get to it (user POV)

Log in as a verified user, then open `/games` or click **Games** in the sidebar.

## Driving it with Pest Browser / Feature tests

Preconditions:

- `User::factory()->withoutTwoFactor()->create()` and `actingAs($user)`
- Playwright browsers installed for the Browser file

- **CRUD in browser →** `php artisan test --compact tests/Browser/GamesTest.php` → create **Catan**, rename to **Ticket to Ride**, delete; toasts + `assertNoJavaScriptErrors()`
- **HTTP / Inertia / gates →** `php artisan test --compact tests/Feature/Controllers/GameControllerTest.php` → guest → login, unverified → `verification.notice`, list isolation, create/update/delete, unique-title validation
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-games`

## Gotchas

- No `games.show` route — `route('games.show')` throws
- Titles must be unique **per user**, not globally
- Index row delete uses `data-test="delete-game-button-{id}"`; the Browser happy path deletes from the **edit** page (`@delete-game-button`)
- Same `auth` + `verified` middleware group as dashboard
