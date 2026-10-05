# Dashboard

Authenticated home at `/dashboard` (route `dashboard`, `DashboardController`, Inertia `dashboard`). Behind `auth` + `verified` middleware. `DashboardController` renders `resources/js/pages/dashboard.tsx` inside `AppLayout` (sidebar link + breadcrumb title `Dashboard`). `/` redirects here (see [home.md](./home.md)). The page body is placeholder cards — no journal recency, title/subtitle, or add-entry affordances.

## Coverage reality

Dedicated tests:

| Proof | How |
|-------|-----|
| Guest gate | `tests/Feature/Controllers/DashboardTest.php` — guest `GET dashboard` → `assertRedirectToRoute('login')` |
| Verified render | `tests/Browser/DashboardTest.php` — `renders the dashboard for a verified user` → `assertSee('Dashboard')` (Feature file has no Inertia render case) |
| Unverified gate | Feature `DashboardTest` — `unverified()` → `assertRedirectToRoute('verification.notice')` |
| Authenticated UI | `tests/Browser/DashboardTest.php` — `actingAs` + `visit(route('dashboard'))` → `assertSee('Dashboard')`, open `@sidebar-menu-button`, `assertSee('Log out')` |
| Logout UI | same Browser file — `@sidebar-menu-button` then `click('@logout-button')` → **`assertPathIs('/login')`** (home redirect chain). HTTP logout stays `SessionControllerTest` `may destroy a session` (`POST logout` → `/` + `assertGuest()`) |
| Home hop | `tests/Browser/HomeTest.php` — verified `visit('/')` → `/dashboard` |

Adjacent (still true, not the dashboard proof):

- `SessionControllerTest` / `UserControllerTest` success paths `assertRedirectToRoute('dashboard')`
- `LayoutTest` starts on settings (same app layout), not the dashboard page
- Sidebar links to **Games** (`games.index`) — see [games.md](./games.md). `AppLayout` mounts the sidebar shell, not `AppHeader`.

## Sub-features

- Dashboard heading / app layout shell (breadcrumb + sidebar title `Dashboard`)
- Placeholder card body (no Recent Journal Entries)
- Entry to the user menu (`data-test="sidebar-menu-button"`) and logout item (`data-test="logout-button"`, label `Log out`)
- Post-login / post-register landing target
- Nav sibling **Games**
- Sidebar brand **Kibbles** (no Repository / Documentation footer links)
- Document title `{page} :: Kibbles`

## How to get to it (user POV)

Log in or register as a verified user, then land on `/dashboard`, or open `/` / `/dashboard` while authenticated and verified. Guests are sent to `/login`. Authenticated users with `email_verified_at` null are sent to `verification.notice`.

## Driving it with Pest Browser / Feature tests

Preconditions:

- `User::factory()->withoutTwoFactor()->create()` and `actingAs($user)` (factory is verified by default)
- Unverified: `User::factory()->unverified()->withoutTwoFactor()->create()`
- Pest Browser starts its own server — `composer dev` is not required to run these tests
- Playwright browsers installed (`bunx playwright install chromium` if Pest says they are missing)

- **Guest denied (Feature) →** `php artisan test --compact --filter="redirects guests to login" tests/Feature/Controllers/DashboardTest.php`
- **Verified render (Browser) →** `php artisan test --compact --filter="renders the dashboard for a verified user" tests/Browser/DashboardTest.php` → **`assertSee('Dashboard')`**
- **Unverified (Feature) →** `php artisan test --compact --filter="redirects unverified users" tests/Feature/Controllers/DashboardTest.php` → `verification.notice`
- **Unverified notice UI (Browser) →** `php artisan test --compact tests/Browser/EmailVerificationTest.php` → unverified `actingAs` + `visit(dashboard)` → **path `/verify-email`**, sees `Verify email` / `Resend verification email` / `Log out`
- **Browser →** `php artisan test --compact tests/Browser/DashboardTest.php` → **`assertSee('Dashboard')`**, user menu shows **Log out**, logout click lands on **`/login`**, `assertNoJavaScriptErrors()`
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-dashboard` (runs the two dashboard files; doctor against `http://localhost:8000` is logged and does not fail the proof if `composer dev` is down)
- **Logout →** open user menu `@sidebar-menu-button` → `click('@logout-button')` → **`assertPathIs('/login')`**. Feature `SessionControllerTest` `may destroy a session` is the HTTP `assertGuest()` proof (`POST logout` → `/`)

## Gotchas

- `verified` middleware is real: unverified users never see the dashboard Inertia page
- Visible "Dashboard" copy is the sidebar nav item and the breadcrumb, not a page `<h1>` (the page body is placeholder cards; `<Head title="Dashboard" />` is the document title **Dashboard :: Kibbles**)
- Browser logout no longer asserts path `/` — guests following `/` immediately land on `/login`
- Default URL for doctor/ad-hoc curls stays `http://localhost:8000` via `composer dev`. Do not change `.env` / `APP_URL`
- Passkey RP mismatch only if the browser host differs from `APP_URL`
