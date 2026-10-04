# Home (redirect)

`/` is **not a page**. Route `home` is `Route::redirect('/', '/dashboard')` in `routes/web.php`. There is no Inertia `welcome` component and no `resources/js/pages/welcome*`.

## Sub-features

- Guest: `/` → `/dashboard` (`auth` + `verified`) → `/login` (Inertia `session/create`)
- Verified user: `/` → `/dashboard` (Inertia `dashboard`)
- Unverified authenticated user: `/` → `/dashboard` → `verification.notice`
- Logout (`SessionController@destroy`) redirects to `/`, which re-enters this chain

## How to get to it (user POV)

Open `http://localhost:8000/` (`composer dev`, or the disposable serve URL).

## Driving it with Pest Browser / Feature tests

Preconditions:

- Vite manifest present (`bun run build`) or Pest compiles via its test server after build
- Doctor: `/up` is 200; guest `/` is **302** (not 200)

- **Guest `/` →** `php artisan test --compact tests/Browser/HomeTest.php` → **path `/login`**, `assertSee('Log in')`
- **Verified `/` →** same file → **path `/dashboard`**, `assertSee('Dashboard')`
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-home` → **artifacts under `artifacts/verify-kibbles/<run_id>/`**
- **Guest gate (Feature, destination only) →** `php artisan test --compact --filter="redirects guests to login" tests/Feature/Controllers/DashboardTest.php`

## Gotchas

- Do **not** run `tests/Browser/WelcomeTest.php` or look for `"component":"welcome"` / `Let's get started` — those are gone
- `bin/prove-welcome` is gone; use `bin/prove-home`
- curl without `-L` on `/` returns **302**. curl `-L` as a guest ends on login HTML
- Passkeys irrelevant on the redirect itself
