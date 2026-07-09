---
name: headless-browser-verify
description: Verify keystone.guru pages in a real headless Chrome - reproduce rendering/JS issues, measure layout, catch page errors, and take screenshots you can view with the Read tool (and post onto a PR). Use when curl/static reasoning is not enough (JS-driven UI, bootstrap-select menus, layout/sizing bugs, "does this actually render" questions). Works from the main checkout or any worktree via its Docker stack.
---

# Headless browser verification (keystone.guru)

Drive the site in a real headless Chrome — via the `chrome` compose service where the branch has
it, or a locally-launched chrome-headless-shell otherwise. The driver script runs inside the `app`
container, where the site is `http://nginx`; screenshots land in the bind-mounted checkout so they
can be viewed with the Read tool. Proven useful for: bootstrap-select sizing bugs, JS crashes on
specific pages, layout verification after CSS changes, catching the FA7 missing-icon blowup
(MR #3481).

## Spin up (per stack, from the worktree/checkout root)

Preferred: the `chrome` compose service (chromedp/headless-shell, defined in
`docker-compose.worktree.yml` behind the `chrome` profile). It ships with the Bootstrap v4→v5
migration (#3397 / MR #3419) and reaches master when that merges — check
`grep chrome docker-compose.worktree.yml`; if the branch has it:

```sh
docker compose --profile chrome up -d chrome   # profile-gated: normal stack is unaffected
mkdir -p .chrome-tmp && cp .claude/skills/headless-browser-verify/browse.js .chrome-tmp/
```

No published ports, so every worktree stack can run its own chrome simultaneously.

Until #3397 merges, branches cut from master don't have the service — use the local-launch
fallback instead: copy the chrome-headless-shell binary from the host puppeteer cache and install
its runtime deps in the app container; browse.js picks the binary up automatically. Takes ~1
minute:

```sh
mkdir -p .chrome-tmp && cp .claude/skills/headless-browser-verify/browse.js .chrome-tmp/
cp -r ~/.cache/puppeteer/chrome-headless-shell/linux-*/chrome-headless-shell-linux64 .chrome-tmp/
docker compose exec -T -u root app sh -c 'apt-get update -qq && apt-get install -yqq \
    libnspr4 libnss3 libasound2t64 libatk-bridge2.0-0t64 libatk1.0-0t64 libatspi2.0-0t64 \
    libcairo2 libcups2t64 libdbus-1-3 libexpat1 libgbm1 libglib2.0-0t64 libpango-1.0-0 \
    libvulkan1 libxcomposite1 libxdamage1 libxfixes3 libxkbcommon0 libxrandr2 fonts-liberation'
```

(Deps use Debian 13/trixie `t64` names; older releases use the non-`t64` names. The deps do not
survive container recreation — re-run the apt-get after `worktree.sh create`.)

## Running

```sh
docker compose exec -T app sh -c 'cd /var/www && node .chrome-tmp/browse.js http://nginx/search \
    --screenshot /var/www/.chrome-tmp/shot.png \
    --click ".affixselect.bootstrap-select button" \
    --eval "document.querySelectorAll(\".dropdown-menu.show li\").length"'
```

- Output is JSON: `status`, `chrome` (`local launch ...` with the fallback binary, `service` when
  a chrome compose service is used), `pageErrors` (uncaught JS), `consoleErrors`, `evalResult`.
  Exit 1 on HTTP >= 400 or any page error - a bare run doubles as a smoke test.
- View the screenshot with the Read tool at `<worktree>/.chrome-tmp/shot.png`.
- `--eval` takes a JS expression evaluated in the page (JSON-serializable result). For complex
  measurements/flows write a bespoke script next to browse.js (require puppeteer, copy the
  `connectToService` helper from browse.js).
- `--mobile` switches to an iPhone viewport/UA; the site renders a different (sidebar-less) layout
  for mobile UAs - and curl without a desktop UA is treated as mobile too (a trap when grepping for
  sidebar markup).
- `puppeteer` resolves from `/var/www/node_modules` (run with cwd `/var/www`).

## Baseline comparison — always A/B against master for visual changes

A screenshot judged in isolation only catches *missing* elements, not *changed* ones. "Icons
render, no console errors" passed MR #3481's first review while icons were rendering at 10x size;
a same-page master-vs-branch comparison exposed it in one look. Screenshot comparison is also the
cheapest reviewer handoff there is: Wotuu can eyeball a before/after pair instantly at zero cost,
so **post both** to the PR, not just the branch shot.

- The master baseline needs no chrome setup of its own: the main stack's nginx publishes a host
  port (check `docker ps | grep nginx`, e.g. `8008`), reachable from inside the worktree's app
  container via the Docker bridge gateway — run browse.js against `http://172.17.0.1:8008/<page>`
  for master and `http://nginx/<page>` for the branch, same viewport.
- Check the baseline is actually master: the page footer shows the built commit
  (`v15.3.2 (05d7dc)`); if the main checkout's assets are stale, rebuild there first.
- **Exercise dynamic UI, not just page load.** JS-inserted content (map sidebar, dropdown menus)
  breaks differently from server-rendered HTML — FA7's icon blowup only hit dynamically-inserted
  elements. Use `--click` and screenshot the opened/expanded state on both sides.
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

- **Auth**: guest pages only, unless you inject a session cookie via a bespoke script
  (`page.setCookie(...)` with a value taken from a logged-in browser).
- **Cleanup**: `rm -rf .chrome-tmp` before finishing a task; it must never be committed
  (untracked, but `git add -A` would grab it). Files written by the container are root-owned, so
  remove them from inside the container first
  (`docker compose exec -T -u root app rm -rf /var/www/.chrome-tmp`). Stop chrome with
  `docker compose --profile chrome stop chrome` if you want it gone.
- If a chrome compose service is used instead of the local-launch binary, it needs
  `shm_size: 1gb` - with the compose default 64MB /dev/shm, image-heavy pages fail with
  `net::ERR_INSUFFICIENT_RESOURCES`.
