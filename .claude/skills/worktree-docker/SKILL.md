---
name: worktree-docker
description: Use when starting a task that should run in an isolated git worktree with its own Docker app stack, or when the user mentions worktrees, parallel branches, or running multiple checkouts side by side. Covers sh/worktree.sh (create/down/remove/list), how the isolated stack shares the main DB/redis, and how to run artisan/tests inside it. Do not use for the main development stack or generic docker-compose questions.
---

# Worktree + Docker stack

Run each task in its own git **worktree** with a minimal Docker stack (`app` + `nginx`) that shares
the main stack's database, redis, reverb, etc. This lets parallel agents work without colliding.

By default, **every task starts in a fresh worktree** created with `sh/worktree.sh` (see the "Git
worktrees" rule in `.claude/CLAUDE.md`), unless the user says otherwise.

## Prerequisite

The **main stack must be running** — the worktree attaches to its shared containers/network. If it
isn't, start it from the main repo: `docker compose up -d`.

## Create a worktree

```bash
sh/worktree.sh create <issue>-<slug>            # branches off origin/master
sh/worktree.sh create <issue>-<slug> <base-ref> # or off an explicit base
```

This creates the worktree at `../keystone.guru-worktrees/<issue>-<slug>`, copies the main `.env`
(never read — a plain shell `cp`), rewrites only `APP_URL`/`URL_HOST` to the worktree's port,
appends `COMPOSE_PROJECT_NAME` / `COMPOSE_FILE` / `WORKTREE_HTTP_PORT` to that copy, starts the
stack, wires up the shared services, and prints the URL (e.g. `http://localhost:8100`).

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

Open `http://localhost:<port>` in a browser to view the worktree's front-end.

## How the isolation works

- The stack (`docker-compose.worktree.yml`) reuses the prebuilt `keystone.guru` image and
  bind-mounts the worktree checkout — **no rebuild**.
- Each worktree runs on its **own private network**. The worktree `app` is intentionally NOT put on
  the main shared network (that would clash with the main `app` DNS alias and let the main nginx
  serve worktree code). Instead `sh/worktree.sh` attaches the shared containers (`db`,
  `db-combatlog`, `redis`, `reverb`, `app-assets`, `opensearch-node1`, `influxdb`) INTO the
  worktree network with matching aliases — so the copied `.env` needs no host changes and the main
  stack stays untouched.

## Shared database — keep migrations non-destructive

All worktrees and your main app share the single `keystone.guru.dev` schema (tests run against it;
there is no `RefreshDatabase`). A migration in one worktree affects everyone, so:

- Keep migrations **non-destructive** (project policy: only drop a column in a follow-up ticket once
  the code no longer uses it and is deployed).
- **Never** run `migrate:fresh` / `migrate:refresh` in a worktree — it wipes the shared DB.

## Horizon (opt-in — only when changing queue workers)

Redis is shared, so a worktree Horizon competes with the main one for jobs. While iterating:

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

MRs target `master` (the default branch), so a `Closes #<issue>` line in the body auto-links the
issue in the Development panel and closes it on merge — no manual linking step needed.

`sh/worktree.sh push` uses a passphraseless **write deploy key** scoped to this repo
(`~/.ssh/keystone_worktree_ed25519`, override with `KSG_WORKTREE_DEPLOY_KEY`) so no password is
prompted. It runs `ssh -F /dev/null` to bypass `~/.ssh/config` (which maps github.com to a
passphrase-protected key). Commits are SSH-signed with the existing (passphraseless) signing key, so
signing is non-interactive too.

To re-provision the deploy key (e.g. on a new machine): generate a passphraseless key and register it
as a write deploy key —
`gh api -X POST repos/RaiderIO/keystone.guru/keys -f title=claude-worktree-push -f key="$(cat ~/.ssh/keystone_worktree_ed25519.pub)" -F read_only=false`.

## Tear down

```bash
sh/worktree.sh down   <issue>-<slug>   # stop the stack, keep the checkout
sh/worktree.sh remove <issue>-<slug>   # stop the stack and remove the worktree
sh/worktree.sh list                    # list worktrees and running stacks
```
