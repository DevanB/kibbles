# Dashboard

Authenticated home at `/dashboard` (route `dashboard`, Inertia `dashboard`). Behind `auth` + `verified` middleware.

## Coverage reality (live-confirmed)

There is **no** dedicated `tests/Browser/*Dashboard*` or `tests/Feature/*Dashboard*` file. Existing coverage is adjacent:

| Proof | How |
|-------|-----|
| Guest gate | `curl -I http://localhost:8000/dashboard` → **302** `Location: …/login` |
| Auth landing | `SessionControllerTest` / `UserControllerTest` success paths `assertRedirectToRoute('dashboard')` |
| App shell | `LayoutTest` starts on settings (same authenticated app layout), not dashboard heading |

Authenticated **UI** `assertSee('Dashboard')` / Feature `get(route('dashboard'))` + Inertia `dashboard` are **patterns to add** in product tests — not present today. Do not invent product tests from this skill; prefer helper smoke below.

## Sub-features

- Dashboard heading / app layout shell
- Entry to settings via sidebar / user menu (`data-test="sidebar-menu-button"`, `logout-button`)
- Post-login / post-register landing target

## How to get to it (user POV)

Log in or register, then land on `/dashboard`, or open `/dashboard` while authenticated and verified. Guests are sent to login.

## Driving it with Pest Browser / Feature tests

Preconditions:

- `User::factory()->withoutTwoFactor()->create()` and `actingAs($user)` (ensure verified if middleware requires it)
- Vite build for Browser visits

- **Guest denied →** `curl -sS -o /dev/null -w "%{http_code}\n" -I http://localhost:8000/dashboard` → **302** to login
- **Auth redirect (Feature, existing) →** `php artisan test --compact --filter="may create a session$" tests/Feature/Controllers/SessionControllerTest.php` → **`assertRedirectToRoute('dashboard')`** (verified-via-redirect)
- **Durable partial proof →** `.cursor/skills/verify-kibbles/bin/prove-dashboard` → guest gate + login redirect log under artifacts
- **Authenticated render (Feature pattern to add) →** `$this->actingAs($user)->get(route('dashboard'))` → **200** + Inertia `dashboard`
- **Browser after login (pattern to add) →** `actingAs`, `visit(route('dashboard'))` → **`assertSee('Dashboard')`** + `assertNoJavaScriptErrors()`
- **Logout →** open user menu `@sidebar-menu-button` → `click('@logout-button')` → **guest on home/login**

## Gotchas

- Middleware `verified` — unverified users may not reach dashboard; factories should match the app’s verification expectations
- Layout/settings Browser tests start from profile, not dashboard, but use the same app layout (adjacent shell only)
- Prefer existing Feature redirect assertions + guest curl over inventing a new E2E harness or product test files outside this skill
