---
name: new-machine-setup
disable-model-invocation: true
description: Bring up a working keystone.guru dev environment on a brand-new machine, from Docker installed to worktrees + tests running.
---

# New machine setup

**Run this with Opus 5.** This is environment debugging — phantom bind mounts, UID mismatches,
compose-plugin panics, half-initialised MySQL data dirs. It is not a deterministic script, and a
cheaper model will burn an hour re-running `docker compose up` and hoping. Do not delegate the
phases to subagents either: the failure modes are cross-cutting and you need the whole picture.

**Bootstrap paradox:** this file lives inside the repo it tells you to clone. On the new machine,
clone the repo first (phase 2), then point Claude at this file:

> Read `.claude/skills/new-machine-setup/SKILL.md` and follow it.

## Assumptions

- Docker is installed and `docker run hello-world` works.
- The machine is Linux, or Windows + WSL2 with the repo living **inside** the WSL filesystem
  (`/home/<user>/...`, never `/mnt/c/...` — bind-mount I/O across the 9p boundary is unusably slow).
- You have the transfer bundle from phase 0. Without it, stop.

Work top to bottom. Every phase ends in a **probe** — an explicit pass/fail command. Do not move on
from a failed probe, and do not declare the machine ready without running phase 9 in full.

**This document was derived by reading the repo on a working machine, not by executing it on a clean
one.** Treat a failed probe as "this doc may be wrong here" at least as readily as "I ran it wrong" —
read the referenced source (`docker-compose.yml`, `docker-compose/app/Dockerfile`, `sh/worktree.sh`,
`config/database.php`) and trust it over this file. When you find a discrepancy, **fix this file** in
the same session; you are the last person who will have the context to.

---

## Phase 0 — The transfer bundle (do this on the OLD machine)

Six files are gitignored, secret, and **cannot be regenerated or reconstructed**. If the old machine
is gone before these are copied off it, the environment cannot be rebuilt without reissuing every
credential by hand. On top of these six, `~/Git/private/keystone.guru.assets/tiles/` (~36G,
gitignored — see phase 2) is *also* unrecoverable: it isn't in any repo and neither is the software
that generates it (`readme.md:59-61`). It doesn't need "reissuing" like a credential, but if the old
machine is gone before it's copied or synced, it is simply gone.

| File | Why it is unrecoverable |
|---|---|
| `.env` (repo root) | ~200 keys: `APP_KEY`, `GITHUB_ACCESS_TOKEN`, `MAILGUN_*`, `AWS_*`, `CHATGPT_API_KEY`, `RAIDERIO_WEBHOOK_*`, `COMBAT_LOG_ROUTE_REGENERATION_*`, `THUMBNAIL_PREVIEW_SECRET`, `REVERB_APP_SECRET`. `.env.docker.example` has the *shape* but none of the values. |
| `docker-compose/app/.env` | One line: `GIT_TOKEN=…`. The app `Dockerfile` does `COPY .env /tmp/.env` and envsubsts it into composer's `auth.json`. **Without it `docker compose build app` fails outright**, and nothing in a fresh clone hints at why. |
| `~/.ssh/wotuu_passwordless{,.pub}` | Passphraseless GitHub auth. Every `git push`/`fetch` from an agent session depends on it. |
| `~/.ssh/keystone_worktree_ed25519{,.pub}` | Scoped write deploy key for `sh/worktree.sh push`. The script hard-dies without it (`worktree.sh:674`). |
| `~/.ssh/git_signing_ed25519{,.pub}` + `allowed_signers` | Commit/tag signing — `commit.gpgsign=true` is global, so commits *fail* without it. |
| `~/.ssh/config` | Pins `github.com` → `wotuu_passwordless`. |

Nice-to-have, trivially recreated from this file if lost: `~/.claude/settings.json`,
`.claude/settings.local.json`, `~/.config/git/ignore`.

