---
name: heatmap-javascript
description: Reference guide for the EXISTING public heatmap feature's JavaScript implementation. Use when modifying heatmap filters, visualization settings, the HeatPlugin, SearchHandlerHeatmap, or the embed pass-through API. For the PHP backend see heatmap-php; for building a NEW custom heatmap-like overlay that reuses this infra, see heatmap-functionality.
---

# Heatmap JavaScript

A focused reference for the Heatmap feature's client-side JavaScript. The heatmap overlays aggregated player/NPC position data (sourced from Raider.IO) on the Leaflet dungeon map. It is only active in `MapContextDungeonExplore` and controlled by the `app/Features/Heatmap.php` feature flag.

## File Structure

```
resources/assets/js/custom/
├── constants.js                                        — heatmap-related constants
├── mapplugins/
│   └── heatplugin.js                                   — Leaflet layer management + weight grid
└── inline/
    ├── base/
    │   └── searchinlinebase.js                         — base class for search sidebars
    ├── common/
    │   ├── maps/
    │   │   ├── heatmapsearchsidebar.js                 — InlineCode entry point, filter orchestrator
    │   │   └── handlers/
    │   │       ├── heatoptionminopacityhandler.js       — slider: min opacity (0–1, step 0.01)
    │   │       ├── heatoptionmaxzoomhandler.js          — slider: max zoom (1–30, step 0.5)
    │   │       ├── heatoptionmaxhandler.js              — slider: max value (0–20, step 0.1)
    │   │       ├── heatoptionradiushandler.js           — slider: radius (0–50, step 1)
    │   │       └── heatoptionblurhandler.js             — slider: blur (0–30, step 1)
    │   └── search/
    │       ├── searchhandlerheatmap.js                 — AJAX handler for /ajax/heatmap/data
    │       └── searchparams.js                         — filter → flat params conversion
```

## Constants (`constants.js`)

```javascript
// Event types (filter `type`)
COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH    = 'npc_death'
COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH = 'player_death'
COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL = 'player_spell'

// Data types (filter `dataType`)
COMBAT_LOG_EVENT_DATA_TYPE_PLAYER_POSITION = 'player_position'
COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION  = 'enemy_position'
```

## Class Responsibilities

### `CommonMapsHeatmapsearchsidebar` extends `SearchInlineBase`

The InlineCode entry point for the heatmap sidebar. Instantiated via `@include('common.general.inline')`.

**Key responsibilities:**
- Instantiates all search filters in `this.filters` (see filter list below)
- Toggles filter section visibility based on the selected event type
- Sets up 5 `HeatOption*Handler` range sliders in `_setupLeafletHeatOptions()`
- On `activate()`: wires the enabled/disabled checkbox, triggers initial `_search()`
- `_search()`: delegates to `super._search()` with `dungeonId` injected; passes the response to `pluginHeat.setRawLatLngsPerFloor()`
- `searchWithFilters(filters)`: public API used by embed mode to trigger a search with external filter values
- `_redrawHeatmap()`: reads current slider values and calls `pluginHeat.setOptions(options)`

**`passThroughEverything` option:** When `true`, all filters that interact with the DOM call `setPassThrough(true)`, making them store values internally without touching UI elements. Used for the embed pass-through API.

**Filters map:**

| Key | Class | DOM interaction |
|-----|-------|----------------|
| `type` | `SearchFilterRadioEventType` | radio group |
| `dataType` | `SearchFilterRadioDataType` | radio group |
| `includePlayerSpellIds` | `SearchFilterPlayerSpells` | multi-select |
| `region` | `SearchFilterRadioRegion` | radio group |
| `keyLevel` | `SearchFilterMythicLevel` | range slider |
| `itemLevel` | `SearchFilterItemLevel` | range slider |
| `playerDeaths` | `SearchFilterPlayerDeaths` | range slider |
| `includeAffixIds` | `SearchFilterAffixes` | multi-select |
| `weeklyAffixGroups` | `SearchFilterWeeklyAffixGroups` | multi-select |
| `includeClassIds` | `SearchFilterClasses` | multi-select |
| `includeSpecIds` | `SearchFilterSpecializations` | multi-select |
| `includePlayerDeathClassIds` | `SearchFilterClassesPlayerDeaths` | multi-select |
| `includePlayerDeathSpecIds` | `SearchFilterSpecializationsPlayerDeaths` | multi-select |
| `duration` | `SearchFilterDuration` | range slider |
| `excludeSpecIds` | `SearchFilterPassThrough` | none (server-side only) |
| `excludeClassIds` | `SearchFilterPassThrough` | none (server-side only) |
| `excludeAffixIds` | `SearchFilterPassThrough` | none (server-side only) |
| `excludePlayerDeathSpecIds` | `SearchFilterPassThrough` | none (server-side only) |
| `excludePlayerDeathClassIds` | `SearchFilterPassThrough` | none (server-side only) |
| `showSidebar` | `SearchFilterPassThrough` | none |
| `token` | `SearchFilterPassThrough` | none |
| `season` | `SearchFilterPassThrough` | none |
| `minSamplesRequired` | `SearchFilterMinSamplesRequired` | range slider (admin/internal only) |

