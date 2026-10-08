# NAS deploy (pull-based)

Production is `https://kibbles.devanb.us` on the UGREEN DXP4800 Plus. The stack lives in `/volume1/docker/kibbles`. After tests pass on `master` (push or a manual **tests** workflow dispatch), GitHub Actions publishes `ghcr.io/devanb/kibbles` tagged with the full commit SHA and `latest`. The NAS pulls that image. There is no inbound SSH.

Watchtower image: **`nickfedor/watchtower:1.22.3`**. `containrrr/watchtower` is archived. `nickfedor/watchtower` is the actively maintained drop-in fork (same labels and env vars).

## One-time NAS switch-over

Do this after the first image publish on `master` is green, and after the GHCR package is public (next section).

1. On the NAS, in `/volume1/docker/kibbles`, update the checkout so you have the new `compose.yaml` (and this `deploy/` tree). Keep the existing `.env` next to `compose.yaml`. Do not add `APP_REVISION` to `.env`.
2. Pull the published image and recreate the stack (volumes stay put):

   ```bash
   cd /volume1/docker/kibbles
   docker compose pull
   docker compose up -d
   ```

   Default `docker compose up -d` pulls `ghcr.io/devanb/kibbles:latest`. It does not build. Local builds are still available with `APP_REVISION=$(git rev-parse HEAD) docker compose build`.
3. Start Watchtower as its **own** Docker project (not part of the kibbles stack):

   ```bash
   cd /volume1/docker/kibbles/deploy/watchtower
   docker compose up -d
   ```

   It polls every 3 minutes, updates only containers labeled `com.centurylinklabs.watchtower.enable=true` (`app` and `queue`), and removes old images. `cloudflared` is unlabeled and is left alone.

`docker/entrypoint.sh` runs `php artisan migrate --force` only when the command is `frankenphp`. The queue service starts `php artisan queue:work`, so Watchtower restarting both containers does not run migrations twice. SQLite is not migrated concurrently.

## GHCR package visibility

**Yes — change the package to public once.** A first push with `GITHUB_TOKEN` from this public repo creates `ghcr.io/devanb/kibbles` **private**. Linking the package to the repo inherits *who* can access it, not anonymous pull. Watchtower and `docker compose pull` on the NAS have no registry login, so they need a public package.

After the first successful publish:

1. Open [github.com/DevanB/kibbles/pkgs/container/kibbles](https://github.com/DevanB/kibbles/pkgs/container/kibbles) (or the repo **Packages** tab → **kibbles**).
2. **Package settings** (right sidebar).
3. **Danger Zone** → **Change visibility** → **Public**.
4. Type `kibbles` and confirm **I understand the consequences, change package visibility**.

This cannot be reversed (GitHub will not let you make that package private again). Do not put a GHCR token in the repo or in `compose.yaml`.

## Check which commit is live

```bash
curl -sS https://kibbles.devanb.us/version
```

Expected body: the full 40-character git SHA of the image that is running, `Content-Type: text/plain`, and nothing else (no JSON, no env, no keys). Local / unset SHA returns `dev`.
