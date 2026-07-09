---
name: headless-browser-verify
description: Verify keystone.guru pages in a real headless Chrome - reproduce rendering/JS issues, measure layout, catch page errors, and take screenshots you can view with the Read tool (and post onto a PR). Use when curl/static reasoning is not enough (JS-driven UI, bootstrap-select menus, layout/sizing bugs, "does this actually render" questions). Works from the main checkout or any worktree via its Docker stack.
---

# Headless browser verification (keystone.guru)

Drive the site in a real Chrome via the `chrome` compose service (chromedp/headless-shell, defined
in `docker-compose.worktree.yml` behind the `chrome` profile). The driver script runs inside the
`app` container, where the site is `http://nginx`; screenshots land in the bind-mounted checkout so
they can be viewed with the Read tool. Proven useful for: bootstrap-select sizing bugs, JS crashes
on specific pages, layout verification after CSS changes.

## Spin up (per stack, from the worktree/checkout root)

```sh
docker compose --profile chrome up -d chrome   # profile-gated: normal stack is unaffected
mkdir -p .chrome-tmp && cp .claude/skills/headless-browser-verify/browse.js .chrome-tmp/
```

No published ports, so every worktree stack can run its own chrome simultaneously.

## Running

```sh
docker compose exec -T app sh -c 'cd /var/www && node .chrome-tmp/browse.js http://nginx/search \
    --screenshot /var/www/.chrome-tmp/shot.png \
    --click ".affixselect.bootstrap-select button" \
    --eval "document.querySelectorAll(\".dropdown-menu.show li\").length"'
```

- Output is JSON: `status`, `chrome` (should say `service`), `pageErrors` (uncaught JS),
  `consoleErrors`, `evalResult`. Exit 1 on HTTP >= 400 or any page error - a bare run doubles as a
  smoke test.
- View the screenshot with the Read tool at `<worktree>/.chrome-tmp/shot.png`.
- `--eval` takes a JS expression evaluated in the page (JSON-serializable result). For complex
  measurements/flows write a bespoke script next to browse.js (require puppeteer, copy the
  `connectToService` helper from browse.js).
- `--mobile` switches to an iPhone viewport/UA; the site renders a different (sidebar-less) layout
  for mobile UAs - and curl without a desktop UA is treated as mobile too (a trap when grepping for
  sidebar markup).
- `puppeteer` resolves from `/var/www/node_modules` (run with cwd `/var/www`).

## Post the screenshot to a GitHub PR/issue

To make the browser output visible to reviewers, embed the screenshot in the PR body. GitHub has no
image-upload API, but on this **public** repo an image renders in markdown from any raw URL. The
`post-screenshot.sh` helper hosts the PNG on a dedicated orphan branch (`verification-screenshots`)
that carries no code and is never merged, then prints the raw URL:

```sh
URL=$(.claude/skills/headless-browser-verify/post-screenshot.sh .chrome-tmp/shot.png pr/3471-search.png)
# then embed in the PR body / a comment:
#   ![verification](<URL>)
gh pr comment <pr> --repo RaiderIO/keystone.guru --body "$(printf '![verification](%s)' "$URL")"
```

- The first call creates the orphan branch; later calls append to it (one file per PR, e.g.
  `pr/<n>-<page>.png`). The branch accumulates artifacts and is never merged into `master`, so the
  PR's own diff stays clean.
- Requires `gh` (authenticated), `jq`, `base64`. Public repo only — private-repo raw URLs do not
  render in GitHub markdown.
- Verify it landed: `curl -s -o /dev/null -w '%{http_code}' <URL>` should print `200`.

## How the connection works (for bespoke scripts)

Chrome's DevTools endpoint rejects `Host:` headers that are not an IP or localhost. browse.js
resolves the `chrome` service name to its IP, fetches `/json/version` from the IP, rewrites the
`webSocketDebuggerUrl` to that IP, and uses `puppeteer.connect`. Disconnect (do not close) so the
browser stays warm for the next run.

## Gotchas

- **Auth**: guest pages only, unless you inject a session cookie via a bespoke script
  (`page.setCookie(...)` with a value taken from a logged-in browser).
- **Cleanup**: `rm -rf .chrome-tmp` before finishing a task; it must never be committed
  (untracked, but `git add -A` would grab it). Files written by the container are root-owned, so
  remove them from inside the container first
  (`docker compose exec -T -u root app rm -rf /var/www/.chrome-tmp`). Stop chrome with
  `docker compose --profile chrome stop chrome` if you want it gone.
- `shm_size: 1gb` on the service is required - with the compose default 64MB /dev/shm, image-heavy
  pages fail with `net::ERR_INSUFFICIENT_RESOURCES`.
- The main checkout's docker-compose.yml has no chrome service (only docker-compose.worktree.yml);
  for the main stack either add one locally or run a worktree.
- The compose service arrived with the Bootstrap 5 migration branch (#3397 / MR #3419); on branches
  that predate it, worktree.sh copies docker-compose.worktree.yml from the main repo checkout only
  if the branch lacks the file entirely - otherwise use the launch fallback: download
  chrome-headless-shell into `.chrome-tmp/` and apt-install its deps in the app container
  (libnspr4 libnss3 libasound2t64 libatk-bridge2.0-0t64 libatk1.0-0t64 libatspi2.0-0t64 libcairo2
  libcups2t64 libdbus-1-3 libexpat1 libgbm1 libglib2.0-0t64 libpango-1.0-0 libvulkan1
  libxcomposite1 libxdamage1 libxfixes3 libxkbcommon0 libxrandr2 fonts-liberation); browse.js falls
  back to `/var/www/.chrome-tmp/chrome-headless-shell-linux64/chrome-headless-shell` automatically.
  (On Debian 13 / trixie the `t64` package names above apply; older releases use the non-`t64`
  names.)
