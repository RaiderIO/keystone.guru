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

## Baseline comparison — always A/B against master for visual changes

A screenshot judged in isolation only catches *missing* elements, not *changed* ones. "Renders, no
console errors" can pass while an element is sized or positioned wrong; a same-page
master-vs-branch comparison exposes that in one look. Screenshot comparison is also the cheapest
reviewer handoff there is: Wotuu can eyeball a before/after pair instantly at zero cost, so **post
both** to the PR, not just the branch shot.

- The master baseline needs no chrome setup of its own: the main stack's nginx publishes a host
  port (check `docker ps | grep nginx`, e.g. `8008`), reachable from inside the worktree's app
  container via the Docker bridge gateway — run browse.js against `http://172.17.0.1:8008/<page>`
  for master and `http://nginx/<page>` for the branch, same viewport.
- Check the baseline is actually master: the page footer shows the built commit
  (`v15.3.2 (05d7dc)`); if the main checkout's assets are stale, rebuild there first.
- **Exercise dynamic UI, not just page load.** JS-inserted content (map sidebar, dropdown menus)
  breaks differently from server-rendered HTML, and a regression can hit only the
  dynamically-inserted elements. Use `--click` and screenshot the opened/expanded state on both
  sides.
- **A screenshot anomaly is unexplained until DOM-inspected.** Do not attribute odd content to
  "dev environment artifact" until you have either inspected the element (`--eval` with
  `getComputedStyle`/`outerHTML`) or confirmed the identical artifact on the master baseline.
  Known signature: a question mark in a dashed circle is FontAwesome's missing-icon fallback (an
  icon-name or FA-JS-runtime problem), never a site placeholder.

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

- **Auth**: guest pages only, unless you log in first with a bespoke script that submits the
  `/login` form (`input[name="email"]` / `input[name="password"]`) — the session cookie then
  persists in the warm service browser for later browse.js runs on that origin. Log in separately
  per origin (`http://nginx` and the master baseline are different origins). Use a throwaway user
  (create via tinker, `addRole` for admin, set `legal_agreed` to skip the legal modal; delete when
  done).
- **Site assets**: pages reference images/tiles at `ASSETS_BASE_URL` (`http://localhost:8009`, a
  host-published port that does not exist inside containers). The `chrome` service entrypoint in
  `docker-compose.worktree.yml` forwards `127.0.0.1:8009` to the shared `app-assets` container, so
  these load in screenshots — for the master baseline too, which emits the same URLs. If site
  images render as broken glyphs, the chrome service was probably started from a checkout that
  predates that forward.
- **Cleanup**: `rm -rf .chrome-tmp` before finishing a task; it must never be committed
  (untracked, but `git add -A` would grab it). Files written by the container are root-owned, so
  remove them from inside the container first
  (`docker compose exec -T -u root app rm -rf /var/www/.chrome-tmp`). Stop chrome with
  `docker compose --profile chrome stop chrome` if you want it gone.
- The `chrome` service needs `shm_size: 1gb` - with the compose default 64MB /dev/shm, image-heavy
  pages fail with `net::ERR_INSUFFICIENT_RESOURCES`.
