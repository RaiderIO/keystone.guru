---
name: create-release
description: >
  Use when the user asks to "create a release", "cut a release", "make a new release",
  or generate a release changelog from commit history. Builds the
  database/seeders/releases/vX.X.X.json file from squash-merged commits since the last
  release, seeds it locally, creates the GitHub release issue, and cuts the `v*` git tag. NOT
  the legacy PHP-Deployer flow.
---

# Create a release from commit history

Procedural runbook for cutting a new release. It replaces the old manual process (create
the release in the site admin UI, copy-paste its JSON into a hand-made file) by generating
`database/seeders/releases/vX.X.X.json` directly, then driving the existing artisan
commands for seeding and the GitHub release issue.

> **Why a skill and not `release:export`?** `release:export` / `release:save` only run
> inside Docker, so the JSON they write is owned by `root:root` on the host — unusable.
> This skill writes the file on the **host** with the Write tool (correct `$USER`
> ownership), so there is no copy-paste and no ownership fix needed.

## Conventions

- **Write the release JSON on the host** with the Write tool — never via a Docker command.
- **Run artisan inside Docker:** `docker compose exec -T app php artisan …`.
- Repo for `gh`: always pass `--repo RaiderIO/keystone.guru`.
- **Do not commit.** `git add` the new JSON file (project rule: stage new files), but leave
  committing to the user. Don't close the release issue yourself.
- Keep a running summary of the commits included and the category assigned to each.

## Step 1 — Determine the target version

- If the user gave an explicit `vX.X.X`, use it.
- Otherwise propose the next semver from the previous version (Step 2) and the nature of the
  **public** (`is_public: 1`) changes only: **patch** if they are only bugfixes/maintenance,
  **minor** if they include user-facing features/changes, **major** only if the user says so.
  Non-public changes (CI, tests, tooling) do not influence semver. **Confirm the version with
  the user before writing files.**

## Step 2 — Find the previous release and the id counters

The JSON files carry **hardcoded sequential ids**; the new release continues the sequence.

- Previous version:
  ```
  docker compose exec -T app php artisan release:current
  ```
- Latest ids — query the DB (Boost `database-query` tool, read-only):
  ```sql
  SELECT (SELECT MAX(id) FROM releases)            AS max_release_id,
         (SELECT MAX(id) FROM release_changelogs)  AS max_changelog_id;
  ```
  New `release.id = max_release_id + 1`, new `release_changelog_id = max_changelog_id + 1`.
  (Fallback if the DB is unavailable: read the highest `database/seeders/releases/v*.json`
  and increment its `id` / `release_changelog_id`.)

## Step 3 — Collect the commits since the previous release

Commits are squash-merged onto `master`, one per issue, formatted `#NNNN <description>`
(often with a trailing `(#PR)`). The previous release's tag `v<prev>` lives on `master`;
`master` is ahead of it with the commits merged since.

```
git log "v<prev>..HEAD" --no-merges --pretty=format:'%H%x09%s'
```

If the tag isn't present locally, `git fetch --tags` first; if it still can't be resolved,
ask the user for the previous release commit/tag. For each commit, parse the **leading
`#NNNN`** as `ticket_id` and the subject (minus the trailing `(#PR)`) as the raw change
line. Commits with no `#NNNN` → `ticket_id: 0`; surface them to the user, as they usually
need a manual decision.

## Step 4 — Enrich each line, assign a category, and set is_public

For each `ticket_id`:

```
gh issue view <NNNN> --repo RaiderIO/keystone.guru --json title,labels,body
```

Fetch all issues in parallel for speed.

**Include ALL commits** — but mark them with the appropriate `is_public` value:

- **`is_public: 1` (public)** — user-facing changes that appear on the site, Discord, and
  GitHub Release. Write a clear, descriptive `change` line (full sentence ending with a
  period). Longer and more explanatory is better. E.g.:
  > "For dungeons that have multiple entrances, you can now select which entrance you want
  > your route to start at."

- **`is_public: 0` (non-public)** — internal changes that appear only in the GitHub release
  ticket and PR (for internal tracking). These include:
  - Test fixes or new test infrastructure
  - Dependency upgrades (Node, PHP packages, PHPStan level, etc.)
  - Internal refactors with no visible behaviour change
  - Developer tooling / CI / deployment pipeline changes

  For non-public changes, a short `change` line is fine (the commit subject is acceptable).

Assign **one category** per change. Category ids come from `App\Models\ReleaseChangelogCategory::ALL`:

| id | key | id | key |
|----|-----|----|-----|
| 1 | general_changes | 8 | mdt_exporter_changes |
| 2 | route_changes | 9 | live_session_changes |
| 3 | map_changes | 10 | simulation_craft_changes |
| 4 | mapping_changes | 11 | auto_route_changes |
| 5 | bugfixes | 12 | api_changes |
| 6 | mdt_importer_changes | 13 | heatmap_changes |
| 7 | team_changes | | |

Infer from the issue's labels/title (e.g. a `bug` label → `bugfixes` (5); API work →
`api_changes` (12); MDT import → `mdt_importer_changes` (6); mapping data → `mapping_changes`
(4)). When nothing fits, default to `general_changes` (1).

