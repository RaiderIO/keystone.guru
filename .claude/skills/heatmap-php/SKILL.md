---
name: heatmap-php
description: Reference guide for the EXISTING public heatmap feature's PHP/Laravel backend. Use when modifying heatmap controllers, form requests, DTOs, the RaiderIO service layer, the Heatmap feature flag, or the CombatLogEvent grid aggregation. For the client-side JS see heatmap-javascript; for building a NEW custom heatmap-like overlay that reuses this infra, see heatmap-functionality.
---

# Heatmap PHP

A focused reference for the Heatmap feature's server-side PHP. The heatmap overlays aggregated player/NPC position data (sourced from Raider.IO) on the Leaflet dungeon map. Access is gated by the `app/Features/Heatmap.php` feature flag and the per-dungeon `heatmap_enabled` boolean.

## File Map

```
app/
├── Features/
│   └── Heatmap.php                                          — feature flag (global on/off)
├── Http/
│   ├── Controllers/
│   │   ├── Ajax/
│   │   │   └── AjaxHeatmapController.php                   — POST /ajax/heatmap/data
│   │   └── Dungeon/
│   │       └── DungeonHeatmapController.php                 — GET /heatmap/* view routes
│   └── Requests/
│       └── Heatmap/
│           ├── HeatmapUrlFormRequest.php                    — URL filter params (CSV IDs)
│           ├── HeatmapEmbedUrlFormRequest.php               — extends URL + embed options
│           ├── AjaxGetDataFormRequest.php                   — AJAX filter params (array IDs)
│           ├── ExploreUrlFormRequest.php                    — deprecated alias → HeatmapUrlFormRequest
│           └── ExploreEmbedUrlFormRequest.php               — deprecated alias → HeatmapEmbedUrlFormRequest
├── Models/
│   └── Dungeon.php                                          — heatmap_enabled flag
├── Providers/
│   └── KeystoneGuruServiceProvider.php                      — service binding (local vs prod)
└── Service/
    ├── CombatLogEvent/
    │   └── CombatLogEventService.php                        — getGridAggregation() (OpenSearch)
    └── RaiderIO/
        ├── RaiderIOApiServiceInterface.php                  — getHeatmapData() contract
        ├── RaiderIOApiService.php                           — production: calls Raider.IO API
        ├── RaiderIOKeystoneGuruApiService.php               — local/test: queries OpenSearch
        ├── Exceptions/
        │   └── InvalidApiResponseException.php
        └── Dtos/
            ├── HeatmapDataFilter.php                        — filter DTO (in)
            ├── RaiderIOHeatmapGridResponse.php              — wraps raw API response
            └── HeatmapDataResponse/
                ├── HeatmapDataResponse.php                  — response DTO (out)
                ├── HeatmapDataFloorData.php                 — per-floor grid data
                └── HeatmapDataLatLng.php                    — single lat/lng/weight point

config/keystoneguru.php                                      — keystoneguru.heatmap.*
tests/Feature/Controller/Ajax/AjaxHeatmapControllerTest.php
```

## Architecture Overview

```
POST /ajax/heatmap/data
  → AjaxHeatmapController::getData()
      → AjaxGetDataFormRequest          (validates; array fields for include IDs)
      → HeatmapDataFilter::fromArray()  (builds DTO from request data)
      → RaiderIOApiServiceInterface::getHeatmapData()
          [local/test] → RaiderIOKeystoneGuruApiService
                           → CombatLogEventFilter::fromHeatmapDataFilter()
                           → CombatLogEventService::getGridAggregation() (OpenSearch)
          [production]  → RaiderIOApiService
                           → auto-populates season if missing
                           → GET https://raider.io/api/v1/live-tracking/heatmaps/grid
                           → validates response has gridsByFloor + numRuns
                           → RaiderIOHeatmapGridResponse::toArray()
      → HeatmapDataResponse::fromArray()
  ← JsonResponse { data, data_type, run_count, weight_max, grid_size_x, grid_size_y, url? }

GET /heatmap/{gameVersion}/{dungeon}/{floorIndex}
  → DungeonHeatmapController::viewDungeonFloor()
      → guardAgainstInvalidAccess()     (dungeon active + heatmap_enabled + mapping version + season + feature flag)
      → MapContextService::setDungeonContext()
      → returns view with filter defaults from getFilterSettings(?Season)
```

## Feature Flag (`app/Features/Heatmap.php`)

