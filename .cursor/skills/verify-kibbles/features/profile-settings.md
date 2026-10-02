# Profile settings

Authenticated profile edit at `/settings/profile` (route `user-profile.edit`, Inertia `user-profile/edit`). `/settings` redirects here.

## Sub-features

- Update name/email via `data-test="update-profile-button"` (“Save”) → toast `Profile updated.`
- Delete account: `@delete-user-button` → password → `@confirm-delete-user-button` → home, user gone
  - Server path: **`UserController@destroy`** via route **`user.destroy`** (`DELETE user`) — Feature coverage lives in `UserControllerTest` (not `UserProfileControllerTest`)
- Settings nav to Appearance, Password, Passkeys, Two-factor (sibling pages)

## How to get to it (user POV)

While logged in, open Settings (or `/settings/profile`). Password-confirm may be required for sensitive siblings (passkeys).

## Driving it with Pest Browser / Feature tests

Preconditions:

- `$user = User::factory()->withoutTwoFactor()->create(); $this->actingAs($user);`
- For passkeys Browser case: also `session(['auth.password_confirmed_at' => time()])`

- **User opens profile →** `php artisan test --compact --filter="renders profile edit page" tests/Feature/Controllers/UserProfileControllerTest.php` → **Inertia `user-profile/edit`**
- **User saves profile →** Feature `may update profile information` → **redirect profile**, flash toast `Profile updated.`
- **Browser layout + toast →** `php artisan test --compact --filter="renders settings pages|shows a toast after updating the profile" tests/Browser/LayoutTest.php` → **sees** app name, `Settings`, `Profile information`; fill `name` → click **Save** → **`Profile updated.`**
- **Feature delete account →** `php artisan test --compact --filter="may delete user account" tests/Feature/Controllers/UserControllerTest.php` → **`UserController@destroy` / `user.destroy`**
- **Browser delete account →** LayoutTest `may delete the account from the profile settings` → `click('@delete-user-button')` → fill password → `click('@confirm-delete-user-button')` → **path `/`**, user null

## Gotchas

- Use `withoutTwoFactor()` unless testing 2FA settings
- Delete destroys the user — only in Pest’s refresh-database world, never against production data
- Passkey settings page is separate (`user-passkey.index`); RP host mismatch only if you browse a different host than `APP_URL` (e.g. Herd `kibbles.test` while `APP_URL` is `http://localhost:8000`)
