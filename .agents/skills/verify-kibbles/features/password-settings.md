# Password settings

Authenticated password change at `/settings/password` (route `password.edit`, Inertia `user-password/edit`). PUT `password.update` is throttled. Behind `auth` + `verified`.

## Sub-features

- Current + new + confirmation
- Submit via `data-test="update-password-button"`
- Requires verified email

## How to get to it (user POV)

While logged in and verified, open Settings → **Password**.

## Driving it with Pest Browser / Feature tests

- **User opens page →** `php artisan test --compact --filter="renders edit password page" tests/Feature/Controllers/UserPasswordControllerTest.php`
- **User updates password →** `--filter="may update password"` (same file)
- **Unverified gate →** `--filter="requires verified email to edit password"` (same file)
- No dedicated Browser test for this settings page

## Gotchas

- Guest reset lives on a different page (`user-password/create`) — see [reset-password.md](./reset-password.md)
- Do not confuse `password.edit` (settings) with `password.reset` (guest token)
