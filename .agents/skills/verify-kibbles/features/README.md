# kibbles feature map

Baseline map for **verify-kibbles**. Drive features with Pest Browser / Feature tests; keep durable proof under `artifacts/verify-kibbles/<run_id>/`.

Live skill directory: `.agents/skills/verify-kibbles`. `.cursor/skills` and `.claude/skills` symlink to `.agents/skills` — they are not a second map.

## Baseline preconditions

1. From the project root
2. `vendor/` present; `bun install` if `node_modules` missing
3. Preferred stack: `composer dev` (serve + queue + pail + Vite) at `http://localhost:8000`. Ready when `/up` is 200 and Vite is up (`public/hot`, or `public/build/manifest.json` if you built instead). Do not start `composer dev` from a skill — it is a foreground TUI.
4. Doctor green: `.agents/skills/verify-kibbles/bin/doctor` (default `http://localhost:8000`) → `/up` is 200, guest `/` is **302** to `/dashboard`, `/login` is 200
5. If Pest Browser errors about Playwright outdated/missing browsers: `bunx playwright install`
6. Ad-hoc curls use `http://localhost:8000`; Pest Browser boots its own server. Optional alternate: Herd `http://kibbles.test` only if you pass that URL explicitly.
7. Auth fixtures: `User::factory()->withoutTwoFactor()->create()` — seeder is empty. Factory default enables 2FA; omit `withoutTwoFactor()` and login goes to the 2FA challenge.
8. Do not modify `.env`. Keep `APP_URL=http://localhost:8000`. Passkey RP mismatch only if the browser host differs from `APP_URL` (e.g. Herd while APP_URL is localhost). Doctor runs `bin/check-passkey-host` and **fails** on mismatch — do not treat that browse URL as a working passkey path.
9. Public registration defaults **off** (`REGISTRATION_ENABLED=false` / Pennant `App\Features\Registration`). Tests that need signup call `Feature::define(Registration::class, true)`.

## Driving conventions

- **Primary harness:** `php artisan test --compact tests/Browser/...` and `tests/Feature/Controllers/...`
- Pest `@name` → element `data-test="name"` (e.g. `click('@login-button')`)
- Assert copy from real pages (`Log in to your account`, `Create an account`, `Profile information`, `Games`, …)
- Feature tests use `assertInertia` + route names (`login`, `register`, `dashboard`, `games.index`, `user-profile.edit`)

## Proof / skip reporting

- **Proof:** doctor log + test stdout + optional `tests/Browser/Screenshots/*.png` copied to `artifacts/verify-kibbles/<run_id>/`
- **Skip:** note blocked precondition (Vite not up, nothing on :8000, missing browser) — do not invent a parallel Playwright CLI
- Evidence must survive cleanup; never delete `artifacts/verify-kibbles/`

## Features

| Feature | Path | File |
|---------|------|------|
| Home (redirect) | `/` | [home.md](./home.md) |
| Login | `/login` | [login.md](./login.md) |
| Register | `/register` | [register.md](./register.md) |
| Forgot password | `/forgot-password` | [forgot-password.md](./forgot-password.md) |
| Reset password | `/reset-password/{token}` | [reset-password.md](./reset-password.md) |
| Email verification | `/verify-email` | [email-verification.md](./email-verification.md) |
| Two-factor | `/settings/two-factor` + challenge | [two-factor.md](./two-factor.md) |
| Dashboard | `/dashboard` | [dashboard.md](./dashboard.md) |
| Games | `/games` | [games.md](./games.md) |
| Profile settings | `/settings/profile` | [profile-settings.md](./profile-settings.md) |
| Password settings | `/settings/password` | [password-settings.md](./password-settings.md) |
| Passkeys | `/settings/passkeys` | [passkeys.md](./passkeys.md) |
| Appearance | `/settings/appearance` | [appearance.md](./appearance.md) |

There is **no** welcome / marketing page. `welcome.md` and `tests/Browser/WelcomeTest.php` are gone.

## Coverage notes (maintain pass)

- **Home:** `Route::redirect('/', '/dashboard')`. Guest `/` → `/login`; verified `/` → `/dashboard`. Drive with `tests/Browser/HomeTest.php` and `bin/prove-home`.
- **Register:** Pennant kill-switch. Default env is off (403 on `/register`). Feature tests enable the flag in `beforeEach`. LayoutTest is nav smoke; full submit is `tests/Browser/RegistrationTest.php` (lands on `/verify-email`).
- **Login Browser submit:** `tests/Browser/SessionTest.php` (success → `/dashboard`; invalid password stays `/login` with `auth.failed`; 2FA recovery code → `/dashboard`).
- **Dashboard logout (Browser):** lands on `/login` immediately (home redirect chain). HTTP logout still `POST logout` → `/`.
- **Games:** user-owned catalog (`GameController`, unique title per user). Drive with `tests/Feature/Controllers/GameControllerTest.php` + `tests/Browser/GamesTest.php` and `bin/prove-games`.
- **Profile delete:** `UserController@destroy` (`user.destroy`); Browser lands on `/login` after the home redirect.
- **Appearance GET:** `tests/Feature/Controllers/AppearanceTest.php` (guest → login, verified → Inertia `appearance/update`, unverified → `verification.notice`).
- **Verify-email notice UI:** `tests/Browser/EmailVerificationTest.php` (unverified dashboard visit).
- **2FA / passkeys:** Fortify views + custom settings pages. Password confirm required for both. Factory default has 2FA on.
- **Passkey host:** `bin/check-passkey-host` (doctor). Matching browse host → `passkey:host_ok`. Mismatched browse host is a hard fail.
