---
name: generating-thumbnails
description: The two — and only two — supported ways for an agent to generate a dungeon-route thumbnail. Use whenever you need to (re)generate, render, or inspect a route thumbnail, or when a thumbnail is missing/broken/403ing. Path B dispatches to shared Horizon (real thumbnails, visible everywhere); Path A renders your branch's code locally to inspect it. Do NOT hand-roll puppeteer, edit ThumbnailService guards, apt-install Chrome, or symlink public/storage.
---

# Generating dungeon-route thumbnails

There are **exactly two** supported ways to make a thumbnail. Pick by intent. Do not invent a third
(no manual puppeteer, no `ThumbnailService` guard edits, no `.chrome-tmp` Chrome installs, no
`public/storage` symlink hacks — those are obsolete and cause the drift this skill exists to end).

**Why the rules exist:** Chrome/puppeteer is baked into the `keystone.guru-worker` image **only**
(the `app`/`app-swoole`/worktree `app` containers deliberately have no Chrome). So calling
`ThumbnailService::createThumbnail()` synchronously in the `app` container always fails with
`Could not find Chrome`. And because the **database is shared** across the main stack and every
worktree, any thumbnail row you persist is seen everywhere — so a worktree must never write a row
pointing at a worktree-local file.

## Path B — "I just need real thumbnails to exist" (default)

Dispatch the queued job. The shared main-stack **Horizon** container (the only one with Chrome)
renders to the **`public`** disk, which is bind-mounted into every worktree and served at
`/storage/...`. The shared DB means the new rows/files show up on the main stack and all worktrees.

```
docker compose exec -T app php artisan dungeonroute:queuethumbnail <publicKey> --force
```

- Works from the main checkout or any worktree (dispatch only needs Redis; Horizon does the render).
- Uses **master's** code (Horizon runs the main checkout), so it does NOT reflect uncommitted branch
  changes to the render/preview — use Path A for that.
- Give Horizon a few seconds, then verify: the route's thumbnail row is on the `public` disk and
  `curl -o /dev/null -w '%{http_code}' http://nginx/storage/<path>` returns `200`.
- Bulk equivalents already exist: `dungeonroute:refreshoutdatedthumbnails`, the admin "regenerate"
  tool, and `ThumbnailService::queueThumbnailRefresh()` in code.

## Path A — "verify how MY branch renders a thumbnail" (worktree-isolated)

Render this checkout's code in an on-demand container built from the `keystone.guru-worker` image
(the only image with Chrome), writing to the **`local`** disk (`storage/app/private`), then **Read
the resulting `.jpg`** with your file tool. It does **NOT** touch the shared database (the render
runs inside a transaction that is always rolled back), so it can't break the route elsewhere.

```
docker compose --profile render run --rm render dungeonroute:renderthumbnail <publicKey> --floor=<n>
```

- Only in a worktree (needs the `render` service from `docker-compose.worktree.yml`). Requires the
  main stack to be up so the `keystone.guru-worker` image exists.
- The command prints the path(s) it wrote, e.g. `storage/app/private/thumbnails/<key>/<file>.jpg` —
  open that file to inspect the render. Nothing is persisted to the DB or the shared public disk.
- `--floor` is optional (defaults to the map-facade floor set). `--disk` defaults to `local`; keep
  it — writing to `public` from a worktree would land in the shared, bind-mounted dir.

**Local renders show the DebugBar and black (missing) map tiles** — the local `app-assets` container
has no tiles and dev has the DebugBar on. This is identical for Path A and Path B (same render), so
it's fine for verifying layout/enemies/positioning. If you need a tile-accurate, DebugBar-free
image, that's an env concern affecting both paths, not a Path A bug.

## Just want to eyeball the visual, not a real thumbnail file?

The thumbnail is a screenshot of the `dungeonroute.preview` page. To check only how your branch
renders that page, use the **headless-browser-verify** skill against the preview URL — no
`ThumbnailService`, no disk, no DB.

## Disks & serving (reference)

- `public` disk → `storage/app/public`, served statically via the `storage:link` symlink at
  `/storage/...` (no signature). This is where real thumbnails live; the main stack's
  `storage/app/public/thumbnails` is bind-mounted into every worktree (`sh/worktree.sh`).
- `local` disk → `storage/app/private`, worktree-isolated. Its `/storage/{path}` route is
  **signed-URL-gated** (403 without a signature) — do **not** try to serve Path A output over HTTP
  or re-point `public/storage` at it (that conflicts with `storage:link`). Just Read the file.
- `dungeonroute:migratethumbnaildisk` is a separate dev-only one-off that moves legacy `local`-disk
  rows onto the `public` disk; unrelated to day-to-day generation.

See also: [[worktree-docker]], [[headless-browser-verify]].
