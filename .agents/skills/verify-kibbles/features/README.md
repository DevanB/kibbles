# kibbles feature map

Baseline map for **verify-kibbles**. Drive features with Pest Browser / Feature tests; keep durable proof under `artifacts/verify-kibbles/<run_id>/`.

## Baseline preconditions

1. From the project root
2. `vendor/` present; `bun install` if `node_modules` missing
3. Preferred stack: `composer dev` (serve + queue + pail + Vite) at `http://localhost:8000`. Ready when `/up` is 200 and Vite is up (`public/hot`, or `public/build/manifest.json` if you built instead). Do not start `composer dev` from a skill — it is a foreground TUI.
4. Doctor green: `.agents/skills/verify-kibbles/bin/doctor` (default `http://localhost:8000`) → `/up` and `/` are 200
5. If Pest Browser errors about Playwright outdated/missing browsers: `bunx playwright install`
6. Ad-hoc curls use `http://localhost:8000`; Pest Browser boots its own server. Optional alternate: Herd `http://kibbles.test` only if you pass that URL explicitly.
7. Auth fixtures: `User::factory()->withoutTwoFactor()->create()` — seeder is empty
8. Do not modify `.env`. Keep `APP_URL=http://localhost:8000`. Passkey RP mismatch only if the browser host differs from `APP_URL` (e.g. Herd while APP_URL is localhost).

## Driving conventions

- **Primary harness:** `php artisan test --compact tests/Browser/...` and `tests/Feature/Controllers/...`
- Pest `@name` → element `data-test="name"` (e.g. `click('@login-button')`)
- Assert copy from real pages (`Log in to your account`, `Let's get started`, `Profile information`, …)
- Feature tests use `assertInertia` + route names (`login`, `register`, `dashboard`, `user-profile.edit`)

## Proof / skip reporting

- **Proof:** doctor log + test stdout + optional `tests/Browser/Screenshots/*.png` copied to `artifacts/verify-kibbles/<run_id>/`
- **Skip:** note blocked precondition (Vite not up, nothing on :8000, missing browser) — do not invent a parallel Playwright CLI
- Evidence must survive cleanup; never delete `artifacts/verify-kibbles/`

## Features

| Feature | Path | File |
|---------|------|------|
| Welcome (home) | `/` | [welcome.md](./welcome.md) |
| Login | `/login` | [login.md](./login.md) |
| Register | `/register` | [register.md](./register.md) |
| Dashboard | `/dashboard` | [dashboard.md](./dashboard.md) |
| Profile settings | `/settings/profile` | [profile-settings.md](./profile-settings.md) |

## Coverage notes (maintain pass)

- **Dashboard:** `tests/Feature/Controllers/DashboardTest.php` (guest → login, verified → Inertia `dashboard`, unverified → `verification.notice`) and `tests/Browser/DashboardTest.php` (`assertSee('Dashboard')` + user menu Log out). See [dashboard.md](./dashboard.md) and `bin/prove-dashboard`.
- **Welcome Register:** gated by `canRegister`; Laracasts/Deploy links are optional marketing.
- **Profile delete:** `UserController@destroy` (`user.destroy`); Feature in `UserControllerTest`.
- **Register Browser:** LayoutTest is nav smoke only (login → Sign up).
