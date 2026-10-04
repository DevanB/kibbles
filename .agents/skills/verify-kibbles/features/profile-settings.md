# Profile settings

Authenticated profile edit at `/settings/profile` (route `user-profile.edit`, Inertia `user-profile/edit`). `/settings` redirects here. Profile edit/update is `auth` only; account delete and most sibling settings require `auth` + `verified`.

## Sub-features

- Update name/email via `data-test="update-profile-button"` (“Save”) → toast `Profile updated.`
- Delete account: `@delete-user-button` → password → `@confirm-delete-user-button` → user gone
  - Server path: **`UserController@destroy`** via route **`user.destroy`** (`DELETE user`) — Feature coverage lives in `UserControllerTest` (not `UserProfileControllerTest`)
  - Controller redirects to `home` (`/`); guests then follow `/` → `/dashboard` → **`/login`**. Browser test asserts **`assertPathIs('/login')`**
- Settings nav to Profile, Password, Two-Factor Auth, Passkeys, Appearance (sibling pages — see those feature files)

## How to get to it (user POV)

While logged in, open Settings (or `/settings/profile`). Password-confirm may be required for 2FA and passkeys siblings.

## Driving it with Pest Browser / Feature tests

Preconditions:

- `$user = User::factory()->withoutTwoFactor()->create(); $this->actingAs($user);`
- For passkeys Browser case: also `session(['auth.password_confirmed_at' => time()])`

- **User opens profile →** `php artisan test --compact --filter="renders profile edit page" tests/Feature/Controllers/UserProfileControllerTest.php` → **Inertia `user-profile/edit`**
- **User saves profile →** Feature `may update profile information` → **redirect profile**, flash toast `Profile updated.`
- **Browser layout + toast →** `php artisan test --compact --filter="renders settings pages|shows a toast after updating the profile" tests/Browser/LayoutTest.php` → **sees** app name, `Settings`, `Profile information`; fill `name` → click **Save** → **`Profile updated.`**
- **Appearance GET (Feature) →** `php artisan test --compact tests/Feature/Controllers/AppearanceTest.php` → guest → `login`; verified → **200** + Inertia `appearance/update`; unverified → `verification.notice`
- **Feature delete account →** `php artisan test --compact --filter="may delete user account" tests/Feature/Controllers/UserControllerTest.php` → **`UserController@destroy` / `user.destroy`** + `assertRedirectToRoute('home')`
- **Browser delete account →** LayoutTest `may delete the account from the profile settings` → `click('@delete-user-button')` → fill password → `click('@confirm-delete-user-button')` → **path `/login`**, user null
- **Unverified cannot delete →** Feature `requires verified email to delete account`

## Gotchas

- Use `withoutTwoFactor()` unless testing 2FA settings (Browser tests do; `UserProfileControllerTest` uses a plain factory)
- Delete destroys the user — only in Pest’s refresh-database world, never against production data
- Passkey settings page is separate (`user-passkey.index`); RP host mismatch only if you browse a different host than `APP_URL`
- LayoutTest save uses `click('Save')`, not `@update-profile-button`