```php
public function resolve(?User $user): bool
{
    if (!Feature::getAdminValue(self::class)) {
        return false;                              // admin toggle is the master switch
    }
    $env = config('app.env');
    return (($env === 'local' && !empty(config('opensearch-laravel.host'))) || $env !== 'local');
}
```

- Local environments require OpenSearch (`opensearch-laravel.host`) to be configured.
- Controller usage: `Feature::active(Heatmap::class)` — combined with `$dungeon->heatmap_enabled` for full access control.

## Controllers

### `DungeonHeatmapController`

```php
// Route navigation / redirects
get(Request, GameVersionServiceInterface): RedirectResponse
getByGameVersion(Request, GameVersion, GameVersionServiceInterface): RedirectResponse
select(Request, GameVersion): View

// Access-validated view methods
viewDungeon(SeasonServiceInterface, Request, GameVersion, Dungeon): RedirectResponse
viewDungeonFloor(HeatmapUrlFormRequest, MapContextServiceInterface, SeasonServiceInterface,
    SeasonAffixGroupServiceInterface, DungeonServiceInterface, GameVersion, Dungeon,
    string $floorIndex = '1'): View|RedirectResponse
embed(HeatmapEmbedUrlFormRequest, MapContextServiceInterface, SeasonServiceInterface,
    SeasonAffixGroupServiceInterface, GameVersion, Dungeon,
    string $floorIndex = '1'): View|RedirectResponse

// Private helpers
guardAgainstInvalidAccess(GameVersion, Dungeon, ?MappingVersion, ?Season): ?RedirectResponse
getFilterSettings(?Season): array   // returns default min/max bounds for all filters
```

**`guardAgainstInvalidAccess()` checks (all must pass):**
1. `$dungeon->active`
2. `$dungeon->heatmap_enabled`
3. `$currentMappingVersion !== null`
4. `$mostRecentSeason !== null`
5. `Feature::active(Heatmap::class)`

**Page view tracking:**
- `viewDungeonFloor()` → `Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON`
- `embed()` → `Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON_HEATMAP_EMBED`

### `AjaxHeatmapController`

```php
public function getData(
    AjaxGetDataFormRequest $request,
    RaiderIOApiServiceInterface $raiderIOApiService,
): JsonResponse
```

Catches `InvalidApiResponseException` and returns HTTP 500 with `$e->toArray()` as body.

## Form Requests

### `HeatmapUrlFormRequest` (extends `DungeonRouteBaseUrlFormRequest`)

Used for URL-based filter state (browser address bar). All multi-ID fields are **CSV strings**.

```php
'type'                       => CombatLogEventEventType enum (nullable)
'dataType'                   => CombatLogEventDataType enum (nullable)
'region'                     => exists:game_server_regions,short (nullable)
'minMythicLevel'             => integer
'maxMythicLevel'             => integer
'minItemLevel'               => integer
'maxItemLevel'               => integer
'minPlayerDeaths'            => integer
'maxPlayerDeaths'            => integer
'includeAffixIds'            => string (CSV)
'excludeAffixIds'            => string (CSV)
'includeClassIds'            => string (CSV)
'excludeClassIds'            => string (CSV)
'includeSpecIds'             => string (CSV)
'excludeSpecIds'             => string (CSV)
'includePlayerDeathClassIds' => string (CSV)
'excludePlayerDeathClassIds' => string (CSV)
'includePlayerDeathSpecIds'  => string (CSV)
'excludePlayerDeathSpecIds'  => string (CSV)
'includePlayerSpellIds'      => string (CSV)
'minPeriod'                  => integer
'maxPeriod'                  => integer
'minTimerFraction'           => numeric
'maxTimerFraction'           => numeric
'minSamplesRequired'         => integer
'token'                      => string
'season'                     => regex /^season-[a-z]+-\d$/i
```

### `HeatmapEmbedUrlFormRequest` (extends `HeatmapUrlFormRequest` via deprecated alias)

Additional rules for embedded iframes:

```php
'style'                   => in:compact
'headerBackgroundColor'   => regex:#[a-f0-9]{6}|[a-f0-9]{3}
'mapFacadeStyle'          => in:User::MAP_FACADE_STYLE_ALL
'mapBackgroundColor'      => regex:#[a-f0-9]{6}|[a-f0-9]{3}
'showEnemyInfo'           => boolean
'showTitle'               => boolean
'showSidebar'             => boolean
'showHeader'              => boolean
'showDataSourceSnackbar'  => boolean
'defaultZoom'             => numeric
```

### `AjaxGetDataFormRequest` (extends `HeatmapUrlFormRequest` via deprecated alias)

