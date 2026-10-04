# Two-factor

Two surfaces: the **settings** page at `/settings/two-factor` (route `two-factor.show`, Inertia `user-two-factor-authentication/show`) and the **login challenge** (Fortify `two-factor.login`, Inertia `user-two-factor-authentication-challenge/show`).

## Sub-features

- Settings: enabled/disabled state (`twoFactorEnabled` prop). Requires password confirmation.
- Login: when the user has 2FA confirmed, `SessionController@store` stashes `login.id` / `login.remember` and redirects to `two-factor.login` instead of dashboard
- Factory default **enables** 2FA (`two_factor_confirmed_at` is set). Happy-path login/dashboard tests must use `withoutTwoFactor()`

## How to get to it (user POV)

- Settings: logged-in verified user → Settings → **Two-Factor Auth** (password confirm if needed)
- Challenge: log in with a 2FA-enabled user (default factory)

## Driving it with Pest Browser / Feature tests

- **Settings render →** `php artisan test --compact --filter="renders two factor authentication page" tests/Feature/Controllers/UserTwoFactorAuthenticationControllerTest.php`
- **Login challenge redirect →** `php artisan test --compact --filter="two-factor challenge" tests/Feature/Controllers/SessionControllerTest.php`
- **Challenge UI (Browser) →** `php artisan test --compact --filter="challenges a two-factor user" tests/Browser/SessionTest.php` → password submit → `/two-factor-challenge` → recovery code → `/dashboard`

## Gotchas

- Fortify `confirmPassword` applies to 2FA settings. Tests set `session(['auth.password_confirmed_at' => time()])`
- Password login is custom `SessionController`; the challenge view is Fortify
