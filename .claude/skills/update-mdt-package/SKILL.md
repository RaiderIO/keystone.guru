---
name: update-mdt-package
description: >
  Use when asked to update / bump the Mythic Dungeon Tools (MDT) composer package to a
  newer version and reimport dungeon mappings. Covers the full runbook: opening a tracking
  GitHub issue, composer reference bump, mapping reimport for the current retail season, new
  POI type/template handling, test fixture rewriting, NPC translation export, and re-seeding.
  NOT related to the MDT import/export string services (mdt-import / mdt-export skills).
---

# Update the Mythic Dungeon Tools (MDT) package

This is a procedural runbook for updating the **MDT composer package** and reimporting the
dungeon mappings that come with it. Execute the steps in order. Pause for the user only at
the points called out below.

> **Not** the MDT import/export string services. Those (`mdt-import` / `mdt-export`) are
> custom implementations based on this package but are unrelated to updating it. Ignore them
> here.

## Conventions

- **Run every command inside Docker:** `docker compose exec -T app …` (e.g.
  `docker compose exec -T app php artisan …`, `docker compose exec -T app composer …`).
- **Work in a worktree and drive it to a draft MR**, per CLAUDE.md's default — the worktree and
  its branch are yours, so commit the whole run (composer.json, composer.lock, config, mapping
  JSON, fixtures, lang files) as you go and open a draft MR at the end. Only leave the diff
  uncommitted when the user asked you to work in the main checkout instead.
- **Stage only what you meant to touch.** A `mapping:save` run reliably produces trailing-newline
  / comma-placement churn in `database/seeders/seasondata/affix_groups.json`,
  `database/seeders/seasondata/seasons.json` and `database/seeders/dungeondata/spells.json` with
  no content change; `git checkout --` those before committing so the (already large) mapping diff
  stays reviewable.
- Keep a running summary as you go: which dungeons imported, any new POI types/templates
  added, and any tests still failing.

## Step 0 — Baseline the test suite

Before touching anything, run the **full test suite** once on the unmodified worktree and save
the list of failing tests:

```
docker compose exec -T app php artisan test --compact > /tmp/mdt_baseline_test_run.log 2>&1
```

The suite is not guaranteed green even on a fresh checkout (environmental flakes: Reverb/Pusher
not running locally, `MapTilesExistenceTest` needing an asset mount only the main stack has,
etc.) — this baseline is what lets Step 8 tell a **pre-existing** failure apart from a **real
regression** the MDT bump introduced, instead of having to reason about it from scratch after
the fact. Keep the log file around; don't just eyeball the pass count.

## Step 1 — Open a tracking GitHub issue

**A bot already files these.** An automation opens an `Update MDT to v<version>` issue (label
`mdt`) when a new upstream release appears, so check first — `gh issue list -R
RaiderIO/keystone.guru --label mdt --state open` — and if the issue exists, skip straight to
Step 2 with its number. Only create one when there is none.

Otherwise, open an issue on `RaiderIO/keystone.guru` to document the update.
First grab the target version (the upstream tag name) so the title matches convention:

```
gh release view -R nnoggie/MythicDungeonTools --json tagName --jq '.tagName'
```

