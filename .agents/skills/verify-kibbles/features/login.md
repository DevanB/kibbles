# Login

Guest session create page at `/login` (route `login`, Inertia `session/create`). Password login posts to `login.store`; passkey verify UI is also present.

## Sub-features

- Email + password + remember me
- Submit via `data-test="login-button"` (“Log in”)
- Link to register (“Sign up”) and forgot-password when enabled
- Passkey: “Sign in with a passkey” (`PasskeyVerify`)
- Optional redirect to two-factor challenge when 2FA enabled

## How to get to it (user POV)

Open `/login` while logged out, or visit `/` as a guest (redirects toward login). Authenticated users are redirected away from guest routes.

## Driving it with Pest Browser / Feature tests

Preconditions:

- Guest session (or Feature test starts guest)
- For Browser layout checks: Vite build done; Playwright browsers installed
- Auth users for success paths: `User::factory()->withoutTwoFactor()->create([...])`

- **User opens login →** `php artisan test --compact --filter="renders login page" tests/Feature/Controllers/SessionControllerTest.php` → **Inertia `session/create`**, `canResetPassword` true, `canRegister` false unless `Feature::define(Registration::class, true)`
- **User opens login in browser →** `php artisan test --compact --filter="renders auth pages" tests/Browser/LayoutTest.php` → **sees** `Log in to your account`, `Enter your email and password below to log in`, `Sign in with a passkey`; click **Sign up** → **sees** `Create an account`
- **User submits valid password →** Feature `may create a session` (same file) → **redirect `dashboard`**, authenticated
- **User submits valid password in browser →** `php artisan test --compact --filter="creates a session from the login form" tests/Browser/SessionTest.php` → fill email/password → `click('@login-button')` → **path `/dashboard`**, sees `Dashboard`
- **User submits invalid password in browser →** `--filter="shows an error when login credentials are invalid"` (same file) → stays `/login`, sees `__('auth.failed')`, guest
- **2FA user in browser →** `--filter="challenges a two-factor user" tests/Browser/SessionTest.php` → password submit → `/two-factor-challenge` → recovery code → `/dashboard`

## Gotchas

- Passkey register/assert fails only if the browser host differs from `APP_URL` (e.g. Herd `kibbles.test` while `APP_URL` is `http://localhost:8000`). `composer dev` on localhost matches.
- Prefer `withoutTwoFactor()` unless intentionally testing the 2FA challenge — factory default **enables** 2FA. Browser challenge uses a known encrypted recovery code, not the factory’s random `two_factor_recovery_codes` string
- Empty seeder — always factory users; default password `password`