Used for the AJAX endpoint. Multi-ID fields are **PHP arrays** (JS sends `name[]`), not CSV.

```php
'dungeonId'                    => required, exists:dungeons,id
'includeAffixIds'              => array of affix IDs
'includeClassIds'              => array of character class IDs
'includeSpecIds'               => array of specialization IDs
'includePlayerDeathClassIds'   => array of character class IDs
'includePlayerDeathSpecIds'    => array of specialization IDs
'includePlayerSpellIds'        => array of spell IDs
```

## `HeatmapDataFilter` DTO

**Constructor** (required):
```php
public function __construct(
    private readonly Dungeon                 $dungeon,
    private readonly CombatLogEventEventType $eventType,
    private readonly CombatLogEventDataType  $dataType,
)
```

**Property groups** (all have getters/setters with fluent interface):

| Group | Properties |
|-------|------------|
| Include collections | `includeAffixIds`, `includeClassIds`, `includeSpecIds`, `includePlayerDeathClassIds`, `includePlayerDeathSpecIds`, `includePlayerSpellIds` |
| Exclude pass-throughs | `excludeAffixIds`, `excludeClassIds`, `excludeSpecIds`, `excludePlayerDeathClassIds`, `excludePlayerDeathSpecIds` |
| Key/item level | `keyLevelMin`, `keyLevelMax`, `itemLevelMin`, `itemLevelMax` |
| Deaths / timer | `playerDeathsMin`, `playerDeathsMax`, `timerFractionMin`, `timerFractionMax` |
| Period | `minPeriod`, `maxPeriod` |
| Strings | `region`, `token`, `season`, `minSamplesRequired` |

**Key methods:**

```php
public function toArray(): array
// Converts to URL query params (snake_case keys).
// RaiderIOApiService applies Str::camel() before building the query string.
// Empty/null values are filtered out.

public static function fromArray(array $requestArray): HeatmapDataFilter
// Static factory. Parses a raw request array (from AjaxGetDataFormRequest).

public function getFloorsAsArray(): ?bool
// Reads config('keystoneguru.heatmap.api.floors_as_array').
```

## Response DTOs

### `HeatmapDataResponse`

```php
private Collection $data;                    // Collection<HeatmapDataFloorData>
private CombatLogEventDataType $dataType;
private int $runCount;
private int $weightMax;
private int $gridSizeX;
private int $gridSizeY;
private ?string $url;                        // null for non-admin users
```

JSON shape: `{ data, data_type, run_count, weight_max, grid_size_x, grid_size_y, url? }`

### `HeatmapDataFloorData`

```php
private int $floorId;
private Collection $latLngs;   // Collection<HeatmapDataLatLng>
```

JSON shape: `{ floor_id, lat_lngs }`

### `HeatmapDataLatLng`

```php
private float $lat;
private float $lng;
private int $weight;
```

JSON shape: `{ lat, lng, weight }`

### `RaiderIOHeatmapGridResponse` (extends `CombatLogEventGridAggregationResult`)

Wraps the raw Raider.IO JSON (`gridsByFloor`, `numRuns`, `maxSamplesInGrid`).