**Before writing the JSON, present the user with a table** of all proposed changes (ticket,
`is_public`, category, change line) and ask them to confirm or request edits. This avoids
rewriting the file multiple times.

**Adding a new category (only if genuinely needed):** extend `ReleaseChangelogCategory::ALL`
(next id), add the key to `lang/en_US/releasechangelogcategories.php`, and add it to
`ReleaseChangelogCategorySeeder`. Tell the user you added it.

## Step 5 — Write the release JSON on the host

Write `database/seeders/releases/vX.X.X.json` with the Write tool. Use ISO8601 timestamps
(`date -u +%Y-%m-%dT%H:%M:%S+00:00` for now). **`changes[]` entries have no `id` field.**
`title` is a short summary of the release (optional — empty string if none, `title` is not nullable).
Defaults: `backup_db: 0`, `silent: 0`, `spotlight: 0`, `released: 1`.

Each entry in `changes[]` requires an `is_public` field (`1` = shown publicly on site /
Discord / GitHub Release; `0` = shown only in the GitHub release ticket and PR).

If there are no changes that are `is_public` = `1`, mark the release as `silent` = `1`. This
will prevent users from being notified of the release being published on the production site.

```json
{
    "id": 360,
    "release_changelog_id": 367,
    "version": "v15.2.0",
    "title": "Short summary of the release",
    "backup_db": 0,
    "silent": 0,
    "spotlight": 0,
    "released": 1,
    "created_at": "2026-06-24T12:00:00+00:00",
    "updated_at": "2026-06-24T12:00:00+00:00",
    "changelog": {
        "id": 367,
        "release_id": 360,
        "description": null,
        "changes": [
            {
                "release_changelog_id": 367,
                "release_changelog_category_id": 5,
                "ticket_id": 3321,
                "is_public": 1,
                "change": "redis:clearidlekeys no longer removes sessions from localhost."
            },
            {
                "release_changelog_id": 367,
                "release_changelog_category_id": 1,
                "ticket_id": 3320,
                "is_public": 0,
                "change": "Updated CI workflows to use OIDC for AWS credentials."
            }
        ]
    }
}
```

Then stage it: `git add database/seeders/releases/vX.X.X.json`.

**Pause here for the user to review/edit the JSON** before Steps 6–8.

## Step 6 — Seed the release locally

So the site/DB reflect the new release (Steps 7–8 read it from the DB). Use `db:seedone`
— it wraps `DatabaseSeeder` with just the one seeder class, handling the temp-table
create/swap/cleanup that `ReleasesSeeder` requires. Running `db:seed --class=ReleasesSeeder`
directly will fail because the `releases_temp` table won't exist.

```
docker compose exec -T app php artisan db:seedone ReleasesSeeder
```

## Step 7 — Create the GitHub release issue

```
docker compose exec -T app php artisan make:githubreleaseticket vX.X.X
```
Creates/updates a `release`-labelled issue titled `Release vX.X.X - <title>`, body rendered
from the seeded release. (Reads the release from the DB — hence after Step 6.)

## Step 8 — Land the JSON on master

Under the trunk model there is **no release PR and no feature branch** for the release notes.
Commit **only** `database/seeders/releases/vX.X.X.json` directly to `master` and push:

```
git commit database/seeders/releases/vX.X.X.json -m "#<issue> Added release vX.X.X"
git push origin master
```

Why direct-to-master (not a branch/PR): the CI workflows (`js-tests`, `php-tests`, `phpstan`)
run **only on `pull_request` to master**, and `release-deploy` runs **only on a `v*` tag push**.
So a plain push of the JSON to `master` triggers **zero pipelines** — a PR would only add CI runs
for a data-only file, and the push does not deploy anything on its own. (`master` has no
branch-protection PR requirement; recent releases were already committed this way.)

**Ordering guardrail (must-hold):** the deploy re-seeds release notes from the JSON files at the
tagged commit, so the `v*` tag must point at a commit that **already contains** this JSON. Always:
commit the JSON to `master` → push → *then* cut the tag:

```
git tag -a vX.X.X -m "vX.X.X"
git push --tags
```

Only the JSON belongs in the release commit — if `composer run fix` reformatted unrelated files,
discard them (`git checkout -- <other files>`) before committing.

**Do NOT run `make:githubrelease`.** That artisan command creates a GitHub *Release* (not just the
tag), which must only happen when rolling out to **production**. Cutting the GitHub Release is the
infra project's job — from this repo, only cut the plain `v*` tag as shown above. The tag push is
what triggers the `release-deploy` pipeline (staging first).

Summarise to the user: the version, each included change with its category and ticket, the
issue URL, and any commits without an issue number or any new category you added. Remind them
that the tag push triggers the `release-deploy` pipeline (staging first), and that the GitHub
*Release* itself is cut separately from the infra project once it's going to production — see
the deployment-pipeline roadmap (issues #3327–#3329).

Suggest running `sh/release-watch.sh <version>` next — it tracks the build jobs, both
staging/production infra deploys, and verification from one terminal (see the `release-watch`
skill).
