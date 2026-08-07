---
name: seed-dev-routes
description: Seed public dungeon routes with realistic pack-based pulls into the local dev DB and give them real map thumbnails. Use when a discover/route-list page needs demo data ("this dungeon has no routes"), when testing route cards/lists, or when routes need thumbnails in dev. Not for production data or test fixtures (tests use factories directly).
---

# Seed dev routes + thumbnails

`seed_routes.php` in this directory is a tinker script that creates demo `DungeonRoute`s
(published `world`, `expires_at = null`, random pack-based pulls, varied views/rating/forces).
Thumbnails are **not** this skill's job — once the routes exist, hand them to the
[[generating-thumbnails]] skill.

## 1. Seed routes

Runs anywhere — the main checkout or any worktree, isolated or `--shared-db`. (Only step 2 is
picky about where you are.)

```sh
docker compose exec -T -e DUNGEON_KEY=pitofsaron -e ROUTE_COUNT=12 app \
  php artisan tinker .claude/skills/seed-dev-routes/seed_routes.php
```

- Uses `DungeonRoute::factory()` + `KillZone::factory()->withEnemies()` on real enemy packs
  of the dungeon's current mapping version, so pulls look plausible on the map.
- Per-route target forces is randomized 98-112%, so cards exercise all three enemy-forces UI
  states (<100% warning, 100-105% ok, >=105% over-pull warning).
- Prints `CREATED: <key>,<key>,...` — keep those public keys for step 2 and for cleanup.
- Discover pages require `published_state_id = world` AND `expires_at IS NULL` (both set).
  If routes still don't show, check the dungeon is `active` and part of the current season.

## 2. Give them thumbnails

**Needs the main checkout or a `--shared-db` worktree.** A default worktree has its own database
schema *and* redis prefix, so the shared Horizon sees neither the route rows nor the dispatch. If
you seeded in an isolated worktree that is not a failure — the routes simply keep falling back to
the dungeon image, which is usually fine for list/card work. Only re-seed somewhere shared if you
actually need thumbnail images.

Queue each key onto the shared thumbnail queue (Path B of [[generating-thumbnails]] — the
main stack's Horizon is the only Chrome-capable container, and it renders to the `public` disk
that every stack serves at `/storage/...`). Step 1 prints the keys comma-separated, so split them:

```sh
for key in $(echo '<CREATED: line from step 1>' | tr ',' ' '); do
  docker compose exec -T app php artisan dungeonroute:queuethumbnail "$key" --force
done
```

Read [[generating-thumbnails]] before doing anything else with thumbnails — in particular do
**not** call `ThumbnailService::createThumbnail()` synchronously from the `app` container (no
Chrome there), edit its guards, install Chrome into `app`, or symlink `public/storage`.

Gotchas that will otherwise look like bugs:

- **Nothing renders and the queue just grows?** Horizon can wedge on a stale redis connection.
  Check `docker compose exec -T redis redis-cli -p 6380 llen
  'keystoneguru-local-cache:queues:local-thumbnail'` from the main checkout; if it stays
  non-zero, `docker compose restart horizon` and it drains.
- Dev renders show the **DebugBar** and **black map tiles** (the local `app-assets` container
  has no tiles). That is a known dev env gap affecting every render path, not a seeding
  problem — fine for exercising card layout.

## Cleanup / gotchas

- Seeded in a **shared** database (main checkout / `--shared-db`)? The routes are visible on every
  stack — seed a small batch and delete them when done. Delete them **one model at a time**, so
  `DungeonRoute`'s `deleting` hook runs and cleans up the pulls, paths, tags, thumbnail rows and
  the JPGs on the shared `public` disk; a query-builder mass delete fires no model events and
  leaves all of it orphaned:
  ```php
  DungeonRoute::whereIn('public_key', [...])->get()->each->delete();
  ```
- Regenerating a route's thumbnail replaces the old `DungeonRouteThumbnail` + `File` rows
  and deletes the old file from disk — safe to re-run.
- Route cards behind a Pennant feature flag? Activate the admin toggle
  (`Feature::for(User::find(1))->activate(<class>)`) and remember per-user resolved values
  are cached in the `features` table; `Feature::for(null)` covers anonymous headless checks.

See also: [[generating-thumbnails]], [[worktree-docker]].
