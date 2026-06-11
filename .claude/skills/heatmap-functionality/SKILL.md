---
name: heatmap-functionality
description: End-to-end guide on how the dungeon heatmap works — data flow, key files, HeatPlugin API, and how to integrate a custom heatmap-like feature using the same infrastructure.
---

# Heatmap Functionality

## End-to-End Data Flow

```
DungeonHeatmapController
  → blade view (dungeon.heatmap.gameversion.view)
    → common/maps/map.blade.php  ($show['controls']['heatmapSearch'] = true)
      → common/maps/controls/heatmapsearch.blade.php
        → @include('common.general.inline', path: 'common/maps/heatmapsearchsidebar', options: [...])
          → CommonMapsHeatmapsearchsidebar (extends SearchInlineBase)
            → SearchHandlerHeatmap
              → POST /ajax/heatmap/data
                → AjaxHeatmapController::getData()
                  → RaiderIOApiService::getHeatmapData()
                    → Raider.IO API
              ← JSON response
            ← pluginHeat.setRawLatLngsPerFloor(...)
          ← HeatPlugin renders heat layer on Leaflet map
```

## Feature Flag

The public heatmap is gated by the Pennant flag `App\Features\Heatmap`. The admin
failure heatmap does **not** need this flag — it's admin-only behind middleware.

## Key Files

| File | Purpose |
|---|---|
| `app/Features/Heatmap.php` | Pennant feature flag for the public heatmap |
| `app/Http/Controllers/Dungeon/DungeonHeatmapController.php` | Page + redirect controller; also shows how to wire `mapContextService->createMapContextDungeonExplore()` |
| `app/Http/Controllers/Ajax/AjaxHeatmapController.php` | Ajax data endpoint |
| `app/Service/MapContext/MapContextServiceInterface.php` | `createMapContextDungeonExplore()` factory |
| `app/Logic/MapContext/Map/MapContextDungeonExplore.php` | Map context class required for HeatPlugin to function |
| `resources/views/dungeon/heatmap/gameversion/view.blade.php` | Full-page heatmap view |
| `resources/views/common/maps/controls/heatmapsearch.blade.php` | Sidebar filter UI |
| `resources/assets/js/custom/inline/common/maps/heatmapsearchsidebar.js` | `CommonMapsHeatmapsearchsidebar` — the JS class wired to the sidebar |
| `resources/assets/js/custom/inline/common/search/searchhandlerheatmap.js` | `SearchHandlerHeatmap` — thin wrapper around `SearchHandler` |
| `resources/assets/js/custom/mapplugins/heatplugin.js` | `HeatPlugin` — Leaflet.heat layer manager |

## HeatPlugin — Critical Details

### `isEnabled()`
```js
isEnabled() {
    return getState().getMapContext() instanceof MapContextDungeonExplore;
}
```
**The heat layer only works when the page uses `MapContextDungeonExplore` as its map context.**
`addToMap()` and `toggle()` are no-ops if the context is anything else.
When building a page that uses the heat layer, always pass a `MapContextDungeonExplore`
instance (created via `MapContextServiceInterface::createMapContextDungeonExplore()`).

### `addToMap()`
Called automatically when the map initialises its plugins. Adds the Leaflet.heat layer
to the Leaflet map. No need to call this manually.

### `toggle(enabled: boolean)`
Controls visibility. **You MUST call `toggle(true)` in your `activate()` method** or
no dots will appear, even after `setRawLatLngsPerFloor()` has been called.

```js
// In your JS class's activate():
getState().getDungeonMap().pluginHeat.toggle(true);
```

`toggle(false)` hides the layer by rendering for floor ID `-1` (no data).

### `setRawLatLngsPerFloor(rawLatLngsPerFloor, dataType, runCount, weightMax, gridSizeX, gridSizeY)`
Stores data for all floors. Automatically renders data for the currently active floor.
- `dataType` controls the interpolation radius (`weightCacheRadius[dataType]`): `5` for player position, `2` for enemy position. Pass `null` to disable interpolation (exact cell weights only — acceptable for admin-only views).
- `runCount` is displayed in tooltips. Pass `null` if not applicable.
- `weightMax` is used to normalise weights to a percentage in tooltips. Falls back to `Math.max(weightMaxByFloorId)` if null — safe to pass null, but passing the correct value is preferred.
- **Do NOT pass null for `gridSizeX` / `gridSizeY`.** Passing null breaks the precomputation loop (`for (let x = 0; x < null; x++)` never executes, leaving the interpolation cache empty). Always pass the actual grid dimensions — use the config values `keystoneguru.heatmap.service.data.player.size_x` (300) and `size_y` (200).
- Data is stored internally as `rawLatLngsByFloorId[floor_id]`.
- Floor switches call `_applyLatLngsForFloor()` automatically.