**Event type → filter visibility logic:**
- `npc_death` → shows `dataType` filter
- `player_death` → shows `includePlayerDeathClassIds` + `includePlayerDeathSpecIds` filters
- `player_spell` → shows `includePlayerSpellIds` filter

---

### `HeatPlugin` extends `MapPlugin`

Owns the Leaflet `L.heatLayer` and the pre-computed weight grid used for tooltips.

**Key methods:**

```javascript
// Main data ingestion — called after every successful AJAX response
setRawLatLngsPerFloor(rawLatLngsPerFloor, dataType, runCount, weightMax, gridSizeX, gridSizeY)
```
- Builds `rawLatLngsByFloorId[floorId]` as `[[lat, lng, weight], ...]` arrays for Leaflet
- Builds `weightByFloorIdGrid[floorId][x][y]` — a full 300×200 grid of interpolated weights
- Pre-computation uses inverse-distance weighted interpolation via `_getWeightAt()`
- `weightCacheRadius`: `player_position` uses radius 5, `enemy_position` uses radius 2

```javascript
toggle(enabled)          // Show/hide; calls _applyLatLngsForFloor with correct floorId
setOptions(options)      // Forwards to heatLayer.setOptions() — updates radius, blur, etc.
isEnabled()              // Returns true only in MapContextDungeonExplore
```

**State events listened to:**
- `floorid:changed` → calls `_applyLatLngsForFloor(newFloorId)`
- `heatmapshowtooltips:changed` → toggles mouse tooltip visibility

**Mouse tooltip** (desktop only):
- Uses `_getGridPositionForLatLng()` to convert Leaflet lat/lng to grid x/y
- Looks up weight via `_getWeightAt()` on the pre-computed grid
- Displays `{percent}% - {absoluteWeight}` using a permanent Leaflet tooltip
- Percentage = `weight / this.weightMax * 100` (uses global `weightMax`, not per-floor)

**Grid coordinate formula:**
```javascript
x = Math.floor((lat / MAP_MAX_LAT) * gridSizeX)   // gridSizeX = 300
y = Math.floor((lng / MAP_MAX_LNG) * gridSizeY)   // gridSizeY = 200
```

---

### `SearchHandlerHeatmap` extends `SearchHandler`

```javascript
getSearchUrl()    // returns '/ajax/heatmap/data'
getAjaxOptions()  // returns { type: 'POST', dataType: 'json' }
```

**`loaderFn` snackbar behaviour:**

| State | Handlebars template | Content |
|-------|--------------------|---------| 
| Loading | `map_heatmapsearch_loader` | spinner |
| Error (generic) | `map_heatmapsearch_error_loading_data` | `js.error_loading_data_label` |
| Error (too much data) | `map_heatmapsearch_error_loading_data` | `js.too_much_data_label` |
| Success | `map_heatmapsearch_run_count` | `js.run_count_label` with `{count}` |

The "too much data" error is triggered when the response contains `message: 'Invalid response from Raider.IO API'`.

---

### `SearchInlineBase` extends `InlineCode`

Base class shared with other search sidebars.

- `activate()`: activates all filters; restores filter values from URL query params
- `_search(options, queryParameters, blacklist)`: builds `SearchParams`, deduplicates via `JSON.stringify` comparison against `_previousSearchParams`, updates `history.pushState` URL
- `_updateFilters()`: updates the "active filters" display (selector: `currentFiltersSelector`)

---

### `SearchParams`

Converts `this.filters` into a flat `params` object for the AJAX request.

- Filters with `options.array = true` → key formatted as `name[]`
- Filters with `options.csv = true` → array joined as comma-separated string
- Filters returning `null` / `''` / empty array → excluded from params
- `equals(other)`: `JSON.stringify` comparison — used to prevent duplicate searches

---

### Heat Option Handlers

All extend the same pattern — wrap `ionRangeSlider`:

```javascript
handler.apply(selector, { onFinish: this._redrawHeatmap.bind(this) });
```

