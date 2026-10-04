# Passkeys

Authenticated passkey management at `/settings/passkeys` (route `user-passkey.index`, Inertia `user-passkey/index`). Behind `auth` + `verified`. Fortify passkey endpoints handle enroll/assert.

## Sub-features

- Empty copy **No passkeys yet** / **Add passkey**
- Password confirmation required (`password.confirm`)
- Login page also has **Sign in with a passkey** (`PasskeyVerify`) — see [login.md](./login.md)

## How to get to it (user POV)

While logged in and verified, open Settings → **Passkeys**. Sensitive-settings middleware may send you through password confirm first.

## Driving it with Pest Browser / Feature tests

Preconditions: `actingAs($user)` and `session(['auth.password_confirmed_at' => time()])`

- **Feature render →** `php artisan test --compact --filter="renders passkeys page" tests/Feature/Controllers/UserPasskeyControllerTest.php`
- **Browser empty state →** `php artisan test --compact --filter="renders passkeys page" tests/Browser/LayoutTest.php` → **Passkeys**, **No passkeys yet**, **Add passkey**
- **Missing confirm →** Feature `requires password confirmation to render passkeys page` (same Feature file) → redirect `password.confirm`

## Gotchas

- Passkey register/assert fails only if the browser host differs from `APP_URL` (e.g. Herd `kibbles.test` while `APP_URL` is `http://localhost:8000`)
- Prefer Pest Browser over a one-off Playwright script for WebAuthn
- Fortify `config/fortify.php` enables passkeys + 2FA; password login is still custom `SessionController`
