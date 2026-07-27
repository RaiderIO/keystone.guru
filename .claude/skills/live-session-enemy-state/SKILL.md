---
name: live-session-enemy-state
description: End-to-end guide to the live-session enemy-state subsystem (killed / obsolete / overpulled / in-combat / player positions) — the four DB tables, services, broadcast events, MapContext serialization, and the frontend enemy subclass + visual-overlay rendering. Use for any work on live combat-log streaming (#3275 epic) that touches enemy state on the map.
---

# Live-session enemy state

Live sessions stream WoW combat-log lines (see #3275/#3281/#3282) that are processed incrementally; the
result is per-enemy state shown on the dungeon map in real time. This subsystem is split across backend
persistence + services + broadcast events and a frontend echo/model/visual stack. The state is **non-obvious
and overloaded**, so read this before touching it.

## The four enemy states (and a 5th: player positions)

| State | Stored in | Meaning | Map visual |
|-------|-----------|---------|------------|
| **killed** | `live_session_killed_enemies` | Killed AND part of the planned route (assigned to a kill zone) | green check `fa-check-circle` `text-success` |
| **overpulled** | `live_session_overpulled_enemies` | Killed but NOT in any kill zone ("oopsie") | orange plus `fa-plus-circle` `text-warning` |
| **obsolete** | `live_session_obsolete_enemies` | A planned enemy we can now SKIP to compensate for an overpull | red cross `fa-times-circle` `text-danger` |
| **in-combat** | `live_session_in_combat_enemies` | Currently engaged (alive), recomputed and fully replaced every buffer pass | crosshairs `fa-crosshairs` `text-danger` |
| (player positions) | `live_session_player_positions` | Live player dots | — (separate render path) |

A kill resolved by the **buffer pipeline** is *either* on-route (→ killed) or off-route (→ overpulled) —
`LiveSessionOverpullDetectionService::processResolvedKills()` branches on kill-zone membership, a structural
property, so that path can't produce both. The **manual mark path**
(`AjaxOverpulledEnemyController::store()`, used by the map editor's "mark overpulled" UI) does **not**
enforce this: it accepts arbitrary `enemy_ids` with no check against kill-zone membership or the killed
table (`OverpulledEnemyFormRequest` only validates shape), so an enemy can end up in both
`live_session_killed_enemies` and `live_session_overpulled_enemies`. In that case overpulled wins on the map
(see precedence below) — the user explicitly marked it, so that's the right call, not a bug to fix.
**In-combat and obsolete are not mutually exclusive with each other either** — nothing stops a player from
re-engaging an enemy that was flagged obsolete (skippable), so both can be true at once. The frontend's
`LiveSessionEnemy.getStateOverlay()` (`resources/assets/js/custom/models/livesessionenemy.js`) resolves the
display precedence (`overpulled → killed → in-combat → obsolete`). **All four state tables are keyed by
`(live_session_id, npc_id, mdt_id)`**, NOT `enemy_id` — they resolve to live `Enemy.id` by joining
`npc_id`+`mdt_id`+`mapping_version_id`. Do not rely on an `enemy_id` column (the legacy one on the
overpulled table is dead/being dropped).

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
- `app/Service/LiveSession/LiveSessionCombatStateService.php` — `setKilledEnemy()` (`firstOrCreate`, returns `wasRecentlyCreated`), `replaceObsoleteEnemies()` (delete+reinsert), `getObsoleteEnemyIds()`, `replaceInCombatEnemies()` (delete+reinsert, full-set replace every call), `getInCombatEnemyIds()`, `setPlayerPosition()`. `resolveEnemyIds()` does the npc/mdt/mapping-version join back to `enemies.id`.
- `app/Service/LiveSession/LiveSessionOverpullDetectionService.php` — `processResolvedKills()` classifies each resolved kill as on-route/off-route, then `persistAndBroadcastInCombat()` fully replaces the in-combat set via `replaceInCombatEnemies()` and broadcasts `InCombatEnemiesChangedEvent` only when the set actually changed; `recomputeObsoleteIfNeeded()` re-derives obsolete whenever any overpulled or obsolete rows currently exist (not only on a new overpull — see the interface docblock).
- `app/Service/LiveSession/OverpulledEnemyService.php` — `getRouteCorrection(LiveSession): DungeonRouteCorrection`. Reads the overpulled table, groups by kill zone, walks kill zones with `index >` the overpull's to find skippable enemy forces → fills obsolete enemy ids + corrected enemy forces. ⚠️ Its private `getOverpulledEnemyForces()` raw SQL has historically been buggy (wrong `dungeon_routes` join; relied on the dead `enemy_id`) — verify before trusting it.
- `app/Service/LiveSession/DungeonRouteCorrection.php` — value object: `obsoleteEnemies` collection + `enemyForces`.
- `app/Models/LiveSession/LiveSession.php` — relations `killedEnemies()`, `overpulledEnemies()`, `obsoleteEnemies()`, `inCombatEnemies()`, `playerPositions()`; `mapContextKilledEnemyIds()`, `mapContextOverpulledEnemies()` (returns `{enemy_id, kill_zone_id}` pairs, not a flat id list), `mapContextInCombatEnemyIds()`, `mapContextPlayerPositions()`. `boot()` cascades deletes.
- `app/Models/LiveSession/LiveSession{Killed,Overpulled,Obsolete,InCombat}Enemy.php`, `LiveSessionPlayerPosition.php`.
- `app/Console/Commands/Scheduler/LiveSession/CleanupExpiredLiveSessions.php` — cleans the state tables (overpulled/killed/obsolete/in-combat enemies, player positions, combat log buffer) on expiry.

## Broadcast events → map reaction

| Event (`app/Events/...`) | `broadcastAs()` | Frontend handler |
|--------------------------|-----------------|------------------|
| `Models/LiveSession/EnemyKilledEvent` | `enemy-killed` | `echo/messagehandler/listen/models/livesession/enemykilled.js` |
| `Models/LiveSession/PlayerMovedEvent` | `player-moved` | `echo/.../livesession/playermoved.js` |
| `LiveSession/RouteCorrectionEvent` | `route-correction` | `echo/messagehandler/listen/livesession/routecorrection.js` |
| `LiveSession/OverpulledEnemy/OverpulledEnemyChangedEvent` | `overpulledenemy-changed` | `echo/.../models/overpulledenemy/changed.js` |
| `LiveSession/OverpulledEnemy/OverpulledEnemyDeletedEvent` | `overpulledenemy-deleted` | `echo/.../models/overpulledenemy/deleted.js` |
| `LiveSession/InCombatEnemiesChangedEvent` | `incombat-changed` | `echo/messagehandler/listen/livesession/incombatchanged.js` |

- The buffer pipeline has **no `Auth`** — broadcast events with `$liveSession->user` as the user (see `EnemyKilledEvent`/`PlayerMovedEvent`).
- Register a new handler in `echo/echohandler.js` and the message in `echo/message/messagefactory.js` (+ a `ModelMessage`/`Message` under `echo/message/listen/...`).
- `RouteCorrectionEvent.enemy_ids` carries **obsolete** ids only, not killed — but the two call sites that
  broadcast it compute the value differently:
  - `AjaxOverpulledEnemyController::broadcastRouteCorrection()` (manual overpull mark/unmark via the map
    editor UI) broadcasts `getRouteCorrection()->getObsoleteEnemies()` **merged** with
    `combatStateService->getObsoleteEnemyIds()` — the union of the just-computed correction and whatever is
    currently persisted — but does **not** persist that merged set back via `replaceObsoleteEnemies()`.
  - `LiveSessionOverpullDetectionService::recomputeAndBroadcastObsolete()` (buffer-processing path) computes
    `getRouteCorrection()->getObsoleteEnemies()` alone (no merge), immediately persists that exact set via
    `combatStateService->replaceObsoleteEnemies()`, and broadcasts it.
  - The persisted-table drift this could otherwise cause is largely masked at boot:
    `MapContextLiveSession::toArray()` (below) recomputes `getRouteCorrection()->getObsoleteEnemies()` and
    merges it with the persisted set again on every page load, so a late-joining client still sees the
    union, not a stale table. The remaining gap is narrower than "late joiners see stale data" — it's that
    the Ajax path never writes its own merge to the table, so anything reading `live_session_obsolete_enemies`
    directly (not through `MapContext`) between an Ajax mark and the next buffer pass would miss it. Whether
    that gap matters is worth confirming with whoever owns this service before relying on it either way.

## Initial map load: MapContext serialization

`app/Logic/MapContext/Map/MapContextLiveSession.php::toArray()` is what the page boots with:
```php
'overpulledEnemies'   => $liveSession->mapContextOverpulledEnemies(),
'obsoleteEnemies'     => $routeCorrection->getObsoleteEnemies()
                           ->merge($combatStateService->getObsoleteEnemyIds($liveSession))->unique()->values(),
'enemyForcesOverride' => $routeCorrection->getEnemyForces(),
'killedEnemies'       => $liveSession->mapContextKilledEnemyIds(),
'inCombatEnemies'     => $liveSession->mapContextInCombatEnemyIds(),
'playerPositions'     => $liveSession->mapContextPlayerPositions(...),
```
`mapContextOverpulledEnemies()` returns `{enemy_id, kill_zone_id}` pairs (not a flat id list) — the frontend
needs the kill zone attribution to draw the connection line back to the pull. Obsolete is **unioned
(persisted + recomputed) with `unique()`**, so persisting obsolete via `replaceObsoleteEnemies()` is safe and
is needed so late-joining clients get correct state (see the asymmetry note above though — the Ajax path
doesn't persist its own merge). The frontend reads these via `mapcontext/mapcontextlivesession.js`
(`getOverpulledEnemies()`, `getObsoleteEnemies()`, `getInCombatEnemies()`, `getKilledEnemies()` /
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
  `'incombat:changed'`, `'included:changed'`, `'excluded:changed'`). The visual registers the union;
  registering for a signal an enemy never fires is harmless.

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
