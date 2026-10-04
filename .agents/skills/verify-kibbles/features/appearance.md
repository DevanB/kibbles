# Appearance

Authenticated appearance settings at `/settings/appearance` (route `appearance.edit`, Inertia `appearance/update`). Closure in `routes/web.php`. Behind `auth` + `verified`.

## Sub-features

- Heading **Appearance settings**
- `AppearanceTabs` (light / dark / system)
- Settings nav sibling of Profile, Password, Two-Factor Auth, Passkeys

## How to get to it (user POV)

While logged in and verified, open Settings → **Appearance**.

## Driving it with Pest Browser / Feature tests

- **Browser nav →** `php artisan test --compact --filter="renders settings pages inside the app and settings layouts" tests/Browser/LayoutTest.php` → from profile, click **Appearance** → **Appearance settings**
- **Feature GET →** `php artisan test --compact tests/Feature/Controllers/AppearanceTest.php` → guest → `login`; verified → **200** + Inertia `appearance/update`; unverified → `verification.notice`
- Cookie sharing is covered by `tests/Unit/Middleware/HandleAppearanceTest.php` (not a user-path proof)

## Gotchas

- This page is a render-only closure — no controller action
- Appearance is not required for auth/dashboard proof
