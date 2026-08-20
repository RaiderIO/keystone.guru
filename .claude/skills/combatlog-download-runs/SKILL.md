---
name: combatlog-download-runs
description: Find real M+ runs on the Raider.IO API and download their combat logs locally with `combatlog:searchruns` / `combatlog:downloadruns` — filtered by class, spec, dungeon, or key level. Use when validating combat-log extraction features (e.g. spell counters) against real logs, or whenever a task needs "N real logs containing class X". Pipeline internals in combatlog-data-pipeline; parse failures in combatlog-parse-failure-triage.
---

# Downloading real combat logs by run criteria

Two Artisan commands (added in #3826) wrap `RaiderIOApiService` so you can pull real combat logs
matching criteria — "give me 20 high-key runs with a Rogue" — instead of waiting for logs to show
up organically. Built for validating detectors (the spell-counter feature was validated this way);
reuse it whenever a new extraction feature needs real-data evidence (e.g. Warlock tricks →
`--class=warlock`).

Both run inside a worktree/main `app` container: `docker compose exec -T app php artisan ...`.
They work out of the box in dev — no extra credentials.

## Search: what runs exist?

```sh
php artisan combatlog:searchruns --class=rogue --min-level=8 --from-days=14 --limit=40
```

- `--class=*` — class key(s) (`rogue`, `warlock`, ...); resolves to **all specs of that class**.
- `--spec=*` — explicit `CharacterClassSpecialization` ids, unioned with `--class`.
- `--dungeon=` — dungeon key (falls back to translated-name match — `Dungeon->name` is a
  translation key, so the match happens in PHP, not SQL).
- `--min-level=` (default 10), `--from-days=` (completedAt window, default 7), `--limit=`, `--offset=`.

Prints a table of run id / dungeon / key level / member spec ids, plus `found=N total=M`.

## Download: fetch the segments

```sh
php artisan combatlog:downloadruns --class=rogue --min-level=8 --from-days=14 --limit=40 --output-dir=combatlogs/rogue
php artisan combatlog:downloadruns --run=41654528 --run=40847357 --output-dir=combatlogs/controls
```

- Same filter options as searchruns, or `--run=*` to skip the search entirely.
- Files land in `storage/app/<output-dir>/run_<runId>_segment_<segmentId>.<ext>`; a run is
  typically **6–10 segment files, ~40–80 MB total** (one file per boss/trash segment, each with
  its own `RIO_LOG_VERSION` header).
- Segment URLs are **presigned and expire in ~5 minutes** — the command resolves and downloads
  per run back-to-back; don't try to save URLs for later.
- Per-run failures warn and continue; the summary line reports `runs_ok/runs_failed/files/bytes`.

## Process what you downloaded

Each segment file is fed to the normal extraction pipeline individually:

```sh
for f in storage/app/combatlogs/rogue/*; do php artisan combatlog:extractdata "$f"; done
```

- `--force` bypasses the `ParsedCombatLog` idempotency guard on re-runs.
- Cross-segment correlation state resets per file — an event pair spanning a segment boundary
  will not correlate (acceptable; segments split on boss/trash boundaries).
- The extraction summary only prints **non-zero** counters; structured detection logs go to
  **stderr** — don't redirect it to /dev/null if you need them.

### NPC base health from a run (#4094)

`combatlog:extractnpchealth storage/app/combatlogs/<dir> [--dry-run] [--overwrite]` takes a whole
run directory at once (it needs all segments — each NPC appears in only some), reverses the key-level
scaling on every observed NPC's max HP and writes `npc_healths` (missing/placeholder rows by default),
printing a per-NPC comparison table. Only the `Level` column of `combatlog:searchruns` matters:
**prefer a +6 run** — +2..+5 carry Lindormi's −5% on *most* trash, +7+ Fortified (summons exempt) —
and use a run of another level as a cross-check (`--dry-run` shows Δ vs the stored value). Runs of
another dungeon whose healths are already good are the control that proves the factor. Finish with
`mapping:save`. Mechanics and the factor's derivation: combatlog-data-pipeline skill, "NPC health
from logs".

## Practical notes for validation work

- **The searchable pool is only runs with uploaded combat logs** — for a narrow filter (one
  class, high keys) expect single digits per week, not hundreds. Widen `--from-days` before
  lowering `--min-level`.
- **Negative controls**: search *without* the class filter and pick runs whose member spec ids
  contain none of the target class's specs — logs where the target ability cannot occur. (Races
  are not filterable: any party can still contain e.g. a Night Elf racial.)
- **Record the run ids of any validation corpus — they are the reproducible artifact, not the
  files.** Segment files are disposable (hundreds of MB, deleted with the worktree) and their URLs
  are presigned and expire in minutes, but a run id is permanent: `--run=<id> --run=<id> ...`
  re-downloads exactly that corpus later. A corpus described only by its *filters* is NOT
  reproducible — `--from-days` is relative to now, so the same command returns a different set
  tomorrow. Put the ids in the PR body of whatever the corpus validated, before deleting anything.
  If they were lost, re-running the identical searches the same day usually recovers them; confirm
  by checking that run ids you can still name (from filenames quoted in notes) appear in the result.
- **Localized clients**: spell/NPC *names* in logs may be non-English (`"Schattenmimik"` =
  Shadowmeld); always match on spell **ids**, never names — names are only safe for
  same-log correlation (e.g. matching a cast to its targeting debuff).
- Downloads are disposable (not in git); delete `storage/app/<output-dir>` when done or leave it
  for worktree teardown to clean.

## Key files

- Commands: `app/Console/Commands/CombatLog/{SearchCombatLogRuns,DownloadCombatLogRuns}Command.php`
  + shared filter trait `ResolvesCombatLogSearchFilter.php` (same folder).
- API surface: `app/Service/RaiderIO/RaiderIOApiService.php`
  (`searchAdvancedRuns`, `getCombatLogSegmentsForRun`) and its `Dtos/`.
- The production ingestion equivalent is `app/Jobs/CombatLog/ProcessCombatLogSegments.php` —
  these commands mirror its download/validity logic for local use.
