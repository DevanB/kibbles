# Random bookmark API

Token-authenticated JSON read at `GET /api/bookmarks/random` (route `bookmarks.random`). `AuthenticateKibblesApi` plus `throttle:60,1`. This is not an Inertia page and not session auth. The caller sends a Bearer token that must match `config('services.kibbles_api.token')` (`KIBBLES_API_TOKEN` in `.env`).

The handler resolves the sole `User` in the database, finds that owner's tag (query `tag`, default `read`), and returns one of that tag's bookmarks at random as `{ "data": <wire>|null }`. Missing tag is `404` `{ "error": "tag_not_found" }`. Zero or more than one user is `500` `{ "error": "owner_unresolved" }`.

## Sub-features

- Missing, empty, or wrong Bearer token → `401` JSON
- Empty `KIBBLES_API_TOKEN` / `services.kibbles_api.token` → `401` even if the caller sends a token
- Default `tag=read` when the query string omits `tag`
- Tag match is case-insensitive (`tags.name_normalized`)
- Tagged hit → `200` `{ "data": bookmark }` using `XBookmark::toWire()` (id, url, text, author, tags, media, quoted post, timestamps)
- Tag exists but no tagged bookmarks → `200` `{ "data": null }`
- Unknown tag → `404` `{ "error": "tag_not_found" }`
- Not exactly one user → `500` `{ "error": "owner_unresolved" }`

## How to get to it (caller POV)

Set a local token. Do not commit it and do not print it.

```bash
# from the project root
openssl rand -hex 32   # write the value into .env as KIBBLES_API_TOKEN=..., or export it for one isolated serve
```

Then `GET http://localhost:8000/api/bookmarks/random?tag=read` with `Authorization: Bearer <that token>`. Isolated serve: same path on `http://localhost:$PORT`. The process that serves the app must see `KIBBLES_API_TOKEN` (`.env` or inline env). Pest sets `config('services.kibbles_api.token')` in `beforeEach` and does not read a real secret.

## Driving it with Pest / live curl

Preconditions:

- Feature tests: `config(['services.kibbles_api.token' => '...'])` in the test (see `RandomBookmarkControllerTest`). Do not copy a production token into the test file.
- Live curl: a throwaway token in the **serve process** environment. Generate one with `openssl rand -hex 32`. Export or write `.env` locally. Never echo the value into logs or commits.
- Live curl also needs exactly one `User`, a tag named `read` (any casing), and at least one bookmark on that tag when you want a non-null `data` object.

- **HTTP contract (Feature) →** `php artisan test --compact tests/Feature/Controllers/RandomBookmarkControllerTest.php` → 401 without/wrong/empty token, 500 when the owner is unresolved, 200 with a `read`-tagged bookmark, 200 `data: null` when the tag exists but no bookmark is tagged, case-insensitive `tag=READ`, 404 unknown tag
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-x-bookmarks` (includes that Feature file)
- **Live instance (after doctor) →** curl the long-lived base URL. Replace `$BASE` with `http://localhost:8000` or the isolated `http://localhost:$PORT`. Do not print `$KIBBLES_API_TOKEN`.

```bash
# token already in the serve process env; this shell only needs the same value to send the header
curl -sS -D - -o /tmp/kibbles-random-bookmark.json \
  -H "Authorization: Bearer ${KIBBLES_API_TOKEN}" \
  -H "Accept: application/json" \
  "$BASE/api/bookmarks/random?tag=read"
# expect HTTP/1.1 200 (or 404/500 per the cases above). Inspect the JSON file. Do not cat secrets.
curl -sS -o /dev/null -w "%{http_code}\n" "$BASE/api/bookmarks/random?tag=read"
# expect 401 (no Authorization)
```

## Gotchas

- Laravel prefixes `routes/api.php` with `/api`. The path is `/api/bookmarks/random`, not `/bookmarks/random`.
- Session cookies and `actingAs()` do not authenticate this route. Only the Bearer token does.
- The token lives in `.env` / process env. The feature map must never contain a token value.
- `User::sole()` means this API is for a single-owner install. A second user in the project sqlite makes every live curl return `owner_unresolved`.
- Default tag name is `read`. A local DB with no such tag returns `tag_not_found`, not an empty list.
- Throttle is 60 requests per minute. Do not burn the limit during a live pass.
- Local `APP_DEBUG=true` 401 responses are still JSON, but the body includes a Laravel exception trace. Assert status and `error` keys, not the trace. Production debug-off 401 is an empty-message HTTP exception.
- Completing a real x.com sync to populate bookmarks still needs `X_CLIENT_ID` / `X_CLIENT_SECRET`. Feature tests factory the rows instead.
