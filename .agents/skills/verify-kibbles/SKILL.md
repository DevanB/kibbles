---
name: verify-kibbles
description: "REQUIRED before claiming UI, auth, or feature work done. Verify kibbles locally via composer dev (php artisan serve + queue + pail + Vite) at http://localhost:8000, using Pest Browser + Feature tests. Use when checking the app still boots, driving home/login/register/dashboard/games/settings, or collecting proof artifacts after UI or auth changes. Covers doctor (curl /up + guest / 302 + /login 200 + Vite), drive (php artisan test Browser/Feature), evidence under artifacts/verify-kibbles/, and PID-safe cleanup. Herd is optional, not the default."
---

# Verify kibbles

Project-local verification for **kibbles**: Laravel 13 + Inertia React + Fortify/passkeys + Pennant registration + user-owned Games. Preferred local stack is **`composer dev`** at `http://localhost:8000` (`php artisan serve` + queue + pail + Vite). Front-end via vite-plus (`vp`) / bun. Prefer existing **Pest Browser** and **Feature** tests over a new Playwright CLI harness.

## Required before claiming done

This skill is a **required gate**, not optional flavor. If you changed UI, auth, routing, Inertia pages, or user-visible behavior, run this skill (or its prove helpers / the Pest Feature + Browser files below) before saying the work is done. Live path is `.agents/skills/verify-kibbles` (`.cursor/skills` is a symlink).

CI (`.github/workflows/tests.yml`) runs `composer test` on every PR and fails on red. That is the merge gate. It does not replace a local verify run.

How to invoke:

```bash
# from the project root
.agents/skills/verify-kibbles/bin/doctor
.agents/skills/verify-kibbles/bin/prove-home
.agents/skills/verify-kibbles/bin/prove-dashboard
.agents/skills/verify-kibbles/bin/prove-games
# or the mapped Pest files this skill names, e.g.:
php artisan test --compact tests/Feature/Controllers/DashboardTest.php tests/Browser/DashboardTest.php
```

Keep `APP_URL=http://localhost:8000` so passkeys match this host. Do not rewrite `.env`.

There is **no welcome page**. `/` (route `home`) redirects to `/dashboard`. Guests then land on `/login`.

**Live path:** `.agents/skills/verify-kibbles` only. `.cursor/skills` and `.claude/skills` are repo-level symlinks to `.agents/skills` (not separate copies). Do not edit or delete a second tree at those paths — that is this tree. Helpers and feature-map paths below always use `.agents/skills/verify-kibbles`.

## Launch

### Preferred: `composer dev`

From the project root, in a terminal you leave running (foreground TUI — this skill must not start or background it):

```bash
# from the project root
composer dev
```

That runs `php artisan dev`: `php artisan serve` (default `http://localhost:8000`), `queue:listen`, `pail`, and `bun run dev` (Vite).

Ready when both are true:

1. `http://localhost:8000/up` returns **200**
2. Vite is up: `public/hot` exists (dev server), or `public/build/manifest.json` exists if you built assets instead

One-time if tools are missing:

```bash
# from the project root
bun install
bunx playwright install   # once, if Pest Browser complains browsers are missing/outdated
```

`bun run build` is optional while `composer dev` is serving Vite. Use a production build when you are not running the Vite dev server (isolated serve below).

Optional alternate: Laravel Herd at `http://kibbles.test` if the folder is parked — pass that URL explicitly; it is not the default.

### Isolated: disposable `artisan serve` + sqlite

When you must not touch the project DB or port 8000 (and you are not using the `composer dev` process):

```bash
# from the project root
bun run build   # isolated serve has no Vite HMR unless you start it yourself
RUN_ID=$(date +%Y%m%d-%H%M%S)
PORT=8$(printf '%03d' $((RANDOM % 1000)))   # e.g. 8123–8999
DB_FILE="/tmp/kibbles-verify-${RUN_ID}.sqlite"
touch "$DB_FILE"
# Record the PID you start — cleanup kills only this PID
# Bind 127.0.0.1, but APP_URL and doctor must use hostname localhost.
# Browse host 127.0.0.1 fails check-passkey-host (WebAuthn RP ≠ localhost).
APP_URL="http://localhost:${PORT}" \
  DB_CONNECTION=sqlite DB_DATABASE="$DB_FILE" \
  php artisan serve --host=127.0.0.1 --port="$PORT" &
echo $! > "/tmp/kibbles-verify-${RUN_ID}.pid"
echo "$PORT" > "/tmp/kibbles-verify-${RUN_ID}.port"
echo "$DB_FILE" > "/tmp/kibbles-verify-${RUN_ID}.db"
APP_URL="http://localhost:${PORT}" DB_CONNECTION=sqlite DB_DATABASE="$DB_FILE" \
  php artisan migrate --force --no-interaction
```

