# Register

Guest registration at `/register` (route `register`, Inertia `user/create`). Creates a user and logs them in toward the dashboard.

## Sub-features

- Name, email, password, password confirmation
- Submit via `data-test="register-user-button"`
- Link back to login
- Fires `Registered` event on success

## How to get to it (user POV)

From welcome click **Register** (only if `canRegister`), from login click **Sign up**, or open `/register` as a guest.

## Driving it with Pest Browser / Feature tests

Preconditions:

- Guest session
- Registration enabled (`canRegister` / Fortify features as shipped)

- **User opens register →** `php artisan test --compact --filter="renders registration page" tests/Feature/Controllers/UserControllerTest.php` → **Inertia `user/create`** with password rules
- **User submits valid form →** `php artisan test --compact --filter="may register a new user" tests/Feature/Controllers/UserControllerTest.php` → **redirect `dashboard`**, user persisted, authenticated
- **Browser nav smoke only →** LayoutTest auth case: `visit(route('login'))` → click **Sign up** → **`Create an account`** (does **not** submit the register form)
- **Browser submit (pattern to add) →** `visit(route('register'))` → fill name/email/password/password_confirmation → `click('@register-user-button')` → **assertPathIs('/dashboard')**

## Gotchas

- Password rules come from `Password::defaults()` — Feature tests use values that satisfy current rules (`password1234` in happy path)
- Do not rely on DatabaseSeeder; it is empty
- After register, email verification middleware may affect `/dashboard` depending on Fortify config — Feature happy path expects redirect to `dashboard`
- LayoutTest covers register **nav smoke** only; full create path is Feature `UserControllerTest`
