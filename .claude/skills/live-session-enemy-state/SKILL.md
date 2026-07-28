---
name: live-session-enemy-state
description: End-to-end guide to the live-session enemy-state subsystem (killed / obsolete / overpulled / player positions) — the three DB tables, services, broadcast events, MapContext serialization, and the frontend enemy subclass + visual-overlay rendering. Use for any work on live combat-log streaming (#3275 epic) that touches enemy state on the map.
---

# Live-session enemy state

Live sessions stream WoW combat-log lines (see #3275/#3281/#3282) that are processed incrementally; the
result is per-enemy state shown on the dungeon map in real time. This subsystem is split across backend
persistence + services + broadcast events and a frontend echo/model/visual stack. The state is **non-obvious
and overloaded**, so read this before touching it.

## The three enemy states (and a 4th: player positions)

| State | Stored in | Meaning | Map visual |
|-------|-----------|---------|------------|
| **killed** | `live_session_killed_enemies` | Killed AND part of the planned route (assigned to a kill zone) | green check `fa-check-circle` `text-success` |
| **overpulled** | `live_session_overpulled_enemies` | Killed but NOT in any kill zone ("oopsie") | orange plus `fa-plus-circle` `text-warning` |
| **obsolete** | `live_session_obsolete_enemies` | A planned enemy we can now SKIP to compensate for an overpull | red cross `fa-times-circle` `text-danger` |
| (player positions) | `live_session_player_positions` | Live player dots | — (separate render path) |

A detected kill is *either* on-route (→ killed) or off-route (→ overpulled); the states are meant to be
mutually exclusive. **All three state tables are keyed by `(live_session_id, npc_id, mdt_id)`**, NOT
`enemy_id` — they resolve to live `Enemy.id` by joining `npc_id`+`mdt_id`+`mapping_version_id`. Do not rely
on an `enemy_id` column (the legacy one on the overpulled table is dead/being dropped).

## Backend data flow

```
APILiveSessionCombatLogController (ingest endpoint, #3277)
  → LiveSessionCombatLogBuffer (gzipped combat-log lines, #3278)
    → ProcessLiveSessionCombatLogBuffer (queued job)
      → LiveSessionBufferProcessingService::processBuffer()
          parseCombatLogStreaming via CombatLogDungeonRouteFilter (valid NPC ids set)
          → processKilledEnemies()   pairs EnemyEngaged + EnemyKilled result events,
                                      resolveEnemy() = npc_id + nearest position among
                                      enemyRepository->getAvailableEnemiesForDungeonRouteBuilder()
          → processPlayerPositions() last-known AdvancedCombatLogEvent per player GUID
```

Key files (all absolute under repo root):
- `app/Service/LiveSession/LiveSessionBufferProcessingService.php` — orchestrates a buffer; `processKilledEnemies()`, `processPlayerPositions()`, `resolveEnemy()` (npc_id match + nearest lat/lng). `getResultEvents()` returns events in **temporal/stream order** (relied on for "current pull" logic).
- `app/Service/LiveSession/LiveSessionCombatStateService.php` — `setKilledEnemy()` (`firstOrCreate`, returns `wasRecentlyCreated`), `replaceObsoleteEnemies()` (delete+reinsert), `getObsoleteEnemyIds()`, `setPlayerPosition()`. `resolveEnemyIds()` does the npc/mdt/mapping-version join back to `enemies.id`.
- `app/Service/LiveSession/OverpulledEnemyService.php` — `getRouteCorrection(LiveSession): DungeonRouteCorrection`. Reads the overpulled table, groups by kill zone, walks kill zones with `index >` the overpull's to find skippable enemy forces → fills obsolete enemy ids + corrected enemy forces. ⚠️ Its private `getOverpulledEnemyForces()` raw SQL has historically been buggy (wrong `dungeon_routes` join; relied on the dead `enemy_id`) — verify before trusting it.
- `app/Service/LiveSession/DungeonRouteCorrection.php` — value object: `obsoleteEnemies` collection + `enemyForces`.
- `app/Models/LiveSession/LiveSession.php` — relations `killedEnemies()`, `overpulledEnemies()`, `obsoleteEnemies()`, `playerPositions()`; `mapContextKilledEnemyIds()`, `getEnemies()` (overpulled→Enemy models), `mapContextPlayerPositions()`. `boot()` cascades deletes.
- `app/Models/LiveSession/LiveSession{Killed,Overpulled,Obsolete}Enemy.php`, `LiveSessionPlayerPosition.php`.
- `app/Console/Commands/Scheduler/LiveSession/CleanupExpiredLiveSessions.php` — cleans the state tables on expiry.

## Broadcast events → map reaction

| Event (`app/Events/...`) | `broadcastAs()` | Frontend handler |
|--------------------------|-----------------|------------------|
| `Models/LiveSession/EnemyKilledEvent` | `enemy-killed` | `echo/messagehandler/listen/models/livesession/enemykilled.js` |
| `Models/LiveSession/PlayerMovedEvent` | `player-moved` | `echo/.../livesession/playermoved.js` |
| `LiveSession/RouteCorrectionEvent` | `route-correction` | `echo/messagehandler/listen/livesession/routecorrection.js` |
| `LiveSession/OverpulledEnemy/OverpulledEnemyChangedEvent` | `overpulledenemy-changed` | `echo/.../models/overpulledenemy/changed.js` |
| `LiveSession/OverpulledEnemy/OverpulledEnemyDeletedEvent` | `overpulledenemy-deleted` | `echo/.../models/overpulledenemy/deleted.js` |

- The buffer pipeline has **no `Auth`** — broadcast events with `$liveSession->user` as the user (see `EnemyKilledEvent`/`PlayerMovedEvent`).
- Register a new handler in `echo/echohandler.js` and the message in `echo/message/messagefactory.js` (+ a `ModelMessage`/`Message` under `echo/message/listen/...`).
- `RouteCorrectionEvent.enemy_ids` carries **obsolete** ids only (merge of `getRouteCorrection()->getObsoleteEnemies()` + `combatStateService->getObsoleteEnemyIds()`), not killed.

## Initial map load: MapContext serialization

`app/Logic/MapContext/Map/MapContextLiveSession.php::toArray()` is what the page boots with:
```php
'overpulledEnemies' => $liveSession->getEnemies()->pluck('id'),
'obsoleteEnemies'   => $routeCorrection->getObsoleteEnemies()
                         ->merge($combatStateService->getObsoleteEnemyIds($liveSession))->unique()->values(),
'enemyForcesOverride'=> $routeCorrection->getEnemyForces(),
'killedEnemies'     => $liveSession->mapContextKilledEnemyIds(),
'playerPositions'   => $liveSession->mapContextPlayerPositions(...),
```
Obsolete is **unioned (persisted + recomputed) with `unique()`**, so persisting obsolete via
`replaceObsoleteEnemies()` is safe and is needed so late-joining clients get correct state. The frontend
reads these via `mapcontext/mapcontextlivesession.js` (`getOverpulledEnemies()`, `getObsoleteEnemies()`,
`isKilledEnemy()`, `getPlayerPositions()`).

## Frontend: enemy subclasses + visual overlay

Enemy map objects are created by a **factory keyed on map context** in
`mapobjectgroups/enemymapobjectgroup.js::_createMapObject()` (mirrors `AdminEnemy`/`PridefulEnemy`):
admin → `AdminEnemy`, live session → `LiveSessionEnemy`, search/explore → `SearchEnemy`, seasonal
`prideful` → `PridefulEnemy`, else base `Enemy`. **Per-context state belongs on the subclass, not the base
`Enemy`.** New `custom/**` JS files are auto-bundled by webpack (no manual registration).

⚠️ The same `obsolete`/`overpulled` visual is historically **overloaded**: in the route search/explore
context `setObsolete(true)` means *excluded* and `setOverpulledKillZoneId(1)` means *included*
(`inline/common/search/filters/filterexcludedenemies.js`, `filterincludedenemies.js`,
`inline/common/maps/dungeonroutesearchsidebar.js`). Keep search state on `SearchEnemy`, live state on
`LiveSessionEnemy`.

Visual rendering stack (one enemy → its overlay icon):
- `models/enemy.js` — base model. Expose a generic `getStateOverlay()` (returns `null` or
  `{iconClass, colorClass}`) that subclasses override; keep safe default accessors (`isObsolete()=>false`,
  `getOverpulledKillZoneId()=>null`, `isKilled()=>false`) because shared logic in `models/killzone.js` and
  `inline/common/maps/killzonessidebar/rowelementkillzone.js` calls them generically.
- `enemyvisuals/enemyvisual.js` — registers state-change signals (rebuilds the visual) and sets opacity.
- `enemyvisuals/enemyvisualmain.js` (`_getTextWidth`), `enemyvisualmainenemyportrait.js`
  (`_getTemplateData`/`refreshSize`), `enemyvisualicon.js` (`_shouldRebuildOnZoom`) — consult the overlay.
- `handlebars/map_enemy_visual_enemy_portrait_template.handlebars` — the overlay markup. **Handlebars are
  compiled at build time** (`webpack.mix.js` → `resources/assets/js/handlebars.js`); edit the `.handlebars`
  source then `npm run build` (or `npm run dev` / `composer run dev`).
- State-change signals are granular (`'killed:changed'`, `'obsolete:changed'`, `'overpulled:changed'`,
  `'included:changed'`, `'excluded:changed'`). The visual registers the union; registering for a signal an
  enemy never fires is harmless.

## Gotchas / conventions
- Tests use a **shared live MySQL DB** (no `RefreshDatabase`); clean up created rows in a `try/finally`.
  Pattern files: `tests/Feature/Jobs/LiveSession/ProcessLiveSessionCombatLogBufferTest.php` (real combat
  logs, `PublicTestCase`) and `tests/Feature/Controller/Ajax/AjaxOverpulledEnemyControllerTest.php`
  (factories, `Event::fake()`).
- The phpunit DB runs non-strict (`NO_ENGINE_SUBSTITUTION` without `STRICT_TRANS_TABLES`), so a missing
  `$fillable` / NOT-NULL column silently becomes `0` — a class of bug that hides until production. Watch for
  it on any new state table.
- Idempotency across re-processed buffers: rely on `firstOrCreate`/`firstOrNew` + `wasRecentlyCreated` to
  gate broadcasts; gate expensive recompute/rebroadcast behind a "changed" flag.
- Run everything in Docker (`docker compose exec -T app php artisan ...`). Finish with `composer run fix`
  + `composer run analyse`.
- Related: see the `repository-pattern` and `new-map-view` skills for adjacent conventions.