Match `APP_URL` host to **localhost** for that process only (inline env, not `.env`). Doctor as `http://localhost:${PORT}`. Do **not** pass `http://127.0.0.1:${PORT}` to doctor. Do **not** rewrite committed `.env`. Leave the project `APP_URL` at `http://localhost:8000`.

### Passkey / APP_URL mismatch (known)

`.env` should stay `APP_URL=http://localhost:8000`. Fortify passkeys set:

- `relying_party_id` ← host of `config('app.url')`
- `allowed_origins` ← `[config('app.url')]`

With `composer dev` and a browser on `http://localhost:8000`, origin and RP ID match — **no mismatch**.

**Passkey register/assert can fail only if you browse a different host** (e.g. Herd `http://kibbles.test`) while `APP_URL` is `http://localhost:8000`. Password login and most UI still work on either host once assets load. Prefer Pest Browser (it boots its own server with matching `APP_URL`) for passkey flows. Never commit `APP_URL` or secret changes to chase another host.

Doctor (and `bin/check-passkey-host`) compares the browse URL host to `config('app.url')` and `config('fortify.passkeys.relying_party_id')`. Matching hosts print `passkey:host_ok`. A mismatch **fails loudly** — do not treat that browse URL as a working passkey path.

## Doctor

Run from the project root (or via the helper). Default base is `http://localhost:8000`:

```bash
# from the project root
.agents/skills/verify-kibbles/bin/doctor
# equivalent: .agents/skills/verify-kibbles/bin/doctor http://localhost:8000
# isolated serve: .agents/skills/verify-kibbles/bin/doctor http://localhost:$PORT
```

Manual equivalent:

```bash
BASE="${1:-http://localhost:8000}"
curl -sS -o /dev/null -w "up:%{http_code}\n" "$BASE/up"          # expect 200
curl -sS -D - -o /dev/null "$BASE/"                              # expect 302, Location contains /dashboard
curl -sS -o /dev/null -w "login:%{http_code}\n" "$BASE/login"     # expect 200 once Vite is up
if [[ -f public/hot ]]; then echo "vite:hot"; elif [[ -f public/build/manifest.json ]]; then echo "vite:manifest"; else echo "vite:MISSING"; fi
php artisan about --only=environment,drivers 2>/dev/null | head -40
echo "url_under_test=$BASE"
.agents/skills/verify-kibbles/bin/check-passkey-host "$BASE"   # passkey:host_ok, or FAIL on browse ≠ APP_URL / RP
```

Fail doctor if `/up` ≠ 200, guest `/` ≠ 302, `/` Location does not contain `/dashboard`, `/login` ≠ 200, Vite is down (`public/hot` missing **and** `public/build/manifest.json` missing), or the passkey host check fails. Login 500 almost always means Vite is not running and there is no production build. Do **not** expect `/` to be 200 — it is a redirect. A passkey host mismatch means WebAuthn will fail on that browse URL. Isolated serve: doctor `http://localhost:$PORT`, never `http://127.0.0.1:$PORT`.

## Drive

Prefer Pest over inventing selectors from scratch.

### Browser suite (Playwright under Pest)

```bash
php artisan test --compact tests/Browser/HomeTest.php
php artisan test --compact tests/Browser/DashboardTest.php
php artisan test --compact tests/Browser/GamesTest.php
php artisan test --compact tests/Browser/JournalEntriesTest.php
php artisan test --compact tests/Browser/PlaySessionsTest.php
php artisan test --compact tests/Browser/LayoutTest.php
php artisan test --compact tests/Browser/SessionTest.php
php artisan test --compact tests/Browser/RegistrationTest.php
php artisan test --compact tests/Browser/EmailVerificationTest.php
php artisan test --compact --testsuite=Browser
```

Pest Browser starts its own app server; it does not require `composer dev` or Herd. After `bun install`, if Browser tests fail with Playwright outdated / just-installed errors, run `bunx playwright install` (or `bunx playwright install chromium`) so browser binaries match the pinned `playwright` package. Browsers land under `~/Library/Caches/ms-playwright` (macOS) or `~/.cache/ms-playwright` (Linux).

