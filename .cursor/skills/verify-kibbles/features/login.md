# Login

Guest session create page at `/login` (route `login`, Inertia `session/create`). Password login posts to `login.store`; passkey verify UI is also present.

## Sub-features

- Email + password + remember me
- Submit via `data-test="login-button"` (“Log in”)
- Link to register (“Sign up”) and forgot-password when enabled
- Passkey: “Sign in with a passkey” (`PasskeyVerify`)
- Optional redirect to two-factor challenge when 2FA enabled

## How to get to it (user POV)

From welcome, click **Log in**, or open `/login` while logged out. Authenticated users are redirected away from guest routes.

## Driving it with Pest Browser / Feature tests

Preconditions:

- Guest session (or Feature test starts guest)
- For Browser layout checks: Vite build done; Playwright browsers installed
- Auth users for success paths: `User::factory()->withoutTwoFactor()->create([...])`

- **User opens login →** `php artisan test --compact --filter="renders login page" tests/Feature/Controllers/SessionControllerTest.php` → **Inertia `session/create`**, `canResetPassword`/`canRegister` true
- **User opens login in browser →** `php artisan test --compact --filter="renders auth pages" tests/Browser/LayoutTest.php` → **sees** `Log in to your account`, `Enter your email and password below to log in`, `Sign in with a passkey`; click **Sign up** → **sees** `Create an account`
- **User submits valid password →** Feature `may create a session` (same file) → **redirect `dashboard`**, authenticated
- **Ad-hoc submit (Browser style) →** `visit(route('login'))` → `fill` email/password → `click('@login-button')` → **path `/dashboard`** (prefer adding/extending Pest rather than a one-off Playwright script)

## Gotchas

- Passkey register/assert fails only if the browser host differs from `APP_URL` (e.g. Herd `kibbles.test` while `APP_URL` is `http://localhost:8000`). `composer dev` on localhost matches.
- Prefer `withoutTwoFactor()` unless intentionally testing the 2FA challenge redirect
- Empty seeder — always factory users; default password `password`
