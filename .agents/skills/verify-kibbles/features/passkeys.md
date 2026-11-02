# Passkeys

Authenticated passkey management at `/settings/passkeys` (route `user-passkey.index`, Inertia `user-passkey/index`). Behind `auth` + `verified`. Fortify passkey endpoints handle enroll/assert.

## Sub-features

- Empty copy **No passkeys yet** / **Add passkey**. Without WebAuthn, **Add passkey** is replaced by **Passkeys are not supported in this browser.**
- Password confirmation required (`password.confirm` → `/user/confirm-password`). That confirm page also offers **Confirm with passkey**.
- Unverified users hitting `/settings/passkeys` go to `verification.notice`
- `GET /.well-known/passkey-endpoints` returns JSON `{ enroll, manage }` both pointing at `user-passkey.index`
- Login page also has **Sign in with a passkey** (`PasskeyVerify`) — see [login.md](./login.md)

## How to get to it (user POV)

While logged in and verified, open Settings → **Passkeys**. `password.confirm` on `UserPasskeyController@index` (and Fortify passkey management routes) may send you through `/user/confirm-password` first.

## Driving it with Pest Browser / Feature tests

Preconditions: `actingAs($user)` and `session(['auth.password_confirmed_at' => time()])`

- **Feature render →** `php artisan test --compact --filter="renders passkeys page" tests/Feature/Controllers/UserPasskeyControllerTest.php`
- **Browser empty state →** `php artisan test --compact --filter="renders passkeys page" tests/Browser/LayoutTest.php` → **Passkeys**, **No passkeys yet**, **Add passkey**
- **Missing confirm →** Feature `requires password confirmation to render passkeys page` (same Feature file) → redirect `password.confirm` (`/user/confirm-password`)
- **Unverified →** Feature `requires verified email to render passkeys page` → `verification.notice`
- **Well-known →** Feature `exposes passkey endpoints` → JSON `enroll` and `manage` are `user-passkey.index`

## Gotchas

- Passkey register/assert fails if the browser host differs from `APP_URL` (e.g. Herd `kibbles.test` while `APP_URL` is `http://localhost:8000`). Missing WebAuthn hides enroll. The `passkeys` limiter is 10 attempts per minute.
- Prefer Pest Browser over a one-off Playwright script for WebAuthn
- Fortify `config/fortify.php` enables passkeys + 2FA; password login is still custom `SessionController`