Then create the issue (title format `Update MDT to v<version>`, label `mdt` — match prior
issues like #3148 / #3192):

```
gh issue create -R RaiderIO/keystone.guru \
  --title "Update MDT to v<version>" \
  --label mdt \
  --body "$(cat <<'EOF'
Tracking the update of the Mythic Dungeon Tools (MDT) composer package and reimport of the
current retail season's dungeon mappings.

- [ ] Bump composer package reference (version + commit SHA) and `composer update`
- [ ] Reimport each retail-season dungeon mapping
- [ ] Handle any new POI types/templates and missing map-icon translations
- [ ] Run per-dungeon tests (rewrite stale CorrectEvents fixtures)
- [ ] Bump `config/keystoneguru.php` MDT version
- [ ] Re-export NPC translations and re-seed
- [ ] Run the full test suite
EOF
)"
```

**Record the issue number** it prints — reference it in the final summary so the user can
track the work, and (if they later ask you to commit) use it as the `#<number>` prefix that
this repo's commit messages follow (e.g. `#3192 Update MDT to v6.1.4`). Do **not** commit or
close the issue yourself unless asked.

## Step 2 — Bump the composer package reference

The package is a fork: composer downloads it from the user's fork
`https://github.com/Wotuu/MythicDungeonTools.git`, but the source of truth is upstream
`nnoggie/MythicDungeonTools`.

1. You already fetched the latest **upstream tag** (version) in Step 1; now also capture its
   **commit SHA**. Re-confirm the version and resolve the SHA:
   ```
   gh release view -R nnoggie/MythicDungeonTools --json tagName,targetCommitish
   ```
   **`targetCommitish` is usually a branch name (e.g. `master`), NOT a commit SHA** — don't
   use it as the reference. Resolve the tag to its commit SHA via the git refs API. Tags are
   typically **annotated**, so this is a two-step dereference (tag object → commit):
   ```
   # 1) get the tag object (note the type — "tag" means annotated, "commit" means lightweight)
   gh api repos/nnoggie/MythicDungeonTools/git/refs/tags/<tagName> \
     --jq '{type: .object.type, sha: .object.sha}'

   # 2) only if type == "tag": dereference the annotated tag object to the commit SHA
   gh api repos/nnoggie/MythicDungeonTools/git/tags/<shaFrom1> --jq '.object.sha'
   ```
   For a lightweight tag (type `commit`), the SHA from the first call is already the commit SHA.
2. **Major version check:** the current constraint is `^6.0`. If the new release is a major
   bump (e.g. `7.x`, `8.x`), **stop and hand off to the user** — do not proceed.
3. **Sync the fork** to upstream so the commit exists where composer downloads from:
   ```
   gh repo sync Wotuu/MythicDungeonTools --source nnoggie/MythicDungeonTools
   ```
   **If the sync fails** (no access, conflict, etc.): pause and **ask the user for the commit
   hash AND the version number**, then continue with what they provide.

   **Known, non-blocking failure — `HTTP 422 Repository rule violations found`** (`gh repo sync`
   may report it as a misleading `HTTP 404` on `git/refs/heads/master`). A repository ruleset on
   the fork blocks updating `master`, so it can't be fast-forwarded. Don't stop: GitHub forks
   share an object store, so the upstream commit is **already reachable in the fork by SHA**.
   Verify that instead of syncing, then continue:
   ```
   gh api repos/Wotuu/MythicDungeonTools/commits/<sha> --jq '.sha'
   curl -sIL -o /dev/null -w '%{http_code}\n' \
     https://github.com/Wotuu/MythicDungeonTools/archive/<sha>.zip
   ```
   The **dist** install is what CI and production use (`config.preferred-install` is `dist`), so
   a 200 on the archive URL means the bump is sound. See the Step 2.5 note below for the local
   vendor gotcha this exposes.
4. Edit `composer.json` (the MDT package block, around **lines 30–43**, package
   `nnoggie/mythicdungeontools`). The commit hash appears in **two** places — update both,
   plus the version:
   - `package.version` → new version (no `v` prefix, e.g. `6.1.5`)
   - `package.source.reference` → new commit SHA
   - `package.dist.url` → swap the SHA embedded in the archive URL
   Leave the `^6.0` `require` constraint (line ~99) unchanged unless a pre-approved major bump.
5. Download the new version (also updates `composer.lock`):
   ```
   docker compose exec -T app composer update nnoggie/mythicdungeontools
   ```
   **If it fails with `<sha> is gone (history was rewritten?)` / `fatal: unable to read tree`:**
   the local `vendor/nnoggie/mythicdungeontools` was previously installed from **source**, and
   composer keeps the existing install type on update — so it tries a `git checkout` in a clone
   of the fork, which genuinely lacks the commit when the fork couldn't be synced (step 3).
   `--prefer-dist` does **not** override this. The lock file is already written correctly at this
   point, so just reinstall the package from the lock, which honours `preferred-install: dist`:
   ```
   rm -rf vendor/nnoggie/mythicdungeontools
   docker compose exec -T app composer install
   ```
   Confirm afterwards with `grep '^## Version' vendor/nnoggie/mythicdungeontools/MythicDungeonTools.toc`.

## Step 2.5 — Bump the config version *before* importing

Update `config/keystoneguru.php` → `keystoneguru.mdt.version` to the new version, matching the
existing `v`-prefixed format (e.g. `v6.2.1`), **before** running any `mdt:importmapping`.

`MappingService` stamps each new `mapping_versions` row's `mdt_addon_version` from
`MDTAddonVersionService::getCurrentAddonVersion()`, which reads this config value directly. Bump
it afterwards and every row imported in this run carries the **previous** release's addon version
— which silently mis-binds imported MDT strings to the wrong mapping version via the release-date
tie-break in `getMappingVersionForMdtAddonVersion()` (#3380). This was cold-review finding #1 on
PR #3944.

The integer is `preg_replace('/\D/', '', $version)`, mirroring MDT's own
`version:gsub("%.", "")` — so `v6.2.1` → `621`, while `v6.2.0-alpha5` → `6205`. **It is not
monotonic and is not meant to be**; all comparisons happen on release dates, so a "lower" number
for a newer release is expected, not a bug.

After the imports, verify rather than trust:
```sql
SELECT mv.id, d.`key`, mv.version, mv.mdt_addon_version
FROM mapping_versions mv JOIN dungeons d ON d.id = mv.dungeon_id
WHERE mv.created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR) ORDER BY mv.id;
```

## Step 3 — Determine which dungeons to reimport

Get the **latest retail season** and the dungeons attached to it. Do **not** hardcode the
dungeon list — read it from the database each run. The value you need per dungeon is the
**dungeon key** (`Dungeon::$key`, e.g. `priory_of_the_sacred_flame`).

**Use this exact SQL** with the Boost `database-query` tool — it returns the dungeon keys
directly and is the reliable path:

```sql
SELECT d.`key`, d.name
FROM season_dungeons sd
JOIN dungeons d ON d.id = sd.dungeon_id
WHERE sd.season_id = (
    SELECT s.id
    FROM seasons s
    JOIN game_versions gv ON gv.expansion_id = s.expansion_id
    WHERE gv.`key` = 'retail'
    ORDER BY s.start DESC
    LIMIT 1
)
ORDER BY sd.id;
```

**Schema gotchas** (these will bite if you try to build the query from the model relations):
- `seasons` has **no `game_version_id`** column — it has `expansion_id`. The retail game
  version points at the *current* retail expansion via `game_versions.expansion_id` (where
  `game_versions.key = 'retail'`), and the latest retail season always belongs to that
  expansion — that's why the join above works.
- `dungeons` also has **no `game_version_id`** column (it has `expansion_id`); don't join
  dungeons to game versions directly.
- Because of the above, naive `tinker` one-liners like
  `Season::where('game_version_id', …)` fail with "Unknown column". Prefer the SQL above.

## Step 4 — Import each dungeon's mapping

For each dungeon key:

```
docker compose exec -T app php artisan mdt:importmapping <dungeonKey> retail
```

(Command lives in `app/Console/Commands/MDT/ImportMapping.php`, signature
`mdt:importmapping {dungeon} {gameVersion} {--force}`. `gameVersion` is always `retail`. A
numeric season ID may be passed as `dungeon` to import the whole season at once, but prefer
per-dungeon runs so you can observe each output and test it individually.)

Read the output for each dungeon and handle:

- **`Found new template … - we need to add it!` / `Found new type … - we need to add it!`**
  (thrown from `app/Logic/MDT/Entity/MDTMapPOI.php`): add the new case to the matching enum
  in the same folder — `app/Logic/MDT/Entity/MDTMapPOITemplate.php` (templates) or
  `app/Logic/MDT/Entity/MDTMapPOIType.php` (types). Then **notify the user** of every
  template/type you added — they decide what to do with it next. Re-run the import for that
  dungeon afterward.
- **"No change detected"**: the incoming mapping hash matches the previous import
  (`MDTMappingImportService::getMDTMappingHash()`); nothing changed, move on. (`--force`
  overrides this if you ever need to reimport anyway.)
- **New-enemy warnings/errors** that simply indicate an enemy not present in the previous
  mapping: expected and fine. This also covers
  `importEnemiesCannotRecoverPropertiesFromExistingEnemy`,
  `importEnemiesDistanceTooLargeNotTransferringExistingEnemyLatLng`,
  `importEnemyPatrolsFoundPatrolIsEmpty` and
  `importNpcsDataFromMDTNpcNotMarkedForAllDungeons` — the last one is logged at `ERROR` but is
  explicitly documented in `MDTMappingImportService` as expected and recoverable (an NPC that
  already exists globally is being linked to an additional dungeon).
- **`importMapPOIsMissingTranslation` error** with a key like
  `mapping.map_icons.mdt.<comment>` (e.g. `mapping.map_icons.mdt.captive`): MDT introduced a
  new map-icon comment that has no translation yet. This is logged as `ERROR` but **does NOT
  abort the import** (exit code stays 0). Fix it by adding the key under `map_icons.mdt` in
  `lang/en_US/mapping.php` with a sensible English string (e.g. `'captive' => 'Captive'`),
  then **notify the user** so they can refine the wording. Only ever edit `lang/en_US`. No
  re-import is needed — translations resolve at display time, not import time.
- **Other errors:** try to fix them yourself first (the user prefers this). Only pause for the
  user if you get genuinely stuck.

> **Reading import output:** the command emits a very large volume of `DEBUG`/`INFO` log
> lines. Redirect to a file and grep for the markers that matter rather than dumping it all,
> e.g. `... > /tmp/mdt_<key>.log 2>&1; echo "exit=$?"` then
> `grep -ciE 'Found new (template|type)' /tmp/mdt_<key>.log` and
> `grep -iE 'No change detected|MappingChanged|MissingTranslation' /tmp/mdt_<key>.log`.
> A new POI type/template would make the command **exit non-zero**, so an `exit=0` with zero
> `Found new` matches confirms no enum changes were needed.

## Step 5 — Run that dungeon's tests

Two per-dungeon tests run: the **CombatLogRoute** test (the "AutoRoute Creator" test) and the
**CorrectEvents** test (the "RouteCorrection" test).

Tests are grouped by the **PascalCase dungeon name**, NOT the snake_case key (e.g.
`#[Group('PrioryOfTheSacredFlame')]`, not `priory_of_the_sacred_flame`). Locate the dungeon's
test files and read their dungeon-specific `#[Group(...)]` attribute to get the exact group
name — don't guess the conversion. Then:

```
docker compose exec -T app php artisan test --group="<PascalCaseDungeonName>"
```

Handle failures:

- **CorrectEvents (RouteCorrection) fails:** the mapping legitimately changed, so the stored
  fixtures are stale. In the relevant test, temporarily pass `rewriteFixtures: true` to the
  `executeTest(...)` call (base:
  `tests/Feature/Controller/Api/V1/APICombatLogController/CorrectEvents/APICombatLogControllerCorrectEventsTestBase.php`,
  signature `executeTest(string $fixtureName, bool $rewriteFixtures = false)`). Re-run to
  regenerate the fixture, then **revert the parameter back to `false`** and confirm the test
  now passes.
- **CombatLogRoute (AutoRoute Creator) fails:** the user will inspect these manually. **Do not
  block** — record the failure and continue.

**Not every dungeon has test files.** Some (e.g. `theseatofthetriumvirate`) have no
CombatLogRoute/CorrectEvents tests — find the files first
(`grep -rli <dungeonKey> tests/Feature/Controller/Api/V1/APICombatLogController/`) and simply
skip the test step for any dungeon with none.

Repeat Steps 4–5 for every dungeon before moving on.

## Step 6 — Refresh the addon-version map

The config version itself was already bumped in **Step 2.5** — it has to happen before the
imports. Don't bump it again here; just confirm it reads the new version.

Then refresh the MDT addonVersion → release-date map so imported MDT strings resolve to the correct
mapping version (#3380):

```
docker compose exec -T app php artisan mdt:syncaddonversions --refresh
```

This rewrites `database/data/mdt/addon_versions.json` from the GitHub releases and backfills any
mapping versions still missing `mdt_addon_version`. Leave the regenerated JSON staged for review.

## Step 7 — Re-export NPC translations and re-seed

Importing MDT mappings resets NPC names to MDT's values, so re-extract the database names into
the translation files and re-seed (inside Docker):

```
docker compose exec -T app sh -c 'php artisan localization:exportnpcnames && \
  php artisan localization:importnpcnames && \
  php artisan localization:syncnpcnames retail && \
  php artisan mapping:save && \
  php artisan db:seed --database=migrate'
```

These commands rewrite `npcs.php` across **all** locales (not just `en_US`) — that is expected
output of the export/import/sync pipeline, not a violation of the "only edit `en_US`" rule.
Afterward, run `docker compose exec -T app composer run fix` (PhpCsFixer) to normalize the
formatting of the rewritten `npcs.php` files, per the project's finishing-up convention.

## Step 8 — Full suite and wrap-up

1. Run the **full test suite** and diff its failures against the Step 0 baseline log
   (`/tmp/mdt_baseline_test_run.log`). A failure present in both is pre-existing/environmental —
   don't spend time re-diagnosing it. A failure that's **new** (passed at baseline, fails now)
   is a real regression from the bump — root-cause and fix it before wrapping up, don't just
   report it (per this repo's "fix incidental issues" convention).
2. Give the user a **summary**: dungeon mappings imported, any new POI types/templates added,
   and any tests still failing, split into **pre-existing** vs **newly broken by this update**
   (especially any unresolved CombatLogRoute failures). Include the **tracking issue number**
   from Step 1.
3. **Verify visually.** Enemy/pack positions are rendered output, so this is *not* a backend-only
   change — screenshot the explore page of the dungeons whose mapping actually changed with the
   `headless-browser-verify` skill (A/B against the main stack as baseline) and post them on the
   MR. Also remind the user to browse the explore pages themselves.
4. **Also check what the suite can't see:** `MapTilesExistenceTest` fails on its *first* dungeon
   in a worktree (the assets dir isn't mounted), so it never reaches the newly imported ones and
   cannot tell you a new floor is missing map tiles. Confirm directly that
   `git status database/seeders/dungeondata/**` lists no changed `floors.json` / `dungeon.json` —
   if it does, the assets repo needs the tiles and the update is not complete.
5. Commit and push the worktree branch and open the **draft** MR (`Closes #<issue>` in the body),
   then follow CLAUDE.md's "before declaring a MR ready for review" checklist. Leave the tracking
   issue **open** (don't close it yourself unless asked) — the MR closes it on merge.