**`@name` → `data-test`:** Pest `click('@login-button')` targets `data-test="login-button"`. Real attrs in this app:

| data-test | Where |
|-----------|--------|
| `login-button` | `/login` submit |
| `register-user-button` | `/register` submit |
| `update-profile-button` | `/settings/profile` |
| `update-password-button` | `/settings/password` |
| `reset-password-button` | reset-password form |
| `email-password-reset-link-button` | forgot-password |
| `confirm-password-button` | password confirm |
| `delete-user-button` / `confirm-delete-user-button` | profile delete dialog |
| `logout-button` | user menu |
| `sidebar-menu-button` | nav user trigger |
| `create-game-button` | `/games` add (header when the list has games; empty-state CTA when it does not) |
| `save-game-button` | games create/edit save |
| `game-title-input` | games create/edit title + catalog search |
| `game-catalog-results` | games create/edit RAWG result list |
| `game-catalog-result-{id}` | games create/edit one RAWG row |
| `clear-catalog-link-button` | games create/edit unlink |
| `game-rawg-id` | games create/edit hidden catalog id |
| `game-art` | games show hotlinked box art |
| `game-description` | games show catalog description |
| `edit-game-button` | games show → edit |
| `game-actions-button` | games show split-button chevron (Delete lives in the menu) |
| `delete-game-button` | games show menu / edit delete |
| `confirm-delete-game-button` / `cancel-delete-game-button` | games show delete dialog |
| `game-title-{id}` | games index title under the tile |
| `game-open-{id}` | games index artwork tile → show |
| `game-art-{id}` | games index linked tile art |
| `game-art-slot-{id}` | games index placeholder tile |
| `game-art-slot` | games show box-art placeholder |
| `game-playing-{id}` | games index Playing badge |
| `start-play-session-button` | games show / empty play-sessions Start |
| `stop-play-session-button` | games show header Stop |
| `stop-play-session-button-{id}` | games show open-row Stop |
| `add-play-session-button` | games show Add Session |
| `play-time-total` | games show total time played |
| `open-session-game-link` | games show other-game banner |
| `play-session-{id}` | games show session row |
| `play-session-journal-{id}` | games show Journal chip |
| `edit-play-session-button-{id}` | games show session Edit |
| `play-session-actions-button-{id}` | games show session menu |
| `delete-play-session-button-{id}` | games show session Delete |
| `confirm-delete-play-session-button-{id}` / `cancel-delete-play-session-button-{id}` | session delete confirm |
| `play-session-started-at` / `play-session-ended-at` | session create/edit datetime-local |
| `save-play-session-button` | stop/add Save Session |
| `save-play-session-changes-button` | session edit Save Changes |
| `stop-session-body` | stop modal textarea |
| `create-journal-entry-button` | games show opens create modal |
| `add-journal-entry-button` | journal create modal submit |
| `compose-body` | journal create modal textarea |
| `journal-entry-{id}` | games show dated list row → show modal |
| `journal-entry-modal-{id}` | journal show modal |
| `edit-journal-entry-button-{id}` | journal show modal → edit modal |
| `save-journal-entry-button-{id}` | journal edit modal save |
| `delete-journal-entry-button-{id}` | journal show modal delete |
| `confirm-delete-journal-entry-button-{id}` / `cancel-delete-journal-entry-button-{id}` | journal delete confirm |

Fixtures: `User::factory()->withoutTwoFactor()->create()`. `DatabaseSeeder` calls `DemoSeeder` for local Games-show review; tests use factories. Default factory password is `password`. Factory default **enables** 2FA — omit `withoutTwoFactor()` and password login goes to the 2FA challenge.

Public registration defaults **off** (`REGISTRATION_ENABLED=false`). Tests that need signup: `Feature::define(\App\Features\Registration::class, true)`.

### Feature / controller tests

```bash
php artisan test --compact tests/Feature/Controllers/SessionControllerTest.php
php artisan test --compact tests/Feature/Controllers/UserControllerTest.php
php artisan test --compact tests/Feature/Controllers/UserProfileControllerTest.php
php artisan test --compact tests/Feature/Controllers/DashboardTest.php
php artisan test --compact tests/Feature/Controllers/GameControllerTest.php
php artisan test --compact tests/Feature/Controllers/JournalEntryControllerTest.php
php artisan test --compact tests/Feature/Controllers/PlaySessionControllerTest.php
php artisan test --compact tests/Browser/JournalEntriesTest.php
php artisan test --compact tests/Feature/Controllers/UserEmailResetNotificationTest.php
php artisan test --compact tests/Feature/Controllers/UserPasswordControllerTest.php
php artisan test --compact tests/Feature/Controllers/UserEmailVerificationTest.php
php artisan test --compact tests/Feature/Controllers/UserEmailVerificationNotificationControllerTest.php
php artisan test --compact tests/Feature/Controllers/UserTwoFactorAuthenticationControllerTest.php
php artisan test --compact tests/Feature/Controllers/UserPasskeyControllerTest.php
php artisan test --compact tests/Feature/Controllers/AppearanceTest.php
php artisan test --compact tests/Feature/BootstrapTest.php
```