The `keystone-guru-bot` credential used by `sh/gh-bot.sh` (#3924) sits between the two: either
`~/.config/gh-bot/hosts.yml` (an OAuth login — the preferred route, no PAT involved) or
`~/.config/keystone-guru/bot-gh-token` (a fine-grained PAT). Copying one across is easier than
redoing it, but both are recreatable from the bot account, and nothing hard-fails without them —
agents fall back to plain `gh` with a `:robot:`-prefixed body. Re-create per the `worktree-docker`
skill, "Posting to GitHub as the bot account".

**Do not copy the MySQL data dirs** (`docker-compose/mysql*`, ~800M). Phase 7 rebuilds them from
the tracked schema dumps + seeders in ~10–15 minutes.

**Do not copy `~/.claude/.credentials.json`** — just run `claude` on the new machine and log in.

Copy the bundle somewhere private (password manager, encrypted USB, personal cloud). Never into the
repo, never into a gist, never into an artifact.

**Probe:** the bundle contains both `.env` files and all four SSH key pairs.

---

## Phase 1 — Host prerequisites

Beyond Docker:

```bash
sudo apt-get update && sudo apt-get install -y git curl unzip python3 mysql-client
```

- **python3** — the Claude Code statusline (`.claude/statusline/statusline.sh`) pipes JSON through
  it. Missing python3 = broken statusline, nothing worse.
- **Node via nvm**, matching `.nvmrc` (currently `24`). Node is needed **on the host**, not just in
  the container, because `npm run production` is run host-side:
  ```bash
  curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh | bash
  # reopen shell, then from the repo root:
  nvm install && nvm use
  ```
- **A modern `gh`.** Distro packages are far too old — `gh pr edit` needs ≥ 2.96.0 on this repo; the
  apt 2.4.0 at `/usr/bin/gh` fails with a Projects-classic GraphQL error (check `gh --version` if you
  see that error). Install into `~/.local/bin` so it shadows any `/usr/bin/gh`:
  ```bash
  mkdir -p ~/.local/bin && cd /tmp
  curl -sSL https://github.com/cli/cli/releases/latest/download/gh_$(curl -s https://api.github.com/repos/cli/cli/releases/latest | grep -Po '"tag_name": "v\K[^"]*')_linux_amd64.tar.gz | tar xz
  cp gh_*/bin/gh ~/.local/bin/ && hash -r
  gh auth login   # interactive — the user runs this, not you
  ```

**Which Docker backend?** Ask, don't assume. Docker Desktop and Rancher Desktop both work; Rancher
has extra failure modes (phase 10). If Rancher: check `docker compose version` ≥ 5.1.0 — the
bundled 5.0.1 has a nil-pointer panic in `monitor.Start` that kills `docker compose up`.

**WSL memory:** the default VM takes 50% of host RAM and this stack (MySQL ×2, valkey, php-fpm,
swoole, reverb, nginx) will thrash it. Write `C:\Users\<user>\.wslconfig`:

```ini
[wsl2]
memory=12GB
swap=16GB
autoMemoryReclaim=gradual
```

Takes effect only after `wsl --shutdown` — the user must do that themselves.

**Probe:** `docker compose version` ≥ 5.1.0, `node -v` matches `.nvmrc`, `gh --version` ≥ 2.96.0,
`python3 -V` works.

---

## Phase 2 — SSH keys, then clone

The clone below is over `git@github.com:` (a private repo), and phase-4 git config turns on
`commit.gpgsign true` globally — so the SSH auth key and the signing key both have to exist
**before** cloning, not after. Restore them here, first, rather than waiting for the "Restore
secrets" phase:

```bash
mkdir -p ~/.ssh && chmod 700 ~/.ssh
cp <bundle>/ssh/* ~/.ssh/
chmod 600 ~/.ssh/wotuu_passwordless ~/.ssh/keystone_worktree_ed25519 ~/.ssh/git_signing_ed25519
chmod 644 ~/.ssh/*.pub ~/.ssh/config ~/.ssh/allowed_signers
```

`~/.ssh/config` must contain:

```
Host github.com
    HostName github.com
    IdentityFile ~/.ssh/wotuu_passwordless
    IdentitiesOnly yes
```

**Probe:** `ssh -T git@github.com` returns `Hi Wotuu! You've successfully authenticated…` with no
passphrase prompt.

Now clone. Two repos, **siblings in the same parent directory**. This is not cosmetic:
`docker-compose.yml` bind-mounts `../keystone.guru.assets` into both the `app` and `app-assets`
services, and `create_host_path: false` is not set on that mount — a missing sibling gives you an
empty dir and silently broken images.

```bash
mkdir -p ~/Git/private && cd ~/Git/private
git clone git@github.com:RaiderIO/keystone.guru.git
git clone git@github.com:RaiderIO/keystone.guru.assets.git   # ~342M tracked (images, webfonts)
```

`sh/worktree.sh` also expects `~/Git/private/keystone.guru-worktrees/` as its worktree root; it
creates that itself on first use.

**Check `~/Git` and `~/Git/private` are owned by you, not root.** If an earlier attempt cloned the
repo under `sudo`, the parent dirs end up `root:root` while `keystone.guru` itself looks correctly
owned — so nothing is obviously wrong until the second clone fails with
`could not create work tree dir 'keystone.guru.assets': Permission denied`. That failure is easy to
miss because it is routinely piped (`git clone … | tail`), and a pipe reports the *tail* exit code,
so the clone reads as success. Worse, the missing sibling does not stay missing: the Docker daemon
runs as root and will happily create `../keystone.guru.assets` as a root-owned empty dir for the
bind mount, which is exactly the silent-broken-images failure above. Fix ownership before cloning:

```bash
sudo chown -R "$(id -u):$(id -g)" ~/Git
# no sudo available? the daemon runs as root, so borrow it:
docker run --rm -v ~/Git:/git alpine chown -R "$(id -u):$(id -g)" /git
```

The clone above only brings `images/` and `webfonts/`. The checkout also has a `tiles/` directory
(~36G, gitignored per `keystone.guru.assets/.gitignore:1`) that is **not** part of the git history —
map tiles have to be transferred separately (rsync/external drive/etc. from the old machine). See
the tiles note in phase 4 for what happens if you skip this.

Git config (global — replicate from the old machine):

```bash
git config --global core.autocrlf true          # harmless: .gitattributes forces eol=lf anyway
git config --global push.autosetupremote true
git config --global gpg.format ssh
git config --global user.signingkey ~/.ssh/git_signing_ed25519.pub
git config --global gpg.ssh.allowedsignersfile ~/.ssh/allowed_signers
git config --global commit.gpgsign true
git config --global tag.gpgsign true
gh auth setup-git
```

Per-repo identity (`user.name` / `user.email` are set **locally** on the old machine, not globally):

```bash
cd ~/Git/private/keystone.guru
git config user.name  "Wotuu"
git config user.email "wouter.koppenol@gmail.com"
```

And the global ignore that keeps agent-local permission files out of every repo:

```bash
mkdir -p ~/.config/git && echo '**/.claude/settings.local.json' >> ~/.config/git/ignore
```

**Probe:** `ls ~/Git/private` shows both `keystone.guru` and `keystone.guru.assets`;
`git -C ~/Git/private/keystone.guru log -1` works.

---

## Phase 3 — Restore remaining secrets

The SSH keys were already restored in phase 2 (they had to be, before the clone could happen). What's
left is the two `.env` files, which do need the repo cloned first since they're copied into it:

```bash
cd ~/Git/private/keystone.guru
cp <bundle>/env                  .env
cp <bundle>/app-env              docker-compose/app/.env
```

Note `~/.ssh` is bind-mounted read-write into the `app` container (`~/.ssh:/home/ksg/.ssh`), which
is how in-container composer reaches private VCS repos.

**Probe:** `.env` and `docker-compose/app/.env` both exist and are non-empty.

---

## Phase 4 — Per-machine `.env` edits

The restored `.env` is correct except for four things that are machine-specific:

1. **`PUID` / `PGID`** must match the new host user, or every file the container writes into the
   bind mount lands root-owned (breaks `git status`, and root-owned `storage/logs` puts php-fpm
   into a 504 exception loop):
   ```bash
   sed -i "s/^PUID=.*/PUID=$(id -u)/; s/^PGID=.*/PGID=$(id -g)/" .env
   ```
2. **`DB_DATA_VARIANT=`** must be **empty**. It selects the MySQL bind-mount source
   (`docker-compose/mysql${DB_DATA_VARIANT}`); `-prod` points at local restores of production dumps
   which will not exist on a fresh machine. `create_host_path: false` makes compose fail loudly
   rather than mount an empty dir, so a wrong value here shows up as a compose error, not silent
   data loss.
3. **`ASSETS_BASE_URL=https://assets.keystone.guru`** — see the tiles note below.
4. **`ASSETS_BASE_URL_INTERNAL`** — changing `ASSETS_BASE_URL` alone is not enough.
   `config/keystoneguru.php:21` derives a separate `tiles_base_url_internal` from
   `ASSETS_BASE_URL_INTERNAL`, and `DungeonRouteController::preview()`
   (`app/Http/Controllers/DungeonRoute/DungeonRouteController.php:301-304`) swaps to that internal
   URL specifically for the server-side (puppeteer/thumbnail) render path when there's no
   authenticated user. `.env.docker.example:64` ships `ASSETS_BASE_URL_INTERNAL=http://app-assets`,
   which is correct for the default Docker Compose network — verify the restored `.env` has this
   set, or the phase-9 browser-render probe will silently render against a tile-less/asset-less
   container instead of erroring.

Also create the empty data dirs compose refuses to create for you:

```bash
mkdir -p docker-compose/mysql docker-compose/mysql-combatlog docker-compose/redis-data
```

### About map tiles

Map tiles themselves, and the software that generates them, are **not** in any repo
(`readme.md:59-61`: "Not included in this repository"). The `keystone.guru.assets` checkout does
hold a `tiles/` directory locally (~36G) — it's just gitignored, so cloning the repo does not bring
it; it has to be transferred from an existing machine by other means (phase 0/2). `config/
keystoneguru.php` derives `tiles_base_url` from `ASSETS_BASE_URL`, so with
`ASSETS_BASE_URL=https://assets.keystone.guru` the maps load tiles straight from the production CDN
regardless of whether `tiles/` was transferred locally — **everything renders correctly with no
local tiles**, since production CDN tiles are used either way.

The one consequence: `tests/Unit/MapTiles/MapTilesExistenceTest.php` asserts tile files exist on
disk under `../keystone.guru.assets/tiles/`. It only fails if `tiles/` was never transferred to this
machine — it is not expected to fail once tiles are present. If it fails and you don't intend to
transfer tiles here, that is fine to leave; do not "fix" the test itself.

**Probe:** `grep -E '^(PUID|PGID|DB_DATA_VARIANT|ASSETS_BASE_URL|ASSETS_BASE_URL_INTERNAL)=' .env`
shows your real uid/gid, an empty variant, the https assets URL, and `http://app-assets`.

---

## Phase 5 — Build the app image

```bash
docker compose build app
```

Expect **15–40 minutes**. The Dockerfile has three stages: a Rust stage that `cargo install`s the
WeakAuras parser from source, a PHP-extension builder that compiles redis/imagick/swoole/lua +
LuaBitOp, and the runtime image. Nothing is cached on a fresh machine.

Two things must be true or the build produces a subtly broken image:

- `docker-compose/app/.env` exists (phase 3) — otherwise `COPY .env /tmp/.env` fails.
- `PUID`/`PGID` are correct (phase 4) — they are passed as build args and bake the `ksg` user into
  the image. A wrong uid means containers run as a passwd-less user and you get
  `sudo: you do not exist in the passwd database`, which surfaces as **every MDT encode/decode test
  failing** rather than as an obvious permissions error.

**Probe:**
```bash
docker compose up -d app
docker compose exec -T app php -m | grep -E '^(lua|imagick|swoole|redis|pdo_mysql)$'   # all five
docker compose exec -T app id ksg                                                       # uid == id -u
docker compose exec -T app cli_weakauras_parser --help >/dev/null && echo "wa parser ok"
```

---

## Phase 6 — Dependencies and assets

Composer runs **in the container** (PHP 8.4 + ext-lua + ext-imagick live there, not on the host):

```bash
docker compose exec -T app composer install
```

npm runs **on the host**. `/public` and `version` are both gitignored, so the compiled assets do not
come with the clone — and `npm run production` is what writes `version`, which PHP reads in six
places (`ViewService`, the model-cache prefix, `MapContext`, `MakeHotfix`, …). A missing `version`
file breaks page rendering.

```bash
nvm use && npm ci && npm run production
```

`npm run production` can be memory-hungry; if WSL OOMs, `npm run development` produces a working
(unminified) build and is fine for dev.

**`npm ci` dies in puppeteer's postinstall if the host has no `unzip`** — the error is
`Failed to set up chrome-headless-shell … no zip archiver is available`, which reads like a
puppeteer/network problem rather than the missing phase-1 package it is. Install `unzip` (phase 1)
and re-run. To unblock without sudo, `PUPPETEER_SKIP_DOWNLOAD=1 npm ci` installs everything else —
asset builds do not need puppeteer's browser, and the in-container thumbnail path uses the worker
image's own Chrome. Run `npx puppeteer browsers install` once `unzip` is available.

**Probe:**
```bash
[ -s version ] && echo "version: $(cat version)"
# Assets are version-suffixed from the git rev — there is no unsuffixed app.js/app.css
ls public/js/app-$(cat version).js public/css/app-$(cat version).css
docker compose exec -T app php artisan --version
```

---

## Phase 7 — Database bootstrap

Both MySQL containers start with an **empty** bind-mounted data dir and initialise a blank server
from the `MYSQL_*` env vars in `docker-compose.yml`. You then load the tracked schema dumps, apply
pending migrations, and seed. This mirrors exactly what `sh/worktree.sh provision-db` does for
worktrees (`worktree.sh:190-266`) — follow that order.

```bash
docker compose up -d db db-combatlog redis
# wait for first-time init (~30-60s); watch for "ready for connections"
# (the phase 5 probe already started these via depends_on — then this is a no-op and
#  init has long finished)
docker compose logs -f db | grep -m1 "ready for connections"
```

**Database users are created for you — but verify it.** A fresh MySQL container creates exactly one
non-root user, from `MYSQL_USER`/`MYSQL_PASSWORD` (which compose feeds from `DB_USERNAME` /
`DB_PASSWORD` on `db`, and `DB_COMBATLOG_USERNAME` / `DB_COMBATLOG_PASSWORD` on `db-combatlog`).
The `migrate` connection authenticates as `DB_MIGRATION_USERNAME` / `DB_MIGRATION_PASSWORD`, and the
`phpunit` connection as `DB_PHPUNIT_USERNAME` / `DB_PHPUNIT_PASSWORD` — all of which are the *same*
`homestead` / `secret` in the current `.env`, which is the only reason this works out of the box:

```bash
grep -E '^DB_(USERNAME|PASSWORD|MIGRATION_USERNAME|MIGRATION_PASSWORD|PHPUNIT_USERNAME|PHPUNIT_PASSWORD|COMBATLOG_USERNAME|COMBATLOG_PASSWORD)=' .env
```

If any of them ever diverge, MySQL will not have created that user and `migrate` dies access-denied.
Create it by hand on **both** servers before continuing:

```bash
docker exec -i keystone.guru-db-prod sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -uroot' <<'SQL'
CREATE USER IF NOT EXISTS '<user>'@'%' IDENTIFIED BY '<pass>';
GRANT ALL PRIVILEGES ON `keystone.guru.dev`.* TO '<user>'@'%';
FLUSH PRIVILEGES;
SQL
```

(`sh/worktree.sh` never hits this because it grants to *every user that already has grants on the
main schema* — which presupposes those users exist. On a fresh machine, nobody made them.)

Load the squashed schema dumps (skips replaying years of migrations):

```bash
docker exec -i keystone.guru-db-prod           sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -uroot "$MYSQL_DATABASE"' < database/schema/migrate-schema.dump
docker exec -i keystone.guru-db-prod-combatlog sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -uroot "$MYSQL_DATABASE"' < database/schema/combatlog-schema.dump
```

Apply anything newer than the dumps, then seed:

```bash
docker compose exec -T app php artisan migrate --database=migrate --force
docker compose exec -T app php artisan migrate --database=combatlog --path=database/migrations_combatlog --force

# DungeonDataSeeder dominates — several minutes. Do not interrupt.
docker compose exec -T app php artisan db:seed --database=migrate --force

# Users are a SEPARATE seeder — the default seeder does not create them.
docker compose exec -T app php artisan db:seed --class=LaratrustSeeder --database=migrate
```

> `--class` is safe **here only** because `LaratrustSeeder::getAffectedModelClasses()` is empty, so
> it needs no `*_temp` staging tables. For any seeder that does have affected models —
> `DungeonDataSeeder` above all — `--class` bypasses `DatabaseSeeder`'s temp-table wrapper and
> leaves the database with committed deletes and nothing loaded back. Use
> `php artisan db:seedone --database=migrate <SeederClass>` instead; see the `seeder-load` skill.

`LaratrustSeeder` creates exactly three users: **1 = admin (`admin@app.com` / `password`)**,
2 = internal_team, 3 = user. Tests assume user id 1 is the admin, so skipping this step yields a
schema that looks fine and fails half the suite.

Note `DB_PHPUNIT_DATABASE` is the *same* schema as dev — there is no separate test database to
provision, and tests run against this seeded data (see the `writing-tests` skill).

**Probe:**
```bash
docker compose exec -T app php artisan tinker --execute 'echo \App\Models\User::count()." users, ".\App\Models\Dungeon::count()." dungeons";'
```
Expect 3 users and a few hundred dungeons. Zero dungeons = the seed did not finish.

---

## Phase 8 — Start everything

```bash
docker compose up -d
```

Brings up `app`, `app-swoole`, `app-assets`, `nginx`, `db`, `db-combatlog`, `redis`, `horizon`,
`reverb`, `cron`, `phpmyadmin*`, `php-redis-admin`. OpenSearch is **opt-in** and off by default —
it costs ~2.5GB of RAM; only start it with `docker compose --profile opensearch up -d` if a task
actually needs it.

Ports: site **8008**, assets 8009, reverb/swoole 9501, phpmyadmin 8010-ish, redis-admin 6381,
MySQL 34006 / 34007.

**Probe:** `curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8008` → `200`.

---

## Phase 9 — Acceptance (the real gate)

Do not report the machine as ready until all six pass. Fix failures here rather than noting them.

1. **Site renders**: `curl -s http://localhost:8008 | grep -q "Keystone.guru"`.
2. **A map renders**: use the `headless-browser-verify` skill against a dungeon explore page (e.g.
   `/explore/retail/court-of-stars`) and look at the screenshot with `Read`. Tiles must be visible
   (they come from the CDN), enemies must be plotted. A blank grey map means `ASSETS_BASE_URL` is
   wrong.

   That skill's `chrome` service is defined in `docker-compose.worktree.yml`, which the **main**
   checkout's `docker compose` does not load — `--profile chrome up -d chrome` there fails with
   "no such service". Run it standalone on the stack network instead (with
   `ASSETS_BASE_URL` on the CDN the socat asset forward is unnecessary):

   ```bash
   # The network is named <compose project>_<network>; derive it rather than hardcoding it.
   NET=$(docker network ls --format '{{.Name}}' | grep keystone)
   docker rm -f ksg-chrome 2>/dev/null   # idempotent: re-running would hit a name conflict
   docker run -d --rm --name ksg-chrome --network "$NET" \
       --network-alias chrome --shm-size 1gb chromedp/headless-shell:latest
   ```

   Tear it down with `docker rm -f ksg-chrome` when the probe is done. It is not part of the compose
   stack, so `docker compose down` leaves it running.

   Expect ~10 `Unable to find enemy patrol <id> that is coupled to enemy <id>` console errors on
   some dungeons. Those are stale cross-mapping-version references in the tracked seeder data
   (~53 of 7319 patrol-carrying enemies; zero patrols missing outright), not a bad seed — the map
   still plots every enemy. Do not chase them.
3. **Login works**: `admin@app.com` / `password`.
4. **Tests are green**:
   ```bash
   docker compose exec -T app php artisan test --compact --filter=MDTImportStringServiceDecodeTest
   ```
   This one specifically exercises ext-lua + the sudo/`ksg` path, so it catches a bad image build.
   Then a broader sweep: `docker compose exec -T app php artisan test --compact`. The only expected
   failure is `MapTilesExistenceTest`, and only if `keystone.guru.assets/tiles/` wasn't transferred
   to this machine (see phase 4) — if tiles are present, it should pass too.
5. **Toolchain**: `docker compose exec -T app composer run fix` and
   `docker compose exec -T app composer run analyse` both clean.
   > `composer run fix` reformats *pre-existing* style drift across the whole repo, so on a fresh
   > clone it can leave a large unrelated diff. You changed nothing here — after it runs,
   > `git checkout -- .` to get back to a clean tree.
   >
   > If `analyse` shows errors that CI doesn't, run `composer install --dry-run` first — a stale
   > local `vendor/` produces phantom PHPStan errors.
6. **Worktrees work** — this is the single most valuable probe, because it exercises the deploy key,
   private schema provisioning, the baked `ksg` user, and shared-service network attach at once:
   ```bash
   sh/worktree.sh create 9999-smoke     # 5-15 min: seeds a private DB
   sh/worktree.sh remove 9999-smoke
   ```

---

## Phase 10 — Claude Code configuration

Almost everything is in-repo and arrives with the clone: `CLAUDE.md`, `.claude/CLAUDE.md`, all ~46
skills under `.claude/skills/`, `.claude/settings.json` (statusline + shared permissions),
`.claude/statusline/`, and `.mcp.json` (the `laravel-boost` MCP server, which runs
`docker compose exec app php artisan boost:mcp` — so it needs the stack up).

Machine-local, recreate by hand:

- `~/.claude/settings.json` — `model: opus`, `effortLevel: high`, `advisorModel: opus`, dark theme,
  and the rate-limit statusline. Copy from the bundle or set via `/config`.
- `.claude/settings.local.json` — the per-machine permission allowlist. It is ignored via
  `~/.config/git/ignore` (phase 2), so it never travels with the repo. Recreate as needed, or copy
  from the bundle; nothing breaks without it beyond extra permission prompts.
- Plugins: `sentry` (repo-enabled) and `frontend-design` (user-enabled) install on first run.

There is a stale copy of the `headless-browser-verify` skill at
`~/.claude/skills/headless-browser-verify` on the old machine. **Do not transfer it** — the in-repo
copy is newer (it has the `chrome` compose service, overlay-stripping and the worktree
`node_modules` hardlink note that the home copy lacks).

**Probe:** `/skills` lists the project skills; a `laravel-boost` MCP tool call succeeds.

---

## Troubleshooting

Symptom → cause → fix. These are the transferable traps; machine-specific incidents from the old
laptop are deliberately not carried over.

| Symptom | Cause | Fix |
|---|---|---|
| `docker compose build app` fails early on `COPY .env` | `docker-compose/app/.env` missing | Restore it (phase 0/3). Contents: `GIT_TOKEN=<github pat>` |
| Site 500s with `Base table or view not found`, but `docker-compose/mysql` has data | **Rancher phantom bind-mount** — the container got an empty dir instead of the mount source, so MySQL initialised a fresh blank datadir | Compare `docker compose exec -T db cat /var/lib/mysql/auto.cnf` with the host `docker-compose/mysql/auto.cnf`; different server-uuid confirms it. `docker compose up -d --force-recreate db db-combatlog` (sometimes twice), then restart nginx for the stale upstream IP |
| All containers die at once, socket recreated | Rancher backend restart (toggling Kubernetes does this) | Wait for Rancher to settle fully, then `docker compose up -d`, then run the auto.cnf check above |
| `error getting credentials` on pull/build | `~/.docker/config.json` has `"credsStore": "wincred.exe"`, which flakes over WSL interop | Back up `config.json`, delete the `credsStore` key, pull/build, restore |
| `docker compose up` panics in `monitor.Start` | Rancher-bundled compose 5.0.1 | Install compose ≥ 5.1.0 into `~/.docker/cli-plugins/` and remove `cliPluginsExtraDirs` from `~/.docker/config.json` (Rancher rewrites it on update — re-remove) |
| `sudo: you do not exist in the passwd database`; MDT tests all fail | Image built with wrong `PUID`/`PGID`, so no matching `ksg` user | Fix `.env`, `docker compose build app`, `docker compose up -d --force-recreate` |
| Every `Scheduler` test fails with `cURL error 7: Failed to connect to influxdb port <n>` | `INFLUXDB_PORT` is not the container-internal `8086`. `docker-compose.yml` publishes influx on the host as **8096**, but `INFLUXDB_HOST=influxdb` is the service name, so the internal port is what counts | Set `INFLUXDB_PORT=8086`. (`.env.docker.example` shipped `3000` until this was fixed — an old `.env` restored from a bundle may be *more* correct than the example) |
| `composer run fix` aborts: `RecursiveDirectoryIterator(...#innodb_temp): Permission denied` | php-cs-fixer descended into the bind-mounted MySQL data dirs under `docker-compose/`, whose internals are owned by the container's mysql uid | `docker-compose` must be in the finder's `exclude()` in `.php-cs-fixer.php`. Note `ignoreVCSIgnored(true)` does **not** help — Finder filters results only after traversal has already failed |
| Every page 504s; `git status` shows root-owned files | Something ran as root and wrote root-owned files into the bind mount; php-fpm then loops on logging exceptions | `sudo chown -R $(id -u):$(id -g) storage/logs storage/framework` and check `PUID`/`PGID` |
| `Unable to locate file in Vite manifest` / stale JS in browser | Assets not built, or `version` stale | `npm run production` (writes `version` from the git rev) |
| PHPStan red locally, green in CI | Local `vendor/` drifted from `composer.lock` | `docker compose exec -T app composer install` |
| WSL crashes / everything freezes | VM-wide OOM | `.wslconfig` (phase 1) + keep OpenSearch off + `wsl --shutdown` |
| `worktree.sh push` dies on a missing key | `~/.ssh/keystone_worktree_ed25519` not restored | Phase 2 |

## Known-good failures — do not chase

- **`MapTilesExistenceTest`** fails only if tiles weren't transferred to
  `keystone.guru.assets/tiles/` (see phase 4). If tiles are present, it should pass.

**Not** a known-good failure: drift in `database/seeders/dungeondata/`. There is no cron rewriting
it — `mapping:sync` was deleted in #3358 and nothing seeder-related is scheduled anywhere (checked
`routes/console.php`). That JSON only changes when `mapping:save` is run explicitly, so any
unexpected diff there is a real signal to investigate, not benign noise. Still never `git add -A`
while working on seeders — but if a diff shows up unprompted, find out why instead of dismissing it.

## Where to go next

- `worktree-docker` — the isolated-worktree workflow that every task should use by default.
- `writing-tests` — the seeded-DB testing conventions this setup produces.
- `headless-browser-verify` — visual verification, used in phase 9.
- `.claude/CLAUDE.md` — the working agreement (git, MRs, review gates).
