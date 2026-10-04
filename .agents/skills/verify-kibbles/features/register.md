# Register

Guest registration at `/register` (route `register`, Inertia `user/create`). Creates a user and logs them in toward the dashboard. **Pennant-gated**: default `REGISTRATION_ENABLED=false`.

## Sub-features

- Name, email, password, password confirmation
- Submit via `data-test="register-user-button"`
- Link back to login
- Fires `Registered` event on success
- Kill switch: `App\Features\Registration` — routes stay registered; `UserController@create` / `CreateUserRequest::authorize` return **403** when inactive

## How to get to it (user POV)

From login click **Sign up** (only if `canRegister`), or open `/register` as a guest. With default env, `/register` is **403** and login hides Sign up.

## Driving it with Pest Browser / Feature tests

Preconditions:

- Guest session
- Registration enabled (`REGISTRATION_ENABLED=true` or `Feature::define(Registration::class, true)`). `UserControllerTest` enables the flag in `beforeEach`. `phpunit.xml` does **not** set `REGISTRATION_ENABLED`. Fortify `Features::registration()` is not enabled; do not treat that comment as “register is off.”

- **User opens register →** `php artisan test --compact --filter="renders registration page" tests/Feature/Controllers/UserControllerTest.php` → **Inertia `user/create`** with password rules
- **User submits valid form →** `php artisan test --compact --filter="may register a new user" tests/Feature/Controllers/UserControllerTest.php` → **redirect `dashboard`**, user persisted, authenticated, `Registered` fired
- **Feature off →** `--filter="forbids the registration page when the feature is inactive"` and `--filter="does not create a user when the feature is inactive"` → **403**, no user
- **Browser nav smoke only →** LayoutTest `renders auth pages inside the auth layout`: `Feature::define` → `visit(route('login'))` → click **Sign up** → **`Create an account`** (does **not** submit the register form)
- **User submits valid form in browser →** `php artisan test --compact tests/Browser/RegistrationTest.php` → `Feature::define(Registration::class, true)` → fill name/email/password/password_confirmation → `click('@register-user-button')` → **path `/verify-email`** (new users are unverified; dashboard `verified` middleware sends them here). User persisted, `email_verified_at` null

## Gotchas

- Password rules come from `Password::defaults()` — Feature tests use values that satisfy current rules (`password1234` in happy path)
- Do not rely on DatabaseSeeder; it is empty
- Feature happy path asserts the POST redirect to the `dashboard` **route**. The follow-up GET hits `verified` middleware, so Browser submit lands on `/verify-email`
- LayoutTest covers register **nav smoke** only; HTTP create path is Feature `UserControllerTest`; Browser submit is `RegistrationTest`
