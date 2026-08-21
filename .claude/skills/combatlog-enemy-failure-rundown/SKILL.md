---
name: combatlog-enemy-failure-rundown
disable-model-invocation: true
argument-hint: "<dungeon name> [staging]"
description: Fetch a dungeon's Auto Route Creator enemy failures from production (or staging) into the local DB, run the cluster analysis, and hand back a "look here" rundown of what is most likely wrong with the mapping. Invoke as `/combatlog-enemy-failure-rundown ruby life pools` — only the dungeon name varies; everything else is in here. Not for raw combat-log *parse* failures (those are Sentry now — combatlog-parse-failure-triage).
---

# /combatlog-enemy-failure-rundown `<dungeon name>` — enemy-failure rundown for one dungeon

`$ARGUMENTS` is the dungeon, as Wotuu would say it ("ruby life pools", "Den of Nalorakk",
"cinderbrew"); an optional trailing `staging` pulls from staging instead of production.
**Output is a rundown — a ranked list of places in the mapping to review, with a verdict each.
Never edit mapping data from this: Wotuu reviews the footage and edits the mapping himself.**

Background for the machinery (APIs, import command, cluster analysis, `source` column):
`debug-combatlog-route-json` §1 and the #4220/#4222/#4224/#4227 MRs. This skill is only the
runbook.

## Model

Run on **Opus** (the default). Steps 1–3 and 5 are short and mechanical, so Sonnet's rate advantage
is worth cents here, and step 4 is builder debugging. Step 4 is delegated to subagents either way
(see there) — on a Sonnet session pass `model: "opus"` to them; on Opus/Fable let them inherit.

## 0. Where to run it

Run from whichever checkout you are in — the import lands in *that* stack's `combat_log_route_enemy_failures`
table, tagged `source = production|staging`, and only replaces rows previously imported from the
same host, so it is safe on the main checkout's shared DB. **Do not replay post bodies on the main
checkout** (each replay creates a `DungeonRoute`); if step 4 is needed, do it from a worktree
(`sh/worktree.sh create <issue>-<slug>`, `worktree-docker` skill).

Credentials are in `~/.config/keystone-guru/combatlog-production-basic-auth` and
`.../combatlog-staging-basic-auth` (one `user:password` line). They are piped on **stdin** — the
container cannot see `~/.config`, so `--credentials-file` with a host path does not work. The
account carries the `ai_agent` role (read-only combat-log endpoints); never ask for an admin
account on production.

## 1. Resolve the dungeon key

Dungeon keys are not uniform (`rubylifepools`, `den_of_nalorakk`, `therubysanctum`) — never guess
one. Match the spoken name against the seeder's slug/name:

```bash
python3 - "$ARGUMENTS" <<'PY'
import json, re, sys
needle = re.sub(r'[^a-z0-9]+', '-', sys.argv[1].lower().replace('staging', '')).strip('-')
for d in json.load(open('database/seeders/dungeondata/dungeons.json')):
    if needle in d['slug'] or needle.replace('-', '_') in d['name']:
        print(d['id'], d['key'], d['slug'], d['name'])
PY
```

One hit → that is `<key>`. Several (e.g. "ruby" matches two) → pick the M+ one the current season
has, or ask. None → say so and stop; do not fall back to a lookalike.

## 2. Import the failures (+ post bodies)

```bash
mkdir -p storage/app/enemy-failure-bodies/<key>
docker compose exec -T app php artisan combatlog:importenemyfailures <key> \
    --download-post-bodies=storage/app/enemy-failure-bodies/<key> \
    < ~/.config/keystone-guru/combatlog-production-basic-auth
# staging: add --host=staging and pipe combatlog-staging-basic-auth instead
```

Useful narrowing: `--since=<date>` (e.g. since the last mapping change/release),
`--mapping-version=<remote id>` (remote id — only equal to the local one when both DBs were seeded
from the same seeder state). Report the row count the command prints; zero rows for an active
dungeon is itself a finding (wrong host? key? the dungeon not in the active season?).

Imported rows keep production's `dungeon_route_id`, so the admin heatmap sidebar's "matching
routes" links stay empty for them — expected, not a bug.

## 3. Analyse

```bash
docker compose exec -T app php artisan combatlog:analyzeenemyfailures <key> --format=markdown
# --hide-low-volume to drop the `*` rows; --mapping-version=<local id> for a non-current version
```

The analysis runs against the dungeon's **current local mapping version** — if it says "0
clusters" while step 2 imported plenty, check `--mapping-version` (remote ids differ from local
ones; the command only clusters failures whose `mapping_version_id` matches). The header line tells
you how many failures were skipped for that reason.

Verdicts, most urgent first (`*` on the `#` = low volume, below the configured min failures/routes):

| Verdict | Means | What to look at |
|---|---|---|
| `npc_not_mapped` | mapping version has no enemy with this npc id at all | a whole NPC/pack is missing from the mapping |
| `no_enemy_in_range` | the npc exists but none within engagement range of the cluster | a pack is missing or misplaced *here* |
| `enemies_exhausted` | enemies in range, routes still fail | the game has more of them than the mapping |
| `wrong_floor_artifact` | no same-floor enemy in range but one is on another floor | usually the builder's floor inference, not mapping — mention, deprioritise |

## 4. Spot-check the top clusters (optional, worktree only)

When a top cluster's verdict is not self-evident, replay the downloaded bodies so the builder's
real decisions are logged locally with local ids:

```bash
docker compose exec -T app php artisan combatlog:ingestcombatlogroutejson storage/app/enemy-failure-bodies/<key>
```

Then **delegate each cluster you want checked to its own subagent** (`Agent` tool,
`subagent_type: "general-purpose"`; add `model: "opus"` when this session runs on Sonnet) rather
than reading logs and bodies in the main session — the log grepping and body diffing is bulky and
only the conclusion belongs here. Run the subagents for different clusters in parallel. Give each:
the worktree path, the cluster row from the table (npc id, floor, lat/lng, nearest enemy id,
verdict), the bodies dir, and this brief:

> Follow the `debug-combatlog-route-json` skill §2–§5 in `<worktree path>`: pick a body from
> `<bodies dir>` that contains npc `<id>`, replay it through the builder at debug log level, and
> report in ≤ 10 lines why the builder failed to place that npc near floor `<f>` lat/lng `<…>` —
> which candidate enemies it considered, which it rejected and why, whether the mapping actually
> lacks an enemy there or the failure is a floor-inference/builder artefact. Do not change any
> mapping or seeder data. Return only the conclusion, not log excerpts.

Fold each returned paragraph into step 5.3 for that cluster. The admin heatmap page
(`/admin/tools/combatlog/route/enemy-failures`, cluster layer, mapping-version filter) shows the
same clusters as markers — note it only honours `?dungeon_id=` when it matches the admin user's
context dungeon, and facade mode merges every floor's clusters onto the facade floor.

## 5. Deliver the rundown

Reply (or post as a GitHub issue comment if asked) with:

1. One header line: dungeon, host, mapping version, rows imported, `--since` if used, clusters found / skipped.
2. The markdown table from step 3, verbatim (it is already the issue-ready form).
3. Per top cluster (non-`*`, top ~5): one sentence each — where (floor, lat/lng, nearest enemy id), the verdict in plain words, and the concrete thing to check in the mapping editor. Call out `wrong_floor_artifact` rows as probable builder artefacts so they are not treated as mapping bugs.
4. What was *not* covered: low-volume rows hidden, failures skipped for a mapping-version mismatch, bodies not replayed.

Do not open issues or touch mapping/seeder data unless explicitly told to.