### Ad-hoc smoke (`composer dev`)

```bash
curl -sS -o /dev/null -w "%{http_code}\n" http://localhost:8000/          # 302
curl -sS -o /dev/null -w "%{http_code}\n" http://localhost:8000/login     # 200
curl -sS http://localhost:8000/login | rg -o "Log in to your account|Sign in with a passkey|Log in" | head
```

Feature map: `.agents/skills/verify-kibbles/features/` (home, login, register, forgot-password, reset-password, email-verification, two-factor, dashboard, games, profile-settings, password-settings, passkeys, appearance).

## Evidence

- Pest failure / explicit `$page->screenshot('name')` → `tests/Browser/Screenshots/` (directory is wiped at the **start** of the next Browser suite boot).
- Durable proof runs: copy into a named directory you create:

```bash
RUN_ID=$(date +%Y%m%d-%H%M%S)
ART="artifacts/verify-kibbles/${RUN_ID}"
mkdir -p "$ART"
# doctor log, test stdout, HTML dumps, and any Screenshots/*.png copies go here
```

Proof standards: real user path, action + resulting state, and side effects when claiming mutations. Evidence under `artifacts/verify-kibbles/` must **survive cleanup**.

## Cleanup

- Do **not** stop a `composer dev` the user started — it is their foreground TUI, not a PID this skill owns.
- Kill **only** PIDs you recorded for an isolated serve (e.g. contents of `/tmp/kibbles-verify-*.pid`). Never `pkill php` / kill-by-name.
- Remove disposable sqlite files and `/tmp/kibbles-verify-*` markers you created.
- Never delete `artifacts/verify-kibbles/` or other evidence.
- Do not commit, do not modify `.env` secrets, do not delete user data in the project sqlite.
- Herd, if it happens to be running, is not this skill's server — leave it alone.

```bash
# Example when you started an isolated serve:
PID_FILE=/tmp/kibbles-verify-${RUN_ID}.pid
DB_FILE=$(cat /tmp/kibbles-verify-${RUN_ID}.db)
if [[ -f "$PID_FILE" ]]; then kill "$(cat "$PID_FILE")" 2>/dev/null || true; rm -f "$PID_FILE"; fi
rm -f "$DB_FILE" /tmp/kibbles-verify-${RUN_ID}.port /tmp/kibbles-verify-${RUN_ID}.db
```

## Helpers

| Helper | Invocation |
|--------|------------|
| Doctor | `.agents/skills/verify-kibbles/bin/doctor [base_url]` (default `http://localhost:8000`) — includes `check-passkey-host` |
| Passkey host | `.agents/skills/verify-kibbles/bin/check-passkey-host [browse_url]` — exit 0 + `passkey:host_ok` when browse host equals APP_URL / RP; exit 1 on mismatch |
| Prove home | `.agents/skills/verify-kibbles/bin/prove-home` — `tests/Browser/HomeTest.php` (Pest boots its own server). Doctor against `http://localhost:8000` is logged only. |
| Prove dashboard | `.agents/skills/verify-kibbles/bin/prove-dashboard` — `tests/Feature/Controllers/DashboardTest.php` + `tests/Browser/DashboardTest.php`. Doctor is logged only. |
| Prove games | `.agents/skills/verify-kibbles/bin/prove-games` — `GameControllerTest` + `JournalEntryControllerTest` + `PlaySessionControllerTest` + `GamesTest` + `JournalEntriesTest` + `PlaySessionsTest`. Doctor is logged only. |

Helpers are executable and `cd` to the kibbles project root. Set `RUN_ID` / `VERIFY_BASE_URL` to control artifact folder and base URL (default `http://localhost:8000`). Do **not** pipe `php artisan test` (Browser) through `tee`: leftover Playwright `run-server` inherits the pipe and the helper never exits. Redirect Pest to a log file, then `cat` it.
