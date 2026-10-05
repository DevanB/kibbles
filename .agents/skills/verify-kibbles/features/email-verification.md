# Email verification

Authenticated notice at `/verify-email` (route `verification.notice`, Inertia `user-email-verification-notification/create`). Signed GET `verification.verify` marks the email verified. `User` implements `MustVerifyEmail`; `dashboard` and `games` require `verified`.

## Sub-features

- Notice page + resend POST `verification.send` (throttled)
- Signed link verifies, then `redirect()->intended()` with fallback `dashboard?verified=1`. After a dashboard bounce the intended URL is `/dashboard` with no query string.
- Already-verified users hitting the notice go to dashboard
- Unverified users hitting `/dashboard` or `/games` go to this notice

## How to get to it (user POV)

Register (when Pennant is on) or sign in with `email_verified_at` null, then open `/dashboard`.

## Driving it with Pest Browser / Feature tests

- **Notice →** `php artisan test --compact --filter="renders verify email page" tests/Feature/Controllers/UserEmailVerificationNotificationControllerTest.php`
- **Signed verify →** `php artisan test --compact --filter="may verify email" tests/Feature/Controllers/UserEmailVerificationTest.php`
- **Dashboard gate →** `php artisan test --compact --filter="redirects unverified users" tests/Feature/Controllers/DashboardTest.php`
- **Notice UI (Browser) →** `php artisan test --compact tests/Browser/EmailVerificationTest.php` → unverified `actingAs` + `visit(dashboard)` → **path `/verify-email`**

## Gotchas

- Factory users are **verified by default**. Use `unverified()` (or `email_verified_at => null`) for this surface. Form login also needs `withoutTwoFactor()` or the factory 2FA challenge runs first. Browser notice uses `actingAs` + both.
- Feature `may verify email` starts from `verification.notice`, so it asserts the `?verified=1` fallback. A notice reached via `/dashboard` stores `/dashboard` as intended.
- Invalid signatures fail; do not follow an unsigned `/verify-email/{id}/{hash}`
