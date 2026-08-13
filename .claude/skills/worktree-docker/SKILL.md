---
name: worktree-docker
description: Use when starting a task that should run in an isolated git worktree with its own Docker app stack, or when the user mentions worktrees, parallel branches, or running multiple checkouts side by side. Covers sh/worktree.sh (create/down/remove/provision-db/prune-db/list), the per-worktree private database (and the --shared-db escape hatch), and how to run artisan/tests inside it. Do not use for the main development stack or generic docker-compose questions.
---

# Worktree + Docker stack

Run each task in its own git **worktree** with a minimal Docker stack (`app` + `nginx`). By default
each worktree also gets its **own freshly-seeded database schemas** (on the shared MySQL servers)
and its own redis prefix, so parallel agents cannot touch each other's data — or the main stack's.

By default, **every task starts in a fresh worktree** created with `sh/worktree.sh` (see the "Git
worktrees" rule in `.claude/CLAUDE.md`), unless the user says otherwise.

## Prerequisite

The **main stack must be running** — the worktree attaches to its shared containers/network. If it
isn't, start it from the main repo: `docker compose up -d`.

## Create a worktree

**First, check the model against the task** — see "Check the model before starting a task" in
`.claude/CLAUDE.md`. Estimate the task's turn count from its shape and, *if the session is
under-powered for it*, say so and stop before running `create`. Don't raise it when the session is
over-powered. This check belongs here specifically because an isolated `create` seeds a database for
5–15 minutes, and restarting on the right model throws all of that away.

```bash
sh/worktree.sh create <issue>-<slug>              # branches off origin/master
sh/worktree.sh create <issue>-<slug> <base-ref>   # or off an explicit base
sh/worktree.sh create <issue>-<slug> --shared-db  # share the main stack's DB data (see below)
```

This creates the worktree at `../keystone.guru-worktrees/<issue>-<slug>`, copies the main `.env`
(never read — a plain shell `cp`), rewrites `APP_URL`/`URL_HOST` to the worktree's port (and, in the
default isolated mode, `DB_DATABASE`/`DB_PHPUNIT_DATABASE`/`DB_COMBATLOG_DATABASE`/`REDIS_PREFIX`
to per-worktree values), appends `COMPOSE_PROJECT_NAME` / `COMPOSE_FILE` / `WORKTREE_HTTP_PORT` to
that copy, starts the stack, wires up the shared services, provisions the private DB (isolated mode:
create schemas + migrate + full seed — **expect ~5–15 minutes**, the streamed seeder output is the
progress bar), and prints the URL (e.g. `http://localhost:8100`).

A `.worktree-db` marker file in the worktree root records the DB mode and schema name — `remove`
and `prune-db` read it to know what to drop. Don't delete or edit it.

It also marks issue `#<issue>` (the leading number of the branch) with the `in progress` label on
GitHub, so you can see at a glance what's actively being worked on. `remove` clears it again (see
below). This is best-effort: it's skipped silently when the branch has no leading issue number or
`gh` isn't available, and never blocks the worktree operation.

Finally it binds this Claude session's **status line** to the worktree (via
`.claude/statusline/bind-worktree.sh` using `$CLAUDE_CODE_SESSION_ID`), so the status line shows
`<worktree>:<port>` on the right. This is automatic — you do **not** need to call `bind-worktree.sh`
by hand; `remove` unbinds it, and the status line self-cleans markers for removed worktrees.

## Run commands in the worktree

From **inside the worktree dir**, the normal project command pattern just works — `COMPOSE_FILE`
and `COMPOSE_PROJECT_NAME` come from the worktree `.env`, so no extra flags are needed:

```bash
cd ../keystone.guru-worktrees/<issue>-<slug>
docker compose exec -T app php artisan <cmd>
docker compose exec -T app php artisan test --compact --filter=<name>
docker compose exec -T app composer run fix
docker compose exec -T app composer run analyse
```

Note the `docker compose exec -T app` prefix is not optional for `composer run fix` / `analyse`:
run on the host they abort in `vendor/composer/platform_check.php` (the host PHP is the wrong
version).

Open `http://localhost:<port>` in a browser to view the worktree's front-end.

## No `node_modules` — front-end work needs one

