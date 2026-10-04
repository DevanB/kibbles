# Welcome (home)

Public marketing/home page rendered by Inertia component `welcome` at `/` (route `home`). Guests see **Log in**; **Register** only when `canRegister` is true. Authenticated users see **Dashboard**.

## Sub-features

- Guest nav: Log in always; Register gated by Inertia prop `canRegister`
- Authenticated nav link to dashboard
- Starter copy (“Let's get started”, Laravel ecosystem blurb)
- Optional marketing links on the page (**Laracasts**, **Deploy now**) — not auth flows; do not require them for welcome proof

## How to get to it (user POV)

Open `http://localhost:8000/` (`composer dev`, or the disposable serve URL). No auth required.

## Driving it with Pest Browser / Feature tests

Preconditions:

- Vite manifest present (`bun run build`) or Pest will still compile via its test server after build
- Doctor `/up` and `/` return 200 on the URL under test (default `http://localhost:8000`)

- **User opens home →** `php artisan test --compact tests/Browser/WelcomeTest.php` → **passes** `assertSee('Laravel')` on `visit('/')`
- **User opens home (smoke) →** `curl -sS -o /dev/null -w "%{http_code}\n" http://localhost:8000/` → **`200`**; shell markers include `"component":"welcome"` / `<title>Laravel</title>` (full SSR copy may be absent — prove-welcome asserts shell markers)
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-welcome` → **artifacts under `artifacts/verify-kibbles/<run_id>/`** including doctor log + browser test log + optional `screenshots/welcome-proof.png`

## Gotchas

- Without Vite (`public/hot` from `composer dev`, or `public/build/manifest.json`), Inertia returns **500** even though `/up` is 200
- Welcome Browser test asserts `Laravel` (logo/copy), not the full headline — LayoutTest covers richer auth UI
- Register link is prop-gated (`canRegister`); do not fail welcome proof solely because Register is hidden when registration is disabled
- Passkeys irrelevant on this page; APP_URL mismatch does not block welcome render
