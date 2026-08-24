---
name: debug-combatlog-route-json
description: Replay a combat-log-route JSON request body (from production or a bug report) through the Auto Route Creator locally with debug-level structured logging, to see why an NPC/pull/spell ended up where it did. Also covers the per-dungeon `Rules/` exception framework (`app/Service/CombatLog/Builders/Rules/`) for fixing a dungeon-specific mismatch once found — e.g. two overlapping floors/paths stealing each other's kills, or a boss that despawns instead of dying. Use when debugging "this run's bosses/pulls/enemies didn't show up correctly", when a fix needs a new dungeon-specific rule, and you have the JSON POSTed to `api/v1/combatlog/route`. Not for parsing the raw WoW log into that JSON (combatlog-parsing-internals / combatlog-parse-failure-triage) — this starts one step later.
---

# Debugging a combat-log-route JSON body

The Auto Route Creator (ARC) turns a JSON body — `metadata`/`settings`/`challengeMode`/`roster`/
`npcs`/`spells`/`playerDeaths` — into a `DungeonRoute` with `KillZone`s, via
`POST api/v1/combatlog/route` (`APICombatLogController::store()` →
`CombatLogRouteDungeonRouteServiceInterface::convertCombatLogRouteToDungeonRoute()` →
`CombatLogRouteDungeonRouteBuilder`, which extends the shared `DungeonRouteBuilder` spatial-matching
base). When something about the resulting route is wrong (an NPC missing from every pull, an enemy
assigned to the wrong pull, a boss placed twice), the fastest way to find out why is to **run the
exact same code path locally, at debug-level structured logging, against the exact JSON body that
produced the bad result** — rather than reasoning about the builder code in the abstract.

This skill is the mechanics of getting from "I have the JSON" to "I can see the decision the code
made and why." It is deliberately not about any specific bug — see `combatlog-data-pipeline` for
how the wider ingest pipeline fits together, and read the builder classes themselves
(`app/Service/CombatLog/Builders/`) for what a given log line actually means.

## 0. Get a worktree

