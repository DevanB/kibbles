# Register

Guest registration at `/register` (route `register`, Inertia `user/create`). Creates a user and logs them in toward the dashboard.

## Sub-features

- Name, email, password, password confirmation
- Submit via `data-test="register-user-button"`
- Link back to login
- Fires `Registered` event on success

## How to get to it (user POV)

From login click **Sign up** (only if `canRegister`), or open `/register` as a guest.

## Driving it with Pest Browser / Feature tests

Preconditions:

- Guest session
- Registration enabled (`REGISTRATION_ENABLED=true` or `Feature::define(Registration::class, true)`). Signup routes stay registered; the Pennant flag is the kill switch. Fortify `Features::registration()` is not enabled; do not treat that comment as “register is off.”

- **User opens register →** `php artisan test --compact --filter="renders registration page" tests/Feature/Controllers/UserControllerTest.php` → **Inertia `user/create`** with password rules
- **User submits valid form →** `php artisan test --compact --filter="may register a new user" tests/Feature/Controllers/UserControllerTest.php` → **redirect `dashboard`**, user persisted, authenticated
- **Browser nav smoke only →** LayoutTest auth case: `visit(route('login'))` → click **Sign up** → **`Create an account`** (does **not** submit the register form)
- **User submits valid form in browser →** `php artisan test --compact tests/Browser/RegistrationTest.php` → `Feature::define(Registration::class, true)` → fill name/email/password/password_confirmation → `click('@register-user-button')` → **path `/verify-email`** (new users are unverified; dashboard `verified` middleware sends them here). User persisted, `email_verified_at` null

## Gotchas

- Password rules come from `Password::defaults()` — Feature tests use values that satisfy current rules (`password1234` in happy path)
- Do not rely on DatabaseSeeder; it is empty
- Feature happy path asserts the POST redirect to the `dashboard` **route**. The follow-up GET hits `verified` middleware, so Browser submit lands on `/verify-email`
- LayoutTest covers register **nav smoke** only; HTTP create path is Feature `UserControllerTest`; Browser submit is `RegistrationTest`