`create` does not install node modules, so `npx vitest`, `npm run development` and anything that
resolves `puppeteer` all fail until you provide one. Hardlink the main checkout's copy (seconds,
shares its blocks):

```bash
cp -al ../../keystone.guru/node_modules ./node_modules
rm -rf node_modules/.cache   # the only entries cp -al cannot link (root-owned); harmless
```

A **symlink** also works for host-side commands (`npx vitest`) but NOT for anything running inside
the `app` container — only the worktree is bind-mounted, so a symlink pointing into the main
checkout dangles there. Use the hardlink copy if you need either. Delete it before
`sh/worktree.sh remove`.

A JS/CSS change is only visible in the worktree's browser after `npm run development` (host-side);
the served filename is `public/js/app-<git HEAD sha>.js`, so a fresh worktree serves nothing until
that first build.

## How the isolation works

- The stack (`docker-compose.worktree.yml`) reuses the prebuilt `keystone.guru` image and
  bind-mounts the worktree checkout — **no rebuild**.
- Each worktree runs on its **own private network**. The worktree `app` is intentionally NOT put on
  the main shared network (that would clash with the main `app` DNS alias and let the main nginx
  serve worktree code). Instead `sh/worktree.sh` attaches the shared containers (`db`,
  `db-combatlog`, `redis`, `reverb`, `app-assets`, `opensearch-node1`, `influxdb`) INTO the
  worktree network with matching aliases — so the copied `.env` needs no host changes and the main
  stack stays untouched.
- **Database isolation** rides on the same shared MySQL *servers*: `create` provisions a private
  schema `ksg_wt_<branch>_<hash>` on **both** servers (main + combatlog), loads the Laravel schema
  dumps, runs all migrations, the full `DatabaseSeeder`, and `LaratrustSeeder` (users **1=admin,
  2=internal_team, 3=user** — log in as `admin@app.com` / `password`). Tests hit the same private
  schema (`DB_PHPUNIT_DATABASE` is rewritten too). No extra mysql containers exist per worktree.

### What is / isn't isolated (default mode)

Isolated per worktree:
- Main DB schema (app + migrations + tests) and combatlog DB schema (empty, migrated).
- Redis, via a per-worktree `REDIS_PREFIX` — covers the cache, **model cache**
  (laravel-model-caching), **sessions**, and **queues**.