## Weight System — How the Heatmap Gets Its Gradient

**Weight = count of data points that fall in the same grid cell**, not a per-point constant.

### Grid cell formula
The HeatPlugin maps each `{ lat, lng }` to a 2D grid cell:
```js
gridX = Math.floor((lat / MAP_MAX_LAT) * gridSizeX)   // MAP_MAX_LAT = -256
gridY = Math.floor((lng / MAP_MAX_LNG) * gridSizeY)   // MAP_MAX_LNG = 384
```
PHP equivalent (uses `CoordinatesService::MAP_MAX_LAT` / `MAP_MAX_LNG`):
```php
$gridX = (int)floor(($lat / CoordinatesService::MAP_MAX_LAT) * $gridSizeX);
$gridY = (int)floor(($lng / CoordinatesService::MAP_MAX_LNG) * $gridSizeY);
```

### Backend responsibility
The **backend** must aggregate raw records into grid cells before responding. Never return one entry per DB row with `weight = 1.0` — that makes every point equally "hot" and defeats the heatmap.

Correct pattern:
1. For each record compute `(gridX, gridY)`.
2. Group by `floor_id`, then by `"$gridX,$gridY"` string key.
3. `weight = count of records in that cell`.
4. Return the grid-cell centre as the representative lat/lng:
   ```php
   $lat = round((($gridX + 0.5) / $gridSizeX) * CoordinatesService::MAP_MAX_LAT, 2);
   $lng = round((($gridY + 0.5) / $gridSizeY) * CoordinatesService::MAP_MAX_LNG, 2);
   ```

See `CombatLogRouteEnemyFailureService::getEnemyFailureHeatmapData()` for a reference implementation.

### Full response shape
```json
{
  "data": [
    {
      "floor_id": 123,
      "lat_lngs": [
        { "lat": -128.0, "lng": 192.0, "weight": 5 }
      ]
    }
  ],
  "data_type": "combat_log_route_enemy_failure",
  "weight_max": 15,
  "failure_count": 100,
  "grid_size_x": 300,
  "grid_size_y": 200
}
```

Note: The property is `lat_lngs` (snake_case), **not** `latLngs`. The HeatPlugin reads
`rawLatLngsOnFloor.lat_lngs[index].lat/lng/weight` directly (see `heatplugin.js:242`).

The existing `CombatLogEventGridAggregationResult::toArray()` follows the same pattern using
OpenSearch `doc_count` per grid bucket as the weight.

## SearchHandlerHeatmap

Thin wrapper around `SearchHandler` that:
- POSTs to `/ajax/heatmap/data`
- Shows loading/error snackbars via the `loaderFn`

For a custom heatmap-like feature, write a new `SearchHandler` subclass with a
different `getSearchUrl()` and a simpler `loaderFn` that just calls `setRawLatLngsPerFloor`.

## CommonMapsHeatmapsearchsidebar (`activate()` pattern)

```js
activate() {
    super.activate();
    this.map = getState().getDungeonMap();

    // 1. Wire up the enable/disable checkbox
    let $enabledState = $(this.options.enabledStateSelector);
    $enabledState.on('change', () => this._toggleHeatmap($enabledState.is(':checked')));
    this._toggleHeatmap($enabledState.is(':checked'));  // apply initial state

    this.sidebar.activate();
    this._search();  // load data immediately
}

_toggleHeatmap(enabled) {
    this.map.pluginHeat.toggle(enabled);
}
```

For a simpler admin view without a checkbox, skip the checkbox wiring and just call
`pluginHeat.toggle(true)` unconditionally in `activate()`.

## Building a MapContextDungeonExplore

Never use `app()->make()`. Always inject `MapContextServiceInterface` and call:

```php
$mapContext = $mapContextService->createMapContextDungeonExplore(
    $dungeon,
    $mappingVersion,
    User::getCurrentUserMapFacadeStyle(),
);
```

Reference: `DungeonHeatmapController::viewDungeonFloor()` (line 165).
