# Reset password

Guest set-new-password page at `/reset-password/{token}` (route `password.reset`, Inertia `user-password/create`). POST `password.store` updates the password and sends the user to login.

## Sub-features

- Password + confirmation + `data-test="reset-password-button"`
- Invalid token / unknown email fail with session errors
- Authenticated users are redirected away from the reset page

## How to get to it (user POV)

Open the signed reset link from the forgot-password email (tests mint a token with `Password::createToken($user)`).

## Driving it with Pest Browser / Feature tests

- **User opens page →** `php artisan test --compact --filter="renders reset password page" tests/Feature/Controllers/UserPasswordControllerTest.php` → Inertia `user-password/create`
- **User submits valid token →** `--filter="may reset password"` (same file) → redirect `login`, password changed, `PasswordReset` event
- **Browser submit →** `php artisan test --compact --filter="may reset the password from the reset link" tests/Browser/LayoutTest.php` → fill new password → `@reset-password-button` → **path `/login`**

## Gotchas

- Feature happy path uses `new-password`; Browser uses `new-Password-123!` — both must satisfy `Password::defaults()`
- After reset the user is a guest on `/login`, not logged in