- The file cache (`/tmp/...` inside the worktree's own `app` container).

Still shared with the main stack:
- The OpenSearch index, reverb, influxdb, `app-assets` — and the few endpoints nginx proxies to the
  main `app-swoole`, which run **main code against the main DB**.
- The public thumbnails dir (see below).

Consequences to know about:
- A freshly seeded schema has **no dungeon routes** — use the `seed-dev-routes` skill when a task
  needs route/demo data.
- Queued jobs dispatched in an isolated worktree are invisible to the shared Horizon (different
  schema *and* different redis prefix). Run a worker in the worktree if needed, or use
  `--shared-db`.
- Search-driven pages query the shared OpenSearch index, whose ids reference main-DB rows.
- Sessions are per-worktree, so switching between main-stack and worktree tabs re-logins each side.
- **The git stash is shared.** All worktrees use the one `.git` dir, so `git stash list` is global:
  a `git stash pop` in your worktree can pop *another session's* entry and drop its changes into
  your tree as conflicts. To temporarily revert files (e.g. to shoot a "before" screenshot), use
  `git checkout <ref> -- <paths>` and `git checkout HEAD -- <paths>` instead of stashing. If you do
  pop the wrong entry, a conflicted pop keeps the stash — `git reset --hard HEAD` restores your
  tree and the entry stays in the list.

### Destructive operations are now OK (isolated mode only)

The private schema has its own `migrations` table, so in an **isolated** worktree you may test
destructive migrations and even run `migrate:fresh` — it only wipes *your* schema (re-seed after
with `db:seed` or `worktree.sh provision-db`). The production deploy rules in `.claude/CLAUDE.md`
(backward-compatible, expand/contract) still apply to the migration you *ship*, of course.

### `--shared-db` — the escape hatch

`create <branch> --shared-db` skips all of the above and shares the main stack's schemas and redis
prefix, exactly like worktrees used to. Use it when a task genuinely needs production-like data:
heatmaps, Path B thumbnail generation via shared Horizon, big route datasets, queue/Horizon work
against the shared stack. **All the old rules then apply**:

- Keep migrations **non-destructive** (only drop a column in a follow-up ticket once the code no
  longer uses it and is deployed).
- **Never** run `migrate:fresh` / `migrate:refresh` — it wipes the shared DB for everyone.
- Test records must be cleaned up meticulously (see the `writing-tests` skill) — the main checkout
  and other `--shared-db` worktrees see everything you write.

## Shared thumbnails (public disk only)

A worktree's `app`/`nginx` don't run Horizon/puppeteer, so `docker-compose.worktree.yml` bind-mounts
the **main checkout's** `storage/app/public/thumbnails` (`MAIN_THUMBNAILS_DIR`) into every worktree's
`app` + `nginx`. So thumbnails the **main stack** generates (on the `public` disk, i.e.
`FILESYSTEM_DISK=public`) appear live in all worktrees — regenerate via the main stack's Horizon and
they show up everywhere. (To generate a thumbnail *from* a worktree, see the **Thumbnails** section
below.)

Only the **public** dir is shared. `storage/app/private/thumbnails` (the `local` disk) is **not**
mounted — it stays per-worktree. That per-branch isolation is exactly what the `render` service's
Path A (`dungeonroute:renderthumbnail`, see **Thumbnails**) relies on: it forces the `local` disk so a
branch render never touches the shared public set. (A `disk=local` thumbnail is served through
Laravel's local-serve route, not the `public/storage` symlink, so it's only visible on the stack that
created it.)

## Horizon (opt-in — only when changing queue workers)

In the default **isolated** mode the worktree has its own redis prefix, so its jobs are invisible
to the main Horizon (and vice versa) — just run a worker in the worktree when you need one:

```bash
docker compose exec -T app php artisan horizon        # or: php artisan queue:work --once
```

In `--shared-db` mode redis is shared and a worktree Horizon competes with the main one for jobs.
While iterating:

```bash
# from the MAIN repo: pause the main worker so it doesn't steal your jobs
docker compose -p keystoneguru stop horizon
# in the worktree: run the worker against your code
docker compose exec -T app php artisan horizon        # or: php artisan queue:work --once
# when done, restart the main worker
docker compose -p keystoneguru start horizon
```

## Cron (opt-in — rarely needed)

Cron just runs artisan commands, so test the specific command directly in the worktree app
(`php artisan <command>`), rather than spinning up a `cron` container.

## Commit, push & open a MR (autonomous)

The worktree and its branch are yours — commit as you go, then push and open a MR without asking:

```bash
# from inside the worktree
git add -A && git commit -m "#<issue> <what changed>"
sh/worktree.sh push                         # pushes the current branch via the scoped deploy key
gh pr create --repo RaiderIO/keystone.guru --base master --head <issue>-<slug> \
  --title "#<issue> <title>" \
  --body "Closes #<issue>

<summary of what changed and why>"
```

**The `--title` MUST start with `#<issue> ` — this is not optional and easy to drop by accident.**
Every open PR here follows this format (`#3866 Drop a team's tags when...`, `#3674 Cover
StateManager's...`); a title missing it (seen in the wild: `AjaxProfileController 500s for guests:
guard with auth middleware`, `Centralize the "safely get map object group" helpers in util.js`)
breaks the at-a-glance issue↔PR association everywhere titles are read (PR list, notifications, the
squash-merge commit subject) even though the branch name and body still carry the number correctly.
**Before running `gh pr create`, double-check the `--title` string itself contains `#<issue>` as its
first token** — don't rely on the branch name or `Closes #<issue>` body line to carry that on the
title's behalf, they're separate fields. If you're fixing an existing PR that's missing it:
`gh pr edit <n> --title "#<issue> <title>"` (title edits work fine even though `gh pr edit`'s body
edits are broken on this repo — see the CLAUDE.md note above).

MRs target `master` (the default branch), so a `Closes #<issue>` line in the body auto-links the
issue in the Development panel and closes it on merge — no manual linking step needed. Verify it
actually took (`gh api graphql -f query='query { repository(owner: "RaiderIO", name:
"keystone.guru") { pullRequest(number: <n>) { closingIssuesReferences(first: 5) { nodes { number } }
} } }'` must be non-empty) — without it the merge leaves the issue open for Wotuu to close by hand.
When one issue is split across sibling MRs, **exactly one of them says `Closes #<issue>`** (the last
to merge) and the rest say `Part of #<issue>`; `babysit-prs` checks this issue-scoped, so a sibling
with no closing reference is fine as long as one of them has it.

`sh/worktree.sh push` uses a passphraseless **write deploy key** scoped to this repo
(`~/.ssh/keystone_worktree_ed25519`, override with `KSG_WORKTREE_DEPLOY_KEY`) so no password is
prompted. It runs `ssh -F /dev/null` to bypass `~/.ssh/config` (which maps github.com to a
passphrase-protected key). Commits are SSH-signed with the existing (passphraseless) signing key, so
signing is non-interactive too.

To re-provision the deploy key (e.g. on a new machine): generate a passphraseless key and register it
as a write deploy key —
`gh api -X POST repos/RaiderIO/keystone.guru/keys -f title=claude-worktree-push -f key="$(cat ~/.ssh/keystone_worktree_ed25519.pub)" -F read_only=false`.

### Rewriting a commit that isn't `HEAD` (interactive rebase is unsupported here)

Mark the tip first, reset, amend, then replay: `git branch -f save HEAD`,
`git reset --hard <target>`, `git commit --amend --cleanup=verbatim -F <msgfile>`, then
`git cherry-pick save`. Use a branch as the marker, not a tag — `git tag` in this repo forces
annotated tags and needs `-m`. Remember `--cleanup=verbatim` whenever the subject starts with
`#<issue>` (see the commit-subject gotcha in `.claude/CLAUDE.md`).

## Thumbnails

A worktree does not run Horizon/puppeteer. To make a route thumbnail, use the two canonical paths in
the **generating-thumbnails** skill: dispatch to shared Horizon (`dungeonroute:queuethumbnail`) for
real thumbnails, or render this branch's code to the local disk for inspection
(`docker compose --profile render run --rm render dungeonroute:renderthumbnail <key>`). Never call
`ThumbnailService::createThumbnail()` synchronously in the `app` container — it has no Chrome.

**Path B (shared Horizon) does not work from an isolated worktree**: the route row only exists in
the worktree's private schema, and the dispatch lands under the worktree's private redis prefix, so
the main Horizon never sees either. Use Path A, or create the worktree with `--shared-db` when real
thumbnails are the point of the task.

## Broken worktree after a main-stack restart? Run `repair`

Restarting the **main** stack detaches its shared containers from every worktree network — the
worktree's nginx then 502s/fails on every request because its upstreams (`db`, `app-swoole`,
`reverb`, ...) no longer resolve. Don't debug this by hand; run:

