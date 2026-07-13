# Local E2E: dungeon route creation flow

`create-route.e2e.js` drives the **real** create-route flow in a real headless Chrome: real
`/login` form, real game-version switcher, real Tom Select UI (see
`resources/assets/js/selectpicker.js`). This is the kind of coverage a jsdom/vitest contract test
cannot give you - it catches the browser actually producing a malformed POST body after the JS
runs, which is exactly what went wrong in v15.4.0 (issue #3514: Tom Select injected
`dungeon_difficulty=""` and every route creation failed).

This script is **not run in CI**. It's a manual, local verification tool - run it after touching
anything in the create-route path (dungeon select, difficulty select, start select, affixes,
composition, the game version switcher) to catch regressions a unit test would miss.

It also runs a **bonus, non-fatal probe** at the end that reproduces the "stale difficulty"
bug tracked in issue #3535: `dungeondifficultyselect.js` only *hides* the difficulty container
when you switch from a speedrun dungeon to a non-speedrun one - it never clears the underlying
`<select>`'s options/value - so the hidden select still submits its last value. The probe prints
what `new FormData(form)` would actually submit in that situation.

## Prerequisites

1. The stack's `app`/`nginx` containers must be running (worktree or main checkout).
2. The frontend assets must be built and reflect the current source. If `resources/assets/js/**`
   changed since the last build, the served bundle can be stale (this bit us once during
   development: the served bundle still had the pre-Tom-Select bootstrap-select code, so
   `.ts-wrapper` never appeared). Rebuild with `npm run development` (faster, fine for local
   verification) or `npm run production`/`composer run dev` if you specifically need the
   production bundle. Ask the user if unsure which one they want.
3. The `chrome` compose service must be up (profile-gated, so it doesn't affect the normal stack):

   ```sh
   docker compose --profile chrome up -d chrome
   ```

4. A user account with role `user` or `admin`. The `LaratrustSeeder` (local-only, guarded by
   `config('app.type') === 'local'`) creates one per role with a fixed password, e.g.
   `user@app.com` / `password`. Check `database/seeders/LaratrustSeeder.php` before creating a new
   throwaway user - there's usually already one seeded.

## Running

From the worktree/checkout root, on the host:

```sh
docker compose exec -T app sh -c 'cd /var/www && node sh/e2e/create-route.e2e.js \
    --email user@app.com --password password'
```

Options:

| Flag           | Required | Description                                                        |
|----------------|----------|----------------------------------------------------------------------|
| `--email`      | yes      | Login email of an existing `user`/`admin` account.                    |
| `--password`   | yes      | Password for that account.                                            |
| `--base-url`   | no       | Site origin as seen from inside the `app` container. Default `http://nginx`. |
| `--keep`       | no       | Skip deleting the routes this script creates (they normally clean up after themselves). |

`CHROME_HOST`/`CHROME_PORT` env vars override the chrome service host/port (default
`chrome`/`9222`), same convention as `browse.js`.

If the `chrome` CDP endpoint isn't reachable, the script fails fast with a message telling you to
start it - it does **not** silently fall back to a locally-launched Chrome (unlike `browse.js`),
so a missing `chrome` service can't quietly change what's actually being tested.

## What it does

1. Clears cookies on the warm chrome-service browser (it stays alive between runs - see
   `.claude/skills/headless-browser-verify/SKILL.md` - so a previous run's session, possibly for a
   different user, must not leak into this one) and logs in via the real `/login` form.
2. Scrapes the game-version switcher in the header (`.game_version_header .game_version a`) to
   find the current version and the Retail/Classic links - **no game version IDs are hardcoded**.
3. **Retail flow**: switches to Retail if needed, opens `/new`, picks a dungeon by opening the Tom
   Select dropdown and clicking an option, fills the title, submits, asserts the redirect landed on
   `/route/{dungeon}/{key}/{title}/edit`, then deletes the created route via
   `DELETE /ajax/{public_key}` (same endpoint the profile table's delete button uses - see
   `resources/assets/js/custom/inline/dungeonroute/table.js` `_promptDeleteDungeonRouteClicked`),
   using the CSRF token off the page's own `<meta name="csrf-token">`.
4. **Classic flow**: switches to Classic, opens `/new`, live-searches "Serpentshrine" in the Tom
   Select dropdown, asserts it narrows to exactly Serpentshrine Cavern, asserts the
   `#dungeon_difficulty_select_container` becomes visible with a 25-man option (this dev DB only
   has 25-man enabled for SSC, not 10-man), picks it, submits, asserts the redirect, cleans up.
5. **Stale-difficulty probe** (informational, does not affect the exit code): on a fresh `/new` in
   Classic, selects SSC, picks a difficulty, then switches to a non-speedrun classic dungeon
   (Blackfathom Deeps) and prints what `new FormData(form)` would submit - showing the container
   is hidden but `dungeon_difficulty` is still present in the form data. See issue #3535.
6. Restores the user's original game version, **even on failure** (`try`/`finally`) - this mutates
   a shared dev-DB user's `game_version_id` column, so leaving it switched would surprise whoever
   uses that account next.
7. Prints a structured JSON summary (same shape as `browse.js`'s output: per-step pass/fail, the
   created route URLs/keys, page/console errors) and exits non-zero if any assertion failed.

## Gotchas

- **Tom Select's open-on-click was flaky under Puppeteer's synthetic mouse click** in this
  environment - same selector, same wait, intermittently never opened the dropdown, no visible
  cause. Dispatching a real `.click()` via `page.evaluate()` was reliable across dozens of manual
  runs and still calls the same handlers Tom Select registers - see the `nativeClick()` helper.
  If you extend this script, prefer `nativeClick()` over `page.click()` for anything Tom-Select
  related.
- **The cookie consent banner** (`.cc-window`, fixed-bottom, from the cookieconsent2 CDN script)
  intercepts clicks aimed at whatever's underneath it - notably the login submit button - and
  reappears on every fresh page load since it's dismissed via a cookie we never set. The script
  removes it from the DOM after every navigation (`dismissCookieBanner()`).
- **`document.querySelector('form')` is not safe on this site** - there's usually a hidden
  logout/modal form earlier in the DOM. The script always anchors off
  `document.querySelector('#dungeon_id_select').closest('form')` to get the actual create-route
  form.
- **`waitUntil: 'networkidle2'` hangs** on this site (persistent WebSocket connections keep the
  network "busy" forever) - the script uses `waitUntil: 'load'` throughout, same as `browse.js`.
