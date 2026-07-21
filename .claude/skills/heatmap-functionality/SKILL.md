---
name: heatmap-functionality
description: How to build a NEW custom heatmap-like feature by reusing the existing heatmap infrastructure — the HeatPlugin Leaflet layer, MapContextDungeonExplore, and grid-cell weight aggregation (e.g. the admin enemy-failure heatmap). Use when integrating a new heatmap-style overlay. For a reference of the EXISTING public heatmap feature itself, use heatmap-javascript (client) or heatmap-php (backend) instead.
---

# Heatmap Functionality — Reusing the Infrastructure

This skill is a **how-to for building a new heatmap-like overlay** on top of the shared
infrastructure (the admin enemy-failure heatmap is the reference example). It covers only
what you need to integrate; it is **not** an exhaustive reference of the existing public
heatmap.

- For the full client-side reference (filters, `HeatPlugin` methods, sidebar, `SearchHandler`,
  API contract), see the **`heatmap-javascript`** skill.
- For the full backend reference (controllers, form requests, DTOs, `RaiderIO` service layer,
  routes), see the **`heatmap-php`** skill.

## What "reuse" gives you

The heatmap infrastructure is a Leaflet `L.heatLayer` (`HeatPlugin`) that renders weighted
dots per floor, plus a pre-computed weight grid for tooltips. To drive it with your own data
you provide two things: a **page that uses `MapContextDungeonExplore`** and a **backend
endpoint that returns grid-aggregated weights**. Everything else (rendering, floor switching,
tooltips) is handled for you.

## The three integration requirements

### 1. The page must use `MapContextDungeonExplore`

`HeatPlugin.isEnabled()` returns `true` **only** when the map context is a
`MapContextDungeonExplore`. `addToMap()` and `toggle()` are silent no-ops otherwise, so no
dots ever appear on any other context.

Never use `app()->make()`. Inject `MapContextServiceInterface` and call:

```php
$mapContext = $mapContextService->createMapContextDungeonExplore(
    $dungeon,
    $mappingVersion,
    User::getCurrentUserMapFacadeStyle(),
);
```

Reference: `DungeonHeatmapController::viewDungeonFloor()`.

### 2. Feed the layer with `setRawLatLngsPerFloor()` and call `toggle(true)`

```js
// In your JS class's activate():
const pluginHeat = getState().getDungeonMap().pluginHeat;
pluginHeat.setRawLatLngsPerFloor(
    json.data, json.data_type, json.run_count,
    json.weight_max, json.grid_size_x, json.grid_size_y
);
pluginHeat.toggle(true); // REQUIRED — without this no dots render, even with data loaded
```

Integration traps (see `heatmap-javascript` for the full method reference):
- **You MUST call `toggle(true)`** in `activate()`, or the layer stays hidden.
- **Never pass `null` for `gridSizeX` / `gridSizeY`.** The interpolation precompute loop
  (`for (let x = 0; x < gridSizeX; x++)`) silently does nothing with `null`, leaving an empty
  tooltip cache. Pass the real dimensions — `config('keystoneguru.heatmap.service.data.player.size_x')`
  (300) and `size_y` (200).
- `dataType` selects the interpolation radius (`player_position` → 5, `enemy_position` → 2).
  Pass `null` to disable interpolation (exact cell weights only — fine for admin-only views).

### 3. The backend must aggregate into grid cells (weight = count)

**Weight is the count of records that fall in the same grid cell**, not a per-row constant.
Returning one entry per DB row with `weight = 1.0` makes every point equally hot and defeats
the heatmap.

Grid-cell formula (PHP; JS equivalent uses `MAP_MAX_LAT = -256`, `MAP_MAX_LNG = 384`):

```php
$gridX = (int)floor(($lat / CoordinatesService::MAP_MAX_LAT) * $gridSizeX);
$gridY = (int)floor(($lng / CoordinatesService::MAP_MAX_LNG) * $gridSizeY);
```

Aggregation pattern:
1. For each record compute `(gridX, gridY)`.
2. Group by `floor_id`, then by `"$gridX,$gridY"` key.
3. `weight = count of records in that cell`.
4. Return the cell **centre** as the representative lat/lng:
   ```php
   $lat = round((($gridX + 0.5) / $gridSizeX) * CoordinatesService::MAP_MAX_LAT, 2);
   $lng = round((($gridY + 0.5) / $gridSizeY) * CoordinatesService::MAP_MAX_LNG, 2);
   ```

Response shape (note `lat_lngs` is **snake_case** — `HeatPlugin` reads
`rawLatLngsOnFloor.lat_lngs[i].lat/lng/weight` directly):

```json
{
  "data": [
    { "floor_id": 123, "lat_lngs": [ { "lat": -128.0, "lng": 192.0, "weight": 5 } ] }
  ],
  "data_type": "combat_log_route_enemy_failure",
  "weight_max": 15,
  "grid_size_x": 300,
  "grid_size_y": 200
}
```

Reference implementations:
- `CombatLogRouteEnemyFailureService::getEnemyFailureHeatmapData()` — the admin failure heatmap.
- `CombatLogEventGridAggregationResult::toArray()` — same pattern, using OpenSearch `doc_count`
  per grid bucket as the weight.

## Wiring the sidebar / data loader

The public heatmap uses `SearchHandlerHeatmap` (POSTs to `/ajax/heatmap/data`). For a custom
feature, write a new `SearchHandler` subclass with a different `getSearchUrl()` and a simpler
`loaderFn` that just calls `setRawLatLngsPerFloor()`. A minimal `activate()` for an admin view
without an enable/disable checkbox just calls `pluginHeat.toggle(true)` unconditionally. See
`CommonMapsHeatmapsearchsidebar` (in `heatmap-javascript`) for the full-featured pattern.
