---
name: seed-dev-routes
description: Seed public dungeon routes with realistic pack-based pulls into the local dev DB and generate real map thumbnails for them locally. Use when a discover/route-list page needs demo data ("this dungeon has no routes"), when testing route cards/lists, or when routes need thumbnails in dev. Not for production data or test fixtures (tests use factories directly).
---

# Seed dev routes + local thumbnails

Two tinker scripts in this directory create demo `DungeonRoute`s (published `world`,
`expires_at = null`, random pack-based pulls, varied views/rating/forces) and generate real
map thumbnails for them, entirely against the local dev stack. Proven end-to-end on
PR #3447's worktree (12 Pit of Saron routes, 2026-07-14).

## 1. Seed routes

```sh
docker compose exec -T -e DUNGEON_KEY=pitofsaron -e ROUTE_COUNT=12 app \
  php artisan tinker .claude/skills/seed-dev-routes/seed_routes.php
```

- Uses `DungeonRoute::factory()` + `KillZone::factory()->withEnemies()` on real enemy packs
  of the dungeon's current mapping version, so pulls look plausible on the map.
- Per-route target forces is randomized 98-112%, so cards exercise all three enemy-forces UI
  states (<100% warning, 100-105% ok, >=105% over-pull warning).
- Prints `CREATED: <key>,<key>,...` — feed that into the thumbnail script.
- Discover pages require `published_state_id = world` AND `expires_at IS NULL` (both set).
  If routes still don't show, check the dungeon is `active` and part of the current season.

## 2. Generate thumbnails locally

`ThumbnailService::doCreateThumbnail` hard-refuses in the `local` environment. Overriding
`APP_ENV` does NOT work: any non-local env makes `AppServiceProvider` call
`URL::forceScheme('https')`, which breaks the puppeteer preview URL. Instead, **temporarily
edit the guard** in `app/Service/DungeonRoute/ThumbnailService.php` (prefix the condition
with `false && `), and `git checkout --` the file afterwards. With `FILESYSTEM_DISK=local`
(the dev default) nothing can touch S3 — never do this with an S3 default disk.

Prerequisites (once per worktree):

```sh
# node_modules must exist (route_thumbnail.js requires puppeteer). Hardlink-copy is instant:
cmp -s package-lock.json ../../keystone.guru/package-lock.json && cp -al ../../keystone.guru/node_modules ./node_modules

# Chrome: copy chrome-headless-shell from the host puppeteer cache + install runtime deps
# (identical to the headless-browser-verify skill; deps vanish on container recreation)
mkdir -p .chrome-tmp && cp -r ~/.cache/puppeteer/chrome-headless-shell/linux-*/chrome-headless-shell-linux64 .chrome-tmp/
docker compose exec -T -u root app sh -c 'apt-get update -qq && apt-get install -yqq \
    libnspr4 libnss3 libasound2t64 libatk-bridge2.0-0t64 libatk1.0-0t64 libatspi2.0-0t64 \
    libcairo2 libcups2t64 libdbus-1-3 libexpat1 libgbm1 libglib2.0-0t64 libpango-1.0-0 \
    libvulkan1 libxcomposite1 libxdamage1 libxfixes3 libxkbcommon0 libxrandr2 fonts-liberation'
```

The preview page swaps tile/asset URLs to `*_internal` for unauthenticated (puppeteer)
requests, but the local `app-assets` container has NO map tiles (known env gap — see
MapTilesExistenceTest memory). Also the debugbar bakes itself into the screenshot. Fix both
in `.env` for the duration of the run, then restore:

```sh
sed -i 's|^ASSETS_BASE_URL_INTERNAL=.*|ASSETS_BASE_URL_INTERNAL=https://assets.keystone.guru|; s|^DEBUGBAR_ENABLED=.*|DEBUGBAR_ENABLED=false|' .env

docker compose exec -T \
  -e APP_URL=http://nginx \
  -e PUPPETEER_EXECUTABLE_PATH=/var/www/.chrome-tmp/chrome-headless-shell-linux64/chrome-headless-shell \
  -e THUMBNAIL_KEYS=<keys from step 1> \
  app php artisan tinker .claude/skills/seed-dev-routes/generate_thumbnails.php

sed -i 's|^ASSETS_BASE_URL_INTERNAL=.*|ASSETS_BASE_URL_INTERNAL=http://app-assets|; s|^DEBUGBAR_ENABLED=.*|DEBUGBAR_ENABLED=true|' .env
```

(`APP_URL=http://nginx` makes `route()` in the tinker process emit URLs headless Chrome can
reach inside the compose network; the web pages themselves still use the `.env` APP_URL.
The lazy-loading guard arms on >1-row hydrations — the scripts eager-load
`dungeon`/`mappingVersion` for this reason.)

## 3. Make the thumbnails actually serve

Files land on the `local` disk (`storage/app/private/thumbnails/...`, `File.disk = local`,
URL `/storage//thumbnails/...`). Two dev-env gaps block serving:

- The framework `/storage/{path}` route (`Illuminate\Filesystem\ServeFile`) returns **403**
  unless the disk config has `visibility => public` — this project's local disk doesn't.
- Flysystem creates the thumbnail dirs `0700`, which nginx's worker can't traverse.

Fix (worktree-local, both git-ignored/untracked):

```sh
ln -sfn /var/www/storage/app/private public/storage   # nginx serves /storage/* statically
chmod -R a+rX storage/app/private/thumbnails           # re-run after every generation batch
```

nginx merges the `//` in the URL automatically. Verify:
`curl -s -o /dev/null -w "%{http_code}" "http://localhost:<port>/storage/thumbnails/<key>/<file>.jpg"` → 200.

## Cleanup / gotchas

- Revert the ThumbnailService guard edit (`git checkout -- app/Service/DungeonRoute/ThumbnailService.php`).
- Routes are shared-DB data visible to every stack; delete via
  `DungeonRoute::whereIn('public_key', [...])` teardown if they should go away again.
- Regenerating a route's thumbnail replaces the old `DungeonRouteThumbnail` + `File` rows
  and deletes the old file from disk — safe to re-run.
- Route cards behind a Pennant feature flag? Activate the admin toggle
  (`Feature::for(User::find(1))->activate(<class>)`) and remember per-user resolved values
  are cached in the `features` table; `Feature::for(null)` covers anonymous headless checks.
