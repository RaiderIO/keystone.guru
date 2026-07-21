---
name: mdt-export
description: How MDTExportStringService converts a DungeonRoute into a compressed MDT-importable string — object structure, Lua serialization, and export tests. Use when adding new exportable object types, debugging export output, or writing/modifying export tests. Not for the import direction (mdt-import) and not for bumping the MDT composer package (update-mdt-package).
---

# MDT Export String Service

Use this skill when working on the MDT (MythicDungeonTools) export pipeline — adding new exportable object types, debugging export output, or writing/modifying export tests.

## What it does

`MDTExportStringService` converts a `DungeonRoute` into a compressed, base64-encoded string that MDT can import. It mirrors the full MDT object structure (notes, lines, arrows, kill zones).

## Entry point & route

```
POST /ajax/dungeonroute/{dungeonRoute}/mdt/export  (or similar)
→ MDTExportStringServiceInterface::setDungeonRoute($route)->getEncodedString($warnings)
```

## `extractObjects()` — the core method

Builds the `objects` array in three passes. Objects are 1-indexed (Lua convention).

### Pass 1: Map icons → MDT notes

```php
$result[$n] = ['n' => true, 'd' => [1 => x, 2 => y, 3 => sublevel, 4 => true, 5 => comment]];
```

### Pass 2: Brushlines + paths → MDT lines

Merged via `brushlines()->get()->merge(paths()->get())`. Vertices use the A→B, B→C duplication pattern MDT requires:

```php
$mdtLine['l'][$i++] = $previousCoords['x'];
$mdtLine['l'][$i++] = $previousCoords['y'];
$mdtLine['l'][$i++] = $currentCoords['x'];
$mdtLine['l'][$i++] = $currentCoords['y'];
```

### Pass 3: Arrows → MDT triangle objects

```php
$result[$n] = ['d' => [...], 'l' => [...], 't' => [1 => $rotation]];
// Rotation = atan2($dy, $dx) of first→last MDT coordinate pair
```
The `'t'` key is what makes MDT treat the object as a triangle (arrowhead). On import, this rotation tells MDT how to draw the arrowhead lines; on keystone.guru, it is ignored and the arrowhead is rendered as a decorator from the line direction.

### Pass 4: Kill zone descriptions → MDT notes

Each kill zone with a description is exported as an MDT note near the pull's enemies.

## Coordinate conversion

```php
// LatLng → MDT x/y
$mdtCoords = Conversion::convertLatLngToMDTCoordinateString($latLng); // ['x' => ..., 'y' => ...]

// For facade dungeons, convert first:
$latLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $latLng);
// Then update the sublevel field: $mdtLine['d'][3] = $latLng->getFloor()->mdt_sub_level ?? $latLng->getFloor()->index;
```

## Adding a new exportable object type

1. Add a loop in `extractObjects()` after the existing passes
2. Query: `$this->dungeonRoute->yourType()->with(['floor'])->get()`
3. Guard against null polyline: `if ($obj->polyline === null) { continue; }`
4. Call `$obj->polyline->getDecodedLatLngs($obj->floor)` to get `Collection<LatLng>`
5. Apply facade conversion per vertex if needed
6. Convert each LatLng: `Conversion::convertLatLngToMDTCoordinateString($latLng)`
7. Set MDT-specific keys in `$result[$currentObjectIndex++]`

## Key files

| File | Role |
|---|---|
| `app/Service/MDT/MDTExportStringService.php` | Main service |
| `app/Logic/MDT/Conversion.php` | `convertLatLngToMDTCoordinateString()`, `convertHtmlToMdtComment()` |
| `app/Models/Polyline.php` | `getDecodedLatLngs(Floor $floor): Collection<LatLng>` |
| `app/Service/MDT/PhpArray2LuaTable.php` | Serializes PHP arrays to Lua table syntax |
| `app/Console/Commands/Traits/ConvertsMDTStrings.php` | `decode(string): string` — used in tests to decode back to JSON |

## Tests

Base class: `MDTExportStringServiceTestBase` (uses `ConvertsMDTStrings` + `GeneratesDungeonRoutes`).

Key test helper:
```php
$decodedString = json_decode($this->decode($encodedString), true);
// $decodedString['objects'] — array of exported MDT objects
```

Test files:
- `tests/Feature/App/Service/MDT/MDTExportStringServiceExtractObjectsTest.php` — map icons, kill zone descriptions
- `tests/Feature/App/Service/MDT/MDTExportStringServiceExtractArrowsTest.php` — arrow export:
  - `extractObjects_givenRouteWithArrow_shouldExportArrowAsTriangleObject` — asserts `t` key present with rotation
  - `extractObjects_givenRouteWithArrow_shouldNotExportAsBrushlineOrPath` — asserts exactly 1 exported object
  - `extractObjects_givenRouteWithArrow_roundTripShouldProduceOneArrow` — full export+import round-trip

Tags: `#[Group('UsesLua')]`, `#[Group('MDTExportStringService')]`, `#[Group('MDTExportStringServiceExtractArrows')]`

Run: `docker compose exec -T app php artisan test --compact tests/Feature/App/Service/MDT/`