Use a dedicated worktree (`worktree-docker` skill / `sh/worktree.sh create`) rather than the main
checkout — you're going to create throwaway `DungeonRoute`s and want your own DB. If the bug is
against a specific in-flight branch (e.g. a fix that depends on another issue's branch), branch off
that instead of `origin/master`: `sh/worktree.sh create <issue>-<slug> <base-branch>`.

## 1. Get the JSON body

This is whatever was actually POSTed to `api/v1/combatlog/route` (or `.../route/correct`) — from a
downloaded production request log, a bug report attachment, or hand-built. Put it somewhere under
the repo (e.g. `tmp/`) and copy it into the worktree's `app` container:

```bash
docker cp tmp/some_run.json <worktree-project>-app-1:/tmp/some_run.json
```

(Find the container name with `docker compose ps` from inside the worktree, or `docker ps | grep app-1`.)

### Pulling production enemy failures and their post bodies (#4222)

> The end-to-end per-dungeon runbook (resolve the dungeon key → import → `combatlog:analyzeenemyfailures`
> → rundown) is the user-invocable `combatlog-enemy-failure-rundown` skill; this section is only the
> import mechanics it relies on.

When the question is "why does this dungeon produce so many enemy failures", the bodies live on
production and the failures in its `combat_log_route_enemy_failures` table. Two admin-only API
endpoints expose them — `GET api/v1/combatlog/enemy-failures/{dungeon slug}` (cursor-paginated rows,
each with the route's public key) and `GET api/v1/combatlog/route/{public key}/post-body` — and
`combatlog:importenemyfailures` drives both:

```bash
# replaces the local failures of the dungeon with production's, and downloads every failing route's
# body into storage/app/enemy-failure-bodies/<key>/ for replay. Credentials are one "user:password"
# line piped on stdin - the container cannot see ~/.config, so don't try --credentials-file with a
# host path.
docker compose exec -T app php artisan combatlog:importenemyfailures <dungeon key> \
    --download-post-bodies=storage/app/enemy-failure-bodies/<dungeon key> \
    < ~/.config/keystone-guru/combatlog-production-basic-auth

# then replay them all (each becomes a local DungeonRoute and re-records failures locally with
# your local mapping version and real local route ids):
docker compose exec -T app php artisan combatlog:ingestcombatlogroutejson storage/app/enemy-failure-bodies/<dungeon key>
```

`--host=staging`, `--mapping-version=<remote id>`, `--since=<date>` narrow the pull. Imported rows
keep production's `dungeon_route_id`/`mapping_version_id` — the sidebar's "matching routes" links
don't resolve for them, which is another reason to replay the bodies rather than read the imported
rows when you need the builder's actual decisions.

## 2. Write a debug driver script

Call the service directly instead of going through HTTP — it skips validation noise and lets you
inspect the built `DungeonRoute` object directly afterward. This also sidesteps the fact that the
`store()` HTTP path runs in the long-lived app/Octane process, where you can't cheaply flip the log
level for one request; a `tinker` process is its own short-lived process, so config overrides at the
top of the script apply cleanly and don't affect anything else.

```php
<?php
// /tmp/debug_route.php — run via: docker compose exec -T app php artisan tinker --execute="require '/tmp/debug_route.php';"

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;

// See §3 — both of these are required, config alone is not enough.
config(['app.log_level' => 'debug']);
\App\Logging\StructuredLogging::flushConfigCache();

$json = json_decode(file_get_contents('/tmp/some_run.json'), true);
$dto  = CombatLogRouteRequestDto::createFromArray($json);

$route = app(CombatLogRouteDungeonRouteServiceInterface::class)
    ->convertCombatLogRouteToDungeonRoute($dto);

echo "DungeonRoute ID: {$route->id}\n";
foreach ($route->killZones as $kz) {
    echo "KillZone #{$kz->index}: " . $kz->killZoneEnemies->pluck('npc_id')->implode(',') . "\n";
}
```

Adapt the bottom half to whatever you're actually diagnosing — e.g. diffing every `npcId` in the
JSON against every `npc_id` that ended up in any `killZoneEnemies` collection is the fastest way to
spot NPCs the builder silently dropped:

```php
$loggedNpcIds     = collect($json['npcs'])->pluck('npcId')->unique();
$assignedNpcIds   = $route->killZones->flatMap(fn($kz) => $kz->killZoneEnemies->pluck('npc_id'))->unique();
$loggedNpcIds->diff($assignedNpcIds)->each(fn($id) => print("MISSING npc_id={$id}\n"));
```

Copy it in and run it:

```bash
docker cp /tmp/debug_route.php <worktree-project>-app-1:/tmp/debug_route.php
docker compose exec -T app php artisan tinker --execute="require '/tmp/debug_route.php';" 2>&1
```

Re-running is cheap — each run creates a new `DungeonRoute` (the JSON's `settings.temporary: true`
means it expires on its own; no manual cleanup needed for a throwaway worktree DB).

## 3. Getting to debug-level structured logs

The builder logging (`DungeonRouteBuilderLogging`, `CombatLogRouteDungeonRouteBuilderLogging`, …)
goes through `App\Logging\StructuredLogging`, which gates on **`config('app.log_level')`**, not
Laravel's usual `logging.channels.*.level`. In this app that config defaults to `warning`, so the
`->debug()`/`->start()`/`->end()` calls that narrate *why* an enemy was or wasn't matched are
silently dropped unless you override it:

```php
config(['app.log_level' => 'debug']);
\App\Logging\StructuredLogging::flushConfigCache(); // it caches the parsed level statically — must flush after changing config
```

Do this as the very first thing in the script, before anything else touches the container/DTO —
`StructuredLogging` also caches the resolved Monolog channel per logger instance, and instances are
created as soon as anything in the request path constructs a `*Logging` class.

You do **not** need to touch `logging.channels.*.level` — the channel-level filters (`warning` on
`stderr`/`daily` by default) sit *behind* this gate and are irrelevant once `app.log_level` is
`debug`, since `StructuredLogging::shouldLog()` already dropped the call before it reaches Monolog.

**Never edit `.env`** to do this (see the project's `no-env-file` convention) — the `config()` /
`flushConfigCache()` override above is scoped to the tinker process and leaves the running app/nginx
containers untouched.

## 4. Finding the log output

Structured logs land in the `daily` channel, which is **date-suffixed**, not `laravel.log`:

```bash
docker compose exec -T app cat storage/logs/laravel-$(date +%F).log
```

(`storage/logs/laravel.log` without a date will never exist under the `daily` driver — don't waste
time looking for it.)

Grep by whatever identifies the thing you're chasing — an `npc_id`, a `spawnUid`/unique id, a
`kill_zone_id`. Each `StructuredLogging::start()`/`end()` pair groups the context of one method
call, so grepping for an id and reading a few lines around each hit usually shows the full decision:
which candidate enemies were considered, which one (if any) was picked, and why.

If a log line for the thing you expect **never appears at all**, that's itself the finding — it
means the code path that would have logged it was never reached (e.g. the item was filtered out
*before* the method that would log about it, as opposed to being logged as "not found" or "too far
away" *inside* that method). Trace backward from there: which earlier filter (an NPC-eligibility
check, a mapping-version scope, a floor check) could have dropped it silently.

## 5. Isolating one query/decision further

When the builder-level logs point at a specific repository call (e.g. "this NPC never shows up as a
candidate at all"), it's often faster to call that repository method directly in the same tinker
session than to keep re-running the whole builder:

```php
$route = \App\Models\DungeonRoute\DungeonRoute::find($routeId); // from the earlier run's echo
$mv    = $route->mappingVersion;
$repo  = app(\App\Repositories\Interfaces\Npc\NpcRepositoryInterface::class); // resolves the real (possibly Swoole-wrapped) binding
$ids   = $repo->getInUseNpcIds($mv);
$ids->contains($suspectNpcId); // is it even eligible before spatial matching gets involved?
```

Querying the underlying tables directly (`DB::table('npc_enemy_forces')->where('npc_id', $id)->get()`,
etc.) is the next step down when a repository method's result doesn't match your mental model of the
data — don't assume the data looks like you expect, look at it.

## 6. Once you have a fix

Turn what you found into a regression test near the method that was actually wrong (repository,
builder, DTO — wherever the bug lived), following `writing-tests`. A test built directly from the
downloaded JSON is usually too heavy/slow for a unit test; prefer isolating the specific condition
(e.g. two DB rows with a particular relationship) the way the JSON replay showed it, the same way
you would for any other regression test.

## 7. When the fix is dungeon-specific: `Rules/`

Sometimes the replay in §2-4 shows a mismatch that spatial matching genuinely cannot resolve on its
own — two sets of enemies at near-identical ingame X/Y with no way to tell them apart (stacked
floors, or a bridge over a path), or a boss that despawns instead of dying so its death never reaches
us at all, not in the combat log and not in what Raider.IO sends. Those go in
`app/Service/CombatLog/Builders/Rules/` (introduced in #4272), a small per-dungeon exception
framework that lets `DungeonRouteBuilder` apply dungeon-specific corrections during matching. Read
`DungeonRouteBuilderRuleInterface` first — its docblock is the authoritative description of each
hook — then copy the shape of an existing rule rather than starting from scratch:

- `TheBlindingValeBridgeRule` — a **hard** exclusion (`isEnemyEligible()`), used when two enemy sets
  must never compete even when nothing else matches. Its docblock explains why: a *preference* tier
  was tried first and lost, because it outranks distance entirely and overrode a correct match (a
  kill standing on top of the right enemy resolved 15 yards away instead). Excluding candidates
  outright avoided that failure mode.
- `BossKillFloorCutoffRule` — a **soft** exclusion (`isEnemyEligibleOnFirstPass()` only), used when
  a hard exclusion would drop an enemy — and its enemy forces — from the route entirely if no better
  candidate exists. The builder retries without the exclusion on a first-pass miss, falling back to
  pre-rule behaviour rather than losing the enemy. It's also an explicit dungeon allowlist rather
  than a global default: enabling it everywhere shifted pull counts across seven other dungeons'
  regression runs.
- `KingsRestDespawningEnemiesRule` — uses `onEnemyDied()` to award a kill for an npc that despawns,
  keyed off a neighbour whose death *does* reach us. Awarding must be idempotent — the builder will
  happily award the same npc twice otherwise.

**How a rule gets registered:** add its class to the `RULES` array in `DungeonRouteBuilder`
(`app/Service/CombatLog/Builders/DungeonRouteBuilder.php`). The constructor instantiates every rule
fresh per `build()` and filters to the ones whose `appliesToDungeon()` returns true for this route's
dungeon — rules carry mutable state (how far into the run we are), so they are never singletons and
never shared across dungeons. A rule that logs anything (most do) needs a matching method added to
`DungeonRouteBuilderLoggingInterface` (and its implementation) — see
`theBlindingValeBridgeRuleBridgeEnemyPackGroupsBlocked()` /
`kingsRestDespawningEnemiesRuleEnemyKillsAwarded()` for the naming pattern.

**Gotcha: only one of the two builders advances rule state.** Both `CombatLogRouteDungeonRouteBuilder`
and `ResultEventDungeonRouteBuilder` extend `DungeonRouteBuilder` and share the eligibility loop
(`isEnemyEligible()` / `isEnemyEligibleOnFirstPass()` fire on both paths), but only
`CombatLogRouteDungeonRouteBuilder` calls `notifyRulesEnemyDied()` — `ResultEventDungeonRouteBuilder`
never does (see the comment in its `EnemyKilled` branch). So on the ResultEvent path, a rule's
`onEnemyDied()` never fires and any state it would have advanced (e.g.
`TheBlindingValeBridgeRule::$lightwardenRuiaKilled`) stays frozen at its initial value for the whole
build — the rule is not "off" there, it is stuck in its starting state. Wiring that path up is
deliberately left as a separate issue rather than folded into #4272, because unlike the CombatLogRoute
path it has no pinned route fixtures to prove the change is safe.

**Verify a new rule against pinned fixtures**, not just its own unit test: each dungeon with a rule
has a `tests/Unit/App/Service/CombatLog/Builders/Rules/*RuleTest.php` (the rule in isolation) and
usually a `tests/Feature/.../Rules/*RuleMappingTest.php` plus an
`tests/Feature/Controller/Api/V1/APICombatLogController/CombatLogRoute/**/APICombatLogControllerCombatLogRoute<Dungeon>Test.php`
end-to-end fixture built from a real combat log (`combatlog-route-tests` skill). Run the full
dungeon's fixture test, not just the new rule's unit test — `BossKillFloorCutoffRule`'s allowlist
note above is exactly the kind of regression that only a real fixture catches.
