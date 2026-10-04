# Dashboard

Authenticated home at `/dashboard` (route `dashboard`, Inertia `dashboard`). Behind `auth` + `verified` middleware. Closure in `routes/web.php` renders `resources/js/pages/dashboard.tsx` inside `AppLayout` (sidebar link + breadcrumb title `Dashboard`).

## Coverage reality

Dedicated tests:

| Proof | How |
|-------|-----|
| Guest gate | `tests/Feature/Controllers/DashboardTest.php` — guest `GET dashboard` → `assertRedirectToRoute('login')` |
| Verified render | same file — `User::factory()->withoutTwoFactor()->create()` + `actingAs` → **200** + `assertInertia` component `dashboard` |
| Unverified gate | same file — `unverified()` → `assertRedirectToRoute('verification.notice')` (`User` implements `MustVerifyEmail`; `verified` middleware) |
| Authenticated UI | `tests/Browser/DashboardTest.php` — `actingAs` + `visit(route('dashboard'))` → `assertSee('Dashboard')`, open `@sidebar-menu-button`, `assertSee('Log out')`, `assertNoJavaScriptErrors()` |
| Logout UI | same Browser file — `@sidebar-menu-button` then `click('@logout-button')` → `assertPathIs('/')` + `assertSee('Log in')`, then `navigate(route('dashboard'))` → `assertPathIs('/login')` (guest). HTTP logout stays `SessionControllerTest` `may destroy a session` (`POST logout` → `/` + `assertGuest()`) |

Adjacent (still true, not the dashboard proof):

- `SessionControllerTest` / `UserControllerTest` success paths `assertRedirectToRoute('dashboard')`
- `LayoutTest` starts on settings (same app layout), not the dashboard page

## Sub-features

- Dashboard heading / app layout shell (breadcrumb + sidebar title `Dashboard`)
- Entry to the user menu (`data-test="sidebar-menu-button"`) and logout item (`data-test="logout-button"`, label `Log out`)
- Post-login / post-register landing target

## How to get to it (user POV)

Log in or register as a verified user, then land on `/dashboard`, or open `/dashboard` while authenticated and verified. Guests are sent to `/login`. Authenticated users with `email_verified_at` null are sent to the email verification notice (`verification.notice`).

## Driving it with Pest Browser / Feature tests

Preconditions:

- `User::factory()->withoutTwoFactor()->create()` and `actingAs($user)` (factory is verified by default)
- Unverified: `User::factory()->unverified()->withoutTwoFactor()->create()`
- Pest Browser starts its own server — `composer dev` is not required to run these tests
- Playwright browsers installed (`bunx playwright install chromium` if Pest says they are missing)

- **Guest denied (Feature) →** `php artisan test --compact --filter="redirects guests to login" tests/Feature/Controllers/DashboardTest.php`
- **Verified render (Feature) →** `php artisan test --compact --filter="renders the dashboard for a verified user" tests/Feature/Controllers/DashboardTest.php` → **200** + Inertia `dashboard`
- **Unverified (Feature) →** `php artisan test --compact --filter="redirects unverified users" tests/Feature/Controllers/DashboardTest.php` → `verification.notice`
- **Unverified notice UI (Browser) →** `php artisan test --compact tests/Browser/EmailVerificationTest.php` → unverified `actingAs` + `visit(dashboard)` → **path `/verify-email`**, sees `Verify email` / `Resend verification email` / `Log out`
- **Browser →** `php artisan test --compact tests/Browser/DashboardTest.php` → **`assertSee('Dashboard')`**, user menu shows **Log out**, logout click lands on `/` then dashboard redirects to `/login`, `assertNoJavaScriptErrors()`
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-dashboard` (runs the two files above; doctor against `http://localhost:8000` is logged and does not fail the proof if `composer dev` is down)
- **Logout →** open user menu `@sidebar-menu-button` → `click('@logout-button')` → `assertPathIs('/')` (welcome shows **Log in**). Revisit `dashboard` → `assertPathIs('/login')` proves the browser session is a guest. Feature `SessionControllerTest` `may destroy a session` is the HTTP `assertGuest()` proof

## Gotchas

- `verified` middleware is real: unverified users never see the dashboard Inertia page
- Visible "Dashboard" copy is the sidebar nav item and the breadcrumb, not a page `<h1>` (the page body is placeholder cards; `<Head title="Dashboard" />` is the document title)
- Default URL for doctor/ad-hoc curls stays `http://localhost:8000` via `composer dev`. Do not change `.env` / `APP_URL`
- Passkey RP mismatch only if the browser host differs from `APP_URL`