- Overrides `weightMax` with `maxSamplesInGrid` from the API (not the parent's calculation).
- Exposes `url` only to users with `ROLE_ADMIN` or `ROLE_INTERNAL_TEAM`; everyone else gets `null`.

## Service Layer

### Interface

```php
// app/Service/RaiderIO/RaiderIOApiServiceInterface.php
/** @throws InvalidApiResponseException */
public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse;
```

### Production: `RaiderIOApiService`

```php
const BASE_URL = 'https://raider.io/api/v1';
// Endpoint: {BASE_URL}/live-tracking/heatmaps/grid
```

1. If `$filter->getSeason()` is null, resolves the most recent season for the dungeon via `SeasonService::getMostRecentSeasonForDungeon()` and sets it.
2. Converts `$filter->toArray()` keys to camelCase with `Str::camel()`, builds query string.
3. Issues a cURL GET request.
4. Validates response has both `gridsByFloor` and `numRuns`; throws `InvalidApiResponseException` otherwise.
5. Wraps result in `RaiderIOHeatmapGridResponse`, then converts to `HeatmapDataResponse`.

### Local/test: `RaiderIOKeystoneGuruApiService`

```php
public function getHeatmapData(HeatmapDataFilter $heatmapDataFilter): HeatmapDataResponse
{
    return HeatmapDataResponse::fromArray(
        $this->combatLogEventService->getGridAggregation(
            CombatLogEventFilter::fromHeatmapDataFilter(
                $this->seasonService,
                $this->seasonAffixGroupService,
                $heatmapDataFilter,
            ),
        )->toArray(),
    );
}
```

Queries OpenSearch directly — no external HTTP call.

### Service Binding (`KeystoneGuruServiceProvider`)

```php
if (app()->runningUnitTests() || app()->environment('local')) {
    $this->app->bind(RaiderIOApiServiceInterface::class, RaiderIOKeystoneGuruApiService::class);
} else {
    $this->app->bind(RaiderIOApiServiceInterface::class, RaiderIOApiService::class);
}
```

## Configuration

```php
config('keystoneguru.heatmap.service.data.player.size_x')          // 300
config('keystoneguru.heatmap.service.data.player.size_y')          // 200
config('keystoneguru.heatmap.service.data.enemy.size_x')           // 300
config('keystoneguru.heatmap.service.data.enemy.size_y')           // 200
config('keystoneguru.heatmap.api.min_required_sample_factor_default') // 0.0005
config('keystoneguru.heatmap.api.floors_as_array')                 // true
```

## Routes

```
GET  /heatmap/{gameVersion}                                    dungeon.heatmap.gameversion
GET  /heatmap/{gameVersion}/select                            dungeon.heatmap.gameversion.select
GET  /heatmap/{gameVersion}/{dungeon}                         dungeon.heatmap.gameversion.view
GET  /heatmap/{gameVersion}/{dungeon}/{floorIndex}            dungeon.heatmap.gameversion.view.floor
GET  /heatmap/{gameVersion}/{dungeon}/embed                   dungeon.heatmap.gameversion.embed
GET  /heatmap/{gameVersion}/{dungeon}/embed/{floorIndex}      dungeon.heatmap.gameversion.embed.floor
GET  /embed/heatmap/{gameVersion}/{dungeon}                   misc.embed.heatmap
GET  /embed/heatmap/{gameVersion}/{dungeon}/{floorIndex}      misc.embed.heatmap.floor
POST /ajax/heatmap/data                                       ajax.heatmap.data
```

## Exception: `InvalidApiResponseException`

```php
// app/Service/RaiderIO/Exceptions/InvalidApiResponseException extends Exception implements Arrayable
public function __construct(string $message, private readonly string $url, private readonly string $response)

public function toArray(): array
// { message, url?, response? }
// url and response are only included when config('app.debug') is true.
```

## Patterns & Gotchas

- **Two service implementations**: local/test uses `RaiderIOKeystoneGuruApiService` (OpenSearch). Production uses `RaiderIOApiService` (Raider.IO HTTP API). Controlled by `KeystoneGuruServiceProvider` binding. Never inject the concrete class directly — always use the interface.

- **CSV vs array in form requests**: `HeatmapUrlFormRequest` validates include/exclude ID fields as **CSV strings**. `AjaxGetDataFormRequest` validates the same fields as **PHP arrays** (because JS sends `name[]`). Don't mix these conventions when adding new filters.

- **Deprecated "Explore" class names**: `ExploreUrlFormRequest` and `ExploreEmbedUrlFormRequest` are deprecated aliases for the Heatmap equivalents. Do not use them in new code.

- **Season auto-population**: `RaiderIOApiService::getHeatmapData()` resolves the most recent season automatically if `$filter->getSeason()` is null. The local service does not do this — `CombatLogEventFilter::fromHeatmapDataFilter()` handles season mapping itself.

- **`heatmap_enabled` is hidden from API serialization**: `Dungeon::$hidden` includes `heatmap_enabled`. It will not appear in JSON API responses from Eloquent resources. Read it via the model directly.

- **Admin URL exposure**: `RaiderIOHeatmapGridResponse::toArray()` exposes the Raider.IO request URL only to `ROLE_ADMIN` / `ROLE_INTERNAL_TEAM`. Regular users receive `null`. This is for debugging only — do not rely on it in frontend logic.

- **Guard order**: `guardAgainstInvalidAccess()` checks dungeon-specific conditions first (active, heatmap_enabled, mapping version, season) and the feature flag last. All conditions must pass; the first failed check redirects.

- **OpenSearch required locally**: `Heatmap::resolve()` short-circuits to `false` in the `local` environment unless `opensearch-laravel.host` is set. The `RaiderIOKeystoneGuruApiService` will fail silently if OpenSearch is not running.

- **`toArray()` key casing**: `HeatmapDataFilter::toArray()` returns snake_case keys. `RaiderIOApiService` applies `Str::camel()` when building the query string for the API. Do not pre-camelCase keys inside the DTO.