```bash
sh/worktree.sh repair                  # fix ALL running worktree stacks
sh/worktree.sh repair <issue>-<slug>   # fix just one
```

It reattaches the shared-service aliases (idempotent) and restarts each worktree's nginx. Safe to
run blindly whenever a worktree suddenly stops serving pages.

## Tear down

```bash
sh/worktree.sh down   <issue>-<slug>   # stop the stack, keep the checkout AND the private DB
sh/worktree.sh remove <issue>-<slug>   # stop the stack, remove the worktree, DROP the private DB
sh/worktree.sh provision-db <issue>-<slug>  # retry a failed/interrupted DB provisioning (idempotent)
sh/worktree.sh prune-db [--force]      # list/drop private schemas whose worktree checkout is gone
sh/worktree.sh list                    # list worktrees and running stacks
```

The private DB lifecycle follows the checkout, not the stack: `down` + a later `create` re-attaches
to the existing schema and **skips the seed** (fast). `remove` drops the schemas on both servers.
If a `create` fails mid-seed the stack is left up on purpose — retry with `provision-db` (safe to
re-run) or `remove` to give up. If a worktree was deleted behind the script's back (raw
`git worktree remove`, `rm -rf`), its schemas linger — `prune-db` finds and drops them.

`remove` also clears the `in progress` label from issue `#<issue>` (best-effort). `down` leaves it
in place — a stopped-but-present worktree still counts as being worked on. Note a worktree torn down
with a raw `git worktree remove` (bypassing the script) won't clear the label.
