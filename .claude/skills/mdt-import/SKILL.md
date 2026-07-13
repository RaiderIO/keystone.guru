---
name: mdt-import
description: How MDTImportStringService parses a compressed MDT string into a DungeonRoute — decoding, object conversion, warnings/errors, and import tests. Use when adding new importable object types, debugging import failures, or writing/modifying import tests. Not for the export direction (mdt-export) and not for bumping the MDT composer package (update-mdt-package).
---

# MDT Import String Service

Use this skill when working on the MDT (MythicDungeonTools) import pipeline — adding new importable object types, debugging import failures, or writing/modifying import tests.

## What it does

`MDTImportStringService` decodes a compressed, base64-encoded MDT string and persists it as a `DungeonRoute` with all its drawable objects (brushlines, paths, arrows, map icons, kill zones).

## Entry point & route

```
POST /ajax/dungeonroute/{dungeonRoute}/mdt/import
→ AjaxDungeonRouteController@import (or similar)
→ MDTImportStringServiceInterface::setEncodedString($string)->getDungeonRoute(...)
```

## Two-stage pipeline

### Stage 1 — `parseObjects()`: parse MDT objects into DTO collections

Loops over `$decoded['objects']` and dispatches by key:

| Key present | MDT type | Handler |
|---|---|---|
| `object['t']` | Triangle / Arrow | `parseObjectTriangle()` |
| `object['l']` | Line (brushline or path) | `parseObjectLine()` |
| `object['n']` | Note / comment | `parseObjectComment()` |

Results accumulate in `ImportStringObjects` (a plain value object):
- `getLines()` — free-drawn brushlines (`d[6] = true`)
- `getPaths()` — paths (`d[6] = false`)
- `getArrows()` — arrows (triangles; shaft only, no rotation stored)
- `getMapIcons()` — comments/notes → converted to `MapIcon`

### Stage 2 — `applyObjectsToDungeonRoute()`: batch-persist to database

Runs a single unified loop over `$typedObjects` (brushlines, paths, arrows):
1. Batch-insert model rows with `polyline_id = -1`
2. Reload all three relations to know their IDs
3. Assign `model_id` to queued polyline attributes in insertion order
4. Batch-insert `Polyline` rows
5. Query back polylines, update `polyline_id` on each owner

## MDT object detail format (from Lua)

```
d[0] = weight (1–5)          d[3] = enabled (bool)
d[1] = linefactor/smooth     d[4] = color (hex without #)
d[2] = sublevel (1-based)    d[5] = drawlayer    d[6] = smooth/free-drawn

l = [x1, y1, x2, y2, ...]   (vertex pairs in MDT coordinate space)
t = [rotationRadians]        (triangles/arrows only — ignored on import; direction is inferred at render time)
n = true                     (notes only)
```

## Coordinate conversion

```php
Conversion::convertMDTCoordinateToLatLng(['x' => ..., 'y' => ...], $floor)
// For facade dungeons, follow with:
$this->coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $latLng, $dominantFloor)
```

## Adding a new importable object type

1. Add a `private readonly Collection $xyz` + `getXyz(): Collection` to `ImportStringObjects`
2. Write `parseObjectXyz()` — convert coords, push an array shaped `['floor_id' => ..., 'polyline' => ['model_class' => Xyz::class, ...]]`
3. Add the new type to `$typedObjects` in `applyObjectsToDungeonRoute()`:
   ```php
   ['objects' => $importStringObjects->getXyz(), 'model' => Xyz::class, 'relation' => 'xyz'],
   ```
4. Extend the polyline query's `orWhere` clause to include `model_class = Xyz::class`
5. Add a limit check: `config('keystoneguru.dungeon_route_limits.xyz')`
6. Add lang keys in `lang/en_US/services.php`:
   - `import_string.category.xyz`
   - `import_string.limit_reached_xyz`

## Key files

| File | Role |
|---|---|
| `app/Service/MDT/MDTImportStringService.php` | Main service — parse + persist |
| `app/Service/MDT/Models/ImportStringObjects.php` | DTO accumulating parsed objects |
| `app/Service/MDT/Models/ImportStringDetails.php` | DTO for import preview stats (counts) |
| `app/Logic/MDT/Conversion.php` | Coordinate conversion utilities |
| `app/Logic/Structs/LatLng.php` | Lat/Lng value object (has `rotate()`) |
| `app/Models/Brushline.php`, `Path.php`, `Arrow.php` | Polymorphic drawable models |
| `app/Models/Polyline.php` | Shared vertex storage (polymorphic via `model_id` + `model_class`) |
| `config/keystoneguru.php` | `dungeon_route_limits` per type |

## Tests

Base class: `MDTImportStringServiceTestBase` → `MDTExportStringServiceTestBase` → `PublicTestCase`.

Helper methods:
- `createBrushlineForRoute(DungeonRoute)` → `Brushline`
- `createArrowForRoute(DungeonRoute)` → `Arrow`
- `exportDungeonRouteToString(DungeonRoute)` → encoded string
- `importStringToDungeonRoute(string)` → `DungeonRoute`

Test file: `tests/Feature/App/Service/MDT/MDTImportStringServiceObjectsTest.php`  
Tags: `#[Group('UsesLua')]`, `#[Group('MDTImportStringService')]`, `#[Group('MDTImportStringServiceObjects')]`

Run: `docker compose exec -T app php artisan test --compact tests/Feature/App/Service/MDT/`