| Handler | Range | Step |
|---------|-------|------|
| `HeatOptionMinOpacityHandler` | 0–1 | 0.01 |
| `HeatOptionMaxZoomHandler` | 1–30 | 0.5 |
| `HeatOptionMaxHandler` | 0–20 | 0.1 |
| `HeatOptionRadiusHandler` | 0–50 | 1 |
| `HeatOptionBlurHandler` | 0–30 | 1 |

The gradient and pane selectors use plain jQuery `.on('change', ...)` instead of a handler class.

`_redrawHeatmap()` reads all current values and calls:
```javascript
getState().getDungeonMap().pluginHeat.setOptions({
    minOpacity, maxZoom, max, radius, blur,
    gradient: JSON.parse(gradientSelectorValue),
    pane: paneSelectorValue
});
```

## Data Flow

```
Filter change (user interaction or searchWithFilters() call)
  └→ CommonMapsHeatmapsearchsidebar._search()
       └→ SearchParams built from this.filters + { dungeonId }
            └→ JSON dedup check (_previousSearchParams.equals())
                 └→ SearchHandlerHeatmap.search(searchParams)
                      ├→ loaderFn(true)  — shows loading snackbar
                      └→ POST /ajax/heatmap/data
                           ├→ loaderFn(false, response) — shows run_count or error snackbar
                           └→ success: pluginHeat.setRawLatLngsPerFloor(
                                  json.data, json.data_type, json.run_count,
                                  json.weight_max, json.grid_size_x, json.grid_size_y
                              )
                                   └→ _applyLatLngsForFloor(currentFloorId)
                                        └→ heatLayer.setLatLngs([[lat, lng, weight], ...])
```

## API Contract

### Request — POST `/ajax/heatmap/data`

```json
{
  "dungeonId": 1,
  "type": "npc_death | player_death | player_spell",
  "dataType": "player_position | enemy_position",
  "region": "US | EU | KR | TW | CN | world",
  "minMythicLevel": 2,
  "maxMythicLevel": 30,
  "minItemLevel": 0,
  "maxItemLevel": 999,
  "minPlayerDeaths": 0,
  "maxPlayerDeaths": 99,
  "includeAffixIds[]": [1, 2],
  "includeClassIds[]": [1],
  "includeSpecIds[]": [1],
  "includePlayerDeathClassIds[]": [1],
  "includePlayerDeathSpecIds[]": [1],
  "includePlayerSpellIds[]": [101],
  "excludeAffixIds": "3,4",
  "excludeClassIds": "2",
  "excludeSpecIds": "5,6",
  "season": "season-tw-1",
  "token": "optional-access-token"
}
```

Array fields use Laravel's `name[]` convention. Exclude fields are CSV strings.

### Response

```json
{
  "data": [
    {
      "floor_id": 1,
      "lat_lngs": [
        { "lat": -50.5, "lng": 100.2, "weight": 42 }
      ]
    }
  ],
  "data_type": "player_position",
  "run_count": 1234,
  "weight_max": 500,
  "grid_size_x": 300,
  "grid_size_y": 200,
  "url": "(optional, logged to console for debugging)"
}
```

Lat/lng coordinates use the Leaflet map's coordinate system (bounded by `MAP_MAX_LAT` / `MAP_MAX_LNG`).

## Patterns & Gotchas

- **`initializing` flag**: set `true` in the constructor, cleared in `activate()`. `_search()` returns early while `true`, preventing premature AJAX calls during filter setup.
- **`passThroughEverything`**: when `true`, all DOM-backed filters call `setPassThrough(true)`. Values are set via `searchWithFilters()` rather than UI interaction. This is how the embed PostMessage API controls filters.
- **Weight tooltip uses global max**: `this.weightMax` is the max across all floors combined, not per-floor. This is intentional — it keeps percentage scales consistent when switching floors.
- **`SearchFilterPassThrough` filters**: `excludeClassIds`, `excludeAffixIds`, `token`, `season`, etc. have no UI. They are set by the embed API or URL params and forwarded to the server transparently.
- **Admin-only filter**: `minSamplesRequired` is only added to `this.filters` when `state.userHasRole(USER_ROLE_ADMIN)` or `USER_ROLE_INTERNAL_TEAM` is true.
- **Cookie persistence**: filter collapse state uses `filterCookiePrefix + collapseName` cookies. Heatmap enabled/disabled state uses `enabledStateCookie`.
- **Floor switching**: `HeatPlugin` listens to `floorid:changed` on the state manager — no manual calls needed when the user switches floors.
- **`isEnabled()` guard**: `HeatPlugin` checks `MapContextDungeonExplore` before adding/removing the Leaflet layer. Heatmap is silently a no-op on admin maps.
