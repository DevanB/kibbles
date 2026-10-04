# Login

Guest session create page at `/login` (route `login`, Inertia `session/create`). Password login posts to `login.store` via custom `SessionController` (not Fortify views). Passkey verify UI is also present.

## Sub-features

- Email + password + remember me
- Submit via `data-test="login-button"` (“Log in”)
- Link to register (“Sign up”) only when `canRegister` is true (Pennant `App\Features\Registration`, default **false**)
- Link to forgot-password when `canResetPassword` (`Route::has('password.request')`)
- Passkey: “Sign in with a passkey” (`PasskeyVerify`) — no `data-test`; assert the text
- Optional redirect to two-factor challenge when 2FA enabled (see [two-factor.md](./two-factor.md))

## How to get to it (user POV)

Open `/login` while logged out, or visit `/` as a guest (`/` → `/dashboard` → `/login`). Authenticated users are redirected away from guest routes.

## Driving it with Pest Browser / Feature tests

Preconditions:

- Guest session (or Feature test starts guest)
- For Browser layout checks: Vite build done; Playwright browsers installed
- Auth users for success paths: `User::factory()->withoutTwoFactor()->create([...])`

- **User opens login →** `php artisan test --compact --filter="renders login page" tests/Feature/Controllers/SessionControllerTest.php` → **Inertia `session/create`**, `canResetPassword` true, `canRegister` **false** (default env)
- **User opens login in browser →** `php artisan test --compact --filter="renders auth pages" tests/Browser/LayoutTest.php` → **sees** `Log in to your account`, `Enter your email and password below to log in`, `Sign in with a passkey`; this case `Feature::define(Registration::class, true)` then click **Sign up** → **sees** `Create an account`
- **User submits valid password →** Feature `may create a session` (same Feature file) → **redirect `dashboard`**, authenticated
- **User submits valid password in browser →** `php artisan test --compact --filter="creates a session from the login form" tests/Browser/SessionTest.php` → fill email/password → `click('@login-button')` → **path `/dashboard`**, sees `Dashboard`
- **User submits invalid password in browser →** `--filter="shows an error when login credentials are invalid"` (same file) → stays `/login`, sees `__('auth.failed')`, guest
- **2FA user →** Feature `redirects to two-factor challenge when enabled` → `two-factor.login`
- **2FA user in browser →** `--filter="challenges a two-factor user" tests/Browser/SessionTest.php` → password submit → `/two-factor-challenge` → recovery code → `/dashboard`
- **Home hop →** `php artisan test --compact --filter="sends guests from home to login" tests/Browser/HomeTest.php`

There is **no** Feature test that asserts `canRegister: true` on the login page. The true path is Browser LayoutTest (Sign up visible after `Feature::define`).

## Gotchas

- Passkey register/assert fails only if the browser host differs from `APP_URL` (e.g. Herd `kibbles.test` while `APP_URL` is `http://localhost:8000`). `composer dev` on localhost matches.
- Prefer `withoutTwoFactor()` unless intentionally testing the 2FA challenge — factory default **enables** 2FA. Browser challenge uses a known encrypted recovery code, not the factory’s random `two_factor_recovery_codes` string
- Empty seeder — always factory users; default password `password`
- Unverified users who log in are still authenticated but `/dashboard` sends them to `verification.notice`
