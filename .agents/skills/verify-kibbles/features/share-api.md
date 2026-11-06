# Share Sheet API

Token-authenticated JSON write at `POST /api/links` (route `links.store`). Middleware is `AuthenticateKibblesApi:share`, `AuthenticateSoleOwner`, and `throttle:30,1`. This is not an Inertia page and not session auth. The caller sends a Bearer token that must match `config('services.kibbles_api.share_token')` (`KIBBLES_SHARE_TOKEN` in `.env`). That secret is **not** `KIBBLES_API_TOKEN`.

The handler saves through `SaveLink` for the sole `User` in the database. Body is `{ "url": "https://..." }`.

## Sub-features

- Missing, empty, wrong, or **read** (`KIBBLES_API_TOKEN`) Bearer token → `401`
- Empty `KIBBLES_SHARE_TOKEN` / `services.kibbles_api.share_token` → `401` even if the caller sends a token
- Not exactly one user → `401`
- New normalized URL → `201` `{ "message": "Saved to Kibbles." }`
- Duplicate normalized URL → `200` `{ "message": "Already in Kibbles." }`
- Invalid URL → `422` with `url` validation error `Enter a valid http or https URL.`

## How to get to it (caller POV)

Set a local share token. Do not commit it and do not print it.

```bash
# from the project root
openssl rand -hex 32   # write the value into .env as KIBBLES_SHARE_TOKEN=..., or export it for one isolated serve
```

Then `POST http://localhost:8000/api/links` with `Authorization: Bearer <that token>` and JSON `{"url":"https://example.com"}`. Isolated serve: same path on `http://localhost:$PORT`. The process that serves the app must see `KIBBLES_SHARE_TOKEN`. Pest sets `config('services.kibbles_api.share_token')` in the test and does not read a real secret.

Production URL: `https://kibbles.devanb.us/api/links`.

## Save to Kibbles shortcut (iPhone and Mac)

One shortcut named **Save to Kibbles**. Create it on iPhone; it syncs to Mac over iCloud. Do not put the token in git, chat, or screenshots.

### iPhone

1. Open **Shortcuts** → **+**. Name it **Save to Kibbles**.
2. Tap the shortcut name / **i** → turn **Show in Share Sheet** on. Under Share Sheet Types, enable **URLs** only.
3. Add **Get Shortcut Input** if Shortcuts did not add it from that setting.
4. Add **Get Contents of URL**:
   - URL: `https://kibbles.devanb.us/api/links`
   - Method: **POST**
   - Headers: `Authorization` = `Bearer ` plus the `KIBBLES_SHARE_TOKEN` value from the NAS `.env` (paste only inside Shortcuts)
   - Headers: `Accept` = `application/json`
   - Headers: `Content-Type` = `application/json`
   - Request Body: **JSON**. Field `url` = **Shortcut Input**
5. Add **Show Notification**. Title: `Save to Kibbles`. Body: **Contents of URL** (you will see `Saved to Kibbles.` or `Already in Kibbles.`).
6. In Safari (or any app), **Share** → **Save to Kibbles**.

### Mac

1. Open **Shortcuts**. If **Save to Kibbles** already synced from iPhone, skip to step 5.
2. Otherwise create the same shortcut as on iPhone (same name, same **Get Contents of URL** action).
3. Shortcut details: enable **Use as Quick Action** / **Show in Share Sheet**, input **URLs**.
4. From Safari: **File → Share → Save to Kibbles**, or the Share sheet on a selected URL.
5. The POST is identical: `https://kibbles.devanb.us/api/links`, header `Authorization: Bearer …` from `KIBBLES_SHARE_TOKEN`, JSON `{"url": Shortcut Input}`.

## Driving it with Pest / live curl

Preconditions:

- Feature tests: `config(['services.kibbles_api.share_token' => '...'])` in the test (see `StoreLinkControllerTest`). Do not copy a production token into the test file.
- Live curl: a throwaway share token in the **serve process** environment. Generate one with `openssl rand -hex 32`. Never echo the value into logs or commits.
- Live curl also needs exactly one `User`.

- **HTTP contract (Feature) →** `php artisan test --compact tests/Feature/Controllers/StoreLinkControllerTest.php` → 401 without/wrong/read/empty token and when the owner is unresolved; 201 new URL; 200 duplicate; 422 bad URL
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-share-api`
- **Live instance (after doctor) →** curl the long-lived base URL. Replace `$BASE` with `http://localhost:8000` or `https://kibbles.devanb.us`. Do not print `$KIBBLES_SHARE_TOKEN`.

```bash
# token already in the serve process env; this shell only needs the same value to send the header
curl -sS -D - -o /tmp/kibbles-share.json \
  -H "Authorization: Bearer ${KIBBLES_SHARE_TOKEN}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://example.com/from-share"}' \
  "$BASE/api/links"
# expect HTTP/1.1 201 or 200. Inspect the JSON file. Do not cat secrets.
curl -sS -o /dev/null -w "%{http_code}\n" "$BASE/api/links"
# expect 401 (no Authorization)
```

## Gotchas

- Laravel prefixes `routes/api.php` with `/api`. The path is `/api/links`, not `/links`.
- Session cookies and `actingAs()` do not authenticate this route. Only the share Bearer token does.
- The read token (`KIBBLES_API_TOKEN`) is rejected. The two secrets must stay different.
- `User::sole()` means this API is for a single-owner install.
- Throttle is 30 requests per minute.
- Local `APP_DEBUG=true` 401 responses are still JSON, but the body includes a Laravel exception trace. Assert status, not the trace.
