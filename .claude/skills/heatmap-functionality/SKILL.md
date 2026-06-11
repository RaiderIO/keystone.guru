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
- For a simple admin view, pass `null` for `dataType`, `runCount`, `weightMax`, `gridSizeX`, `gridSizeY`.
- Data is stored internally as `rawLatLngsByFloorId[floor_id]`.
- Floor switches call `_applyLatLngsForFloor()` automatically.

## API Response Format

The JSON response consumed by `setRawLatLngsPerFloor` must use **snake_case `lat_lngs`**:

```json
{
  "data": [
    {
      "floor_id": 123,
      "lat_lngs": [
        { "lat": 0.512, "lng": -0.234, "weight": 1.0 }
      ]
    }
  ]
}
```

Note: The property is `lat_lngs` (snake_case), **not** `latLngs`. The HeatPlugin reads
`rawLatLngsOnFloor.lat_lngs[index].lat/lng/weight` directly (see `heatplugin.js:242`).

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
