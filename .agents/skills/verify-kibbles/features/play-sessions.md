# Play Sessions

Authenticated play-time log on `games.show`, nested under `games.play-sessions`. Inertia show hub plus route-backed inertia-modals for stop / add / edit. One open session per user.

## Sub-features

- Show receives `playSessions`, `openPlaySession`, `totalPlayedMinutes`, and a server-formatted `totalPlayedLabel`. Total is `floor(sum of closed-session seconds / 60)`. Open sessions are excluded. No closed sessions → **No time logged**. Otherwise `14m` / `2h 14m` (`data-test="play-time-total"`).
- Header **Start** is a plain Form POST (`data-test="start-play-session-button"`). **Stop** is a ModalLink (`data-test="stop-play-session-button"`). Start is hidden when any session is open. If the open session is on another game, show **You're playing {A}.** with `data-test="open-session-game-link"` and no Start button.
- Play Sessions and Journal Entries share a shadcn Tabs block below Time played / Start / Stop / the other-game banner. Default tab is Play Sessions. Active tab is `?tab=journal` or omitted (`sessions`). Triggers: `data-test="game-tab-sessions"` / `data-test="game-tab-journal"`. **Add Session** / **Create Entry** stay as that tab's header action and hide when the list is empty. Empty copy **No play sessions yet** / **Log a session after you play.** with Start + Add Session.
- An open row reads `{date}, {time} – now · Open` with Stop. A closed same-day row reads `{date}, {start} – {end} · {duration}` (meridiem once when both times share AM/PM; year omitted in the current year). A session that crosses midnight shows both dates. An optional Journal chip (`data-test="play-session-journal-{id}"` → existing journal show modal), Edit, and a Delete dropdown sit on closed rows.
- Stop Session modal: optional **What happened** textarea (`data-test="stop-session-body"`, max 10000). Saving with text creates a linked journal entry and redirects to `?tab=journal`. Button **Save Session**. Journal create / update / destroy also redirect to the journal tab. Journal modal `baseRoute` includes `tab=journal` so closing a modal does not reset the tab.
- Add / Edit Session: native `datetime-local` + Label + hidden `timezone`. Values are browser-local; the server stores UTC. Buttons **Save Session** / **Save Changes**.
- Delete uses DeleteConfirmationDialog: **Delete Session?** / **This will permanently delete this play session. Linked journal entries are kept.** / **Delete Session** / **Cancel**.
- Start moves Backlog and Abandoned to In Progress. Finished stays Finished. Adding a past session never changes status. Update cannot reopen a session. Stopping an already-closed session is a validation error.
- One open session per user: app-level rule plus a partial unique index on `(user_id) WHERE ended_at IS NULL`. Race maps to **Stop your session on {title} first.** Production DB is SQLite (not MySQL generated `is_open`).
- Index receives `openPlaySessionGameId`. The open game's tile gets a top-left **Playing** badge (`data-test="game-playing-{id}"`).
- Toasts: **Session started.** / **Session saved.** / **Session updated.** / **Session deleted.**

## How to get to it (user POV)

Log in, open a game, use Start / Stop / Add Session on the show hub.

## Driving it with Pest Browser / Feature tests

- **Browser happy path →** `php artisan test --compact tests/Browser/PlaySessionsTest.php`
- **HTTP / gates / validation / unique race / cascade →** `php artisan test --compact tests/Feature/Controllers/PlaySessionControllerTest.php`

## Gotchas

- `datetime-local` is local wall time. Send `timezone` (IANA) so the boundary can convert to UTC and round-trip exactly.
- Linked journals are kept when a session is deleted (`nullOnDelete`).
- DemoSeeder play sessions are created only when `$user->playSessions()->doesntExist()`.
