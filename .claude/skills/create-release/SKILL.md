---
name: create-release
description: >
  Use when the user asks to "create a release", "cut a release", "make a new release",
  or generate a release changelog from commit history. Builds the
  database/seeders/releases/vX.X.X.json file from squash-merged commits since the last
  release, seeds it locally, creates the GitHub release issue, and opens the
  development→master MR. NOT for cutting the git tag (that is make:githubrelease, done
  after the MR is merged) and NOT the legacy PHP-Deployer flow.
---

# Create a release from commit history

Procedural runbook for cutting a new release. It replaces the old manual process (create
the release in the site admin UI, copy-paste its JSON into a hand-made file) by generating
`database/seeders/releases/vX.X.X.json` directly, then driving the existing artisan
commands for seeding, the GitHub release issue, and the dev→master MR.

> **Why a skill and not `release:export`?** `release:export` / `release:save` only run
> inside Docker, so the JSON they write is owned by `root:root` on the host — unusable.
> This skill writes the file on the **host** with the Write tool (correct `$USER`
> ownership), so there is no copy-paste and no ownership fix needed.

## Conventions

- **Write the release JSON on the host** with the Write tool — never via a Docker command.
- **Run artisan inside Docker:** `docker compose exec -T app php artisan …`.
- Repo for `gh`: always pass `--repo RaiderIO/keystone.guru`.
- **Do not commit.** `git add` the new JSON file (project rule: stage new files), but leave
  committing to the user. Don't merge the MR or close the issue yourself.
- Keep a running summary of the commits included and the category assigned to each.

## Step 1 — Determine the target version

- If the user gave an explicit `vX.X.X`, use it.
- Otherwise propose the next semver from the previous version (Step 2) and the nature of the
  changes (Step 4): **patch** if it's only bugfixes/maintenance, **minor** if it includes
  user-facing features/changes, **major** only if the user says so. **Confirm the version
  with the user before writing files.**

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

Commits are squash-merged onto `development`, one per issue, formatted `#NNNN <description>`
(often with a trailing `(#PR)`). The previous release's tag `v<prev>` lives on `master`;
`development` is ahead of it.

```
git log "v<prev>..HEAD" --no-merges --pretty=format:'%H%x09%s'
```

If the tag isn't present locally, `git fetch --tags` first; if it still can't be resolved,
ask the user for the previous release commit/tag. For each commit, parse the **leading
`#NNNN`** as `ticket_id` and the subject (minus the trailing `(#PR)`) as the raw change
line. Commits with no `#NNNN` → `ticket_id: 0`; surface them to the user, as they usually
need a manual decision.

## Step 4 — Enrich each line and assign a category

For each `ticket_id`:

```
gh issue view <NNNN> --repo RaiderIO/keystone.guru --json title,labels,body
```

Use the issue title/body to write a clear, user-facing `change` line (full sentence, ending
<<<<<<< HEAD
with a period — match the tone of existing releases). Fetch all issues in parallel for speed.

**Only include user-facing changes.** Omit anything the user doesn't care about:
- Test fixes or new test infrastructure
- Dependency upgrades (Node, PHP packages, PHPStan level, etc.)
- Internal refactors with no visible behaviour change (unless they produce a measurable
  user-facing improvement, e.g. a refactor that also makes the site faster → mention the
  speed improvement, not the refactor)
- Developer tooling / CI changes

For changes that survive this filter: write longer, more explanatory lines rather than terse
summaries. The goal is for the user to immediately understand what changed and why it matters
to them. For example, prefer:
> "For dungeons that have multiple entrances, you can now select which entrance you want your
> route to start at."
over:
> "Added support for multiple dungeon starts."

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
category, change line) and ask them to confirm or request edits. This avoids rewriting the
file multiple times.


**Adding a new category (only if genuinely needed):** extend `ReleaseChangelogCategory::ALL`
(next id), add the key to `lang/en_US/releasechangelogcategories.php`, and add it to
`ReleaseChangelogCategorySeeder`. Tell the user you added it.

## Step 5 — Write the release JSON on the host

Write `database/seeders/releases/vX.X.X.json` with the Write tool. Use ISO8601 timestamps
(`date -u +%Y-%m-%dT%H:%M:%S+00:00` for now). **`changes[]` entries have no `id` field.**
`title` is a short summary of the release (optional — `null` if none). Defaults:
`backup_db: 0`, `silent: 0`, `spotlight: 0`, `released: 1`.

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
                "change": "redis:clearidlekeys no longer removes sessions from localhost."
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

## Step 8 — Open the development → master MR

```
docker compose exec -T app php artisan make:githubreleasepullrequest vX.X.X
```
Creates/updates the `development`→`master` PR (`release` label), body rendered from the
release.

## Step 9 — Wrap up

Summarise to the user: the version, each included change with its category and ticket, the
issue + MR URLs, and any commits without an issue number or any new category you added.
Remind them the actual deploy is triggered later by `make:githubrelease vX.X.X` (the tag)
once the MR is merged to `master` — see the deployment-pipeline roadmap (issues #3327–#3329).
Leave the JSON staged but uncommitted.
