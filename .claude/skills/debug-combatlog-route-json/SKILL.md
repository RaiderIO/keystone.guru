---
name: debug-combatlog-route-json
description: Replay a combat-log-route JSON request body (e.g. downloaded from production, or attached to a bug report) through the Auto Route Creator locally, at full debug-level structured logging, so you can see exactly why an NPC/pull/spell ended up (or didn't end up) where it did. Use whenever debugging a report like "this run's bosses/pulls/enemies didn't show up correctly" and you have (or can get) the JSON body that was POSTed to `api/v1/combatlog/route`. Not for the parser that turns a raw WoW combat log into this JSON in the first place (combatlog-parsing-internals/combatlog-parse-failure-triage) — this skill starts one step later, from the JSON body itself.
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
