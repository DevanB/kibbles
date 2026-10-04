# Forgot password

Guest request-reset page at `/forgot-password` (route `password.request`, Inertia `user-email-reset-notification/create`). POST `password.email` sends a reset notification when the account exists.

## Sub-features

- Email field + `data-test="email-password-reset-link-button"`
- Generic status: **A reset link will be sent if the account exists.**
- Linked from login when `canResetPassword` is true (`Route::has('password.request')`)

## How to get to it (user POV)

From login, click the forgot-password link, or open `/forgot-password` as a guest.

## Driving it with Pest Browser / Feature tests

- **User opens page →** `php artisan test --compact --filter="renders forgot password page" tests/Feature/Controllers/UserEmailResetNotificationTest.php` → Inertia `user-email-reset-notification/create`
- **User submits known email →** `--filter="may send password reset notification"` (same file) → redirect + `ResetPassword` notification
- No dedicated Browser test; do not invent a Playwright CLI

## Gotchas

- Unknown emails still get the generic status (no enumeration)
- Fortify `Features::resetPasswords()` is not the source of these routes — they are custom in `routes/web.php`
