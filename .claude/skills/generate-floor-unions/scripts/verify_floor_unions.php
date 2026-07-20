<?php

/**
 * Tinker script: post-insert self-consistency check for a generated facade
 * mapping version. For every enemy on the dungeon's reference (enemy-carrying)
 * mapping version, project its map location onto the facade through the new
 * FloorUnions and back again; a correct union set returns the SAME floor and
 * (within a map-unit) the same coordinates. This is the "close the loop after
 * insert" verification: it needs no MDT data, so it works even for dungeons MDT
 * has no enemies for (the enemies are used only as sample points across each
 * floor).
 *
 * It catches the failure modes vision review cannot: an enemy that projects
 * into the WRONG floor's union area (floor-identity / area-coverage error), and
 * a union whose forward/inverse transforms disagree.
 *
 * Usage (from the repo root, inside the app container):
 *   KSG_FLOOR_UNION_VERIFY_MV=<new mapping version id> \
 *     php artisan tinker .claude/skills/generate-floor-unions/scripts/verify_floor_unions.php
 *
 * A per-floor summary is printed: enemies sampled, how many round-trip back to
 * the same floor, and the worst coordinate deviation. Enemies sitting exactly
 * on an area boundary can land one floor over - a handful is normal and is
 * nudged in the map editor; a whole floor failing means a swapped/absent union.
 */

use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;

$newMvId = getenv('KSG_FLOOR_UNION_VERIFY_MV');
if ($newMvId === false) {
    throw new RuntimeException('Set KSG_FLOOR_UNION_VERIFY_MV to the new mapping version id');
}

/** @var MappingVersion $newMappingVersion */
$newMappingVersion = MappingVersion::with('dungeon')->findOrFail((int)$newMvId);
$dungeon           = $newMappingVersion->dungeon;

// Reference version: the highest OTHER version of this dungeon that actually has
// enemies to sample. The new bare version has none of its own.
$referenceMappingVersion = MappingVersion::query()
    ->where('dungeon_id', $dungeon->id)
    ->where('id', '!=', $newMappingVersion->id)
    ->whereHas('enemies')
    ->orderByDesc('version')
    ->first();

if ($referenceMappingVersion === null) {
    echo sprintf("%s: no reference mapping version with enemies - cannot round-trip (this is expected for a brand-new dungeon; rely on the overlay review instead).\n", $dungeon->key);

    return;
}

/** @var CoordinatesServiceInterface $coordinatesService */
$coordinatesService = app(CoordinatesServiceInterface::class);

$enemies   = $referenceMappingVersion->enemies()->with('floor')->get();
$tolerance = 1.0;

/** @var array<int, array{floor: string, total: int, same_floor: int, within_tol: int, projected: int, max_dev: float}> $perFloor */
$perFloor = [];

foreach ($enemies as $enemy) {
    $floor = $enemy->floor;
    if ($floor === null) {
        continue;
    }

    $perFloor[$floor->id] ??= [
        'floor' => $floor->name, 'total' => 0, 'same_floor' => 0,
        'within_tol' => 0, 'projected' => 0, 'max_dev' => 0.0,
    ];
    $bucket = &$perFloor[$floor->id];
    $bucket['total']++;

    $source = $enemy->getLatLng();
    $facade = $coordinatesService->convertMapLocationToFacadeMapLocation($newMappingVersion, $source);
    $back   = $coordinatesService->convertFacadeMapLocationToMapLocation($newMappingVersion, $facade);

    // Did the union actually move the point onto the facade plane? (If the floor
    // has no union the conversions are a no-op and prove nothing.)
    if (abs($facade->getLat() - $source->getLat()) > 0.001 || abs($facade->getLng() - $source->getLng()) > 0.001) {
        $bucket['projected']++;
    }

    $sameFloor = $back->getFloor()?->id === $floor->id;
    if ($sameFloor) {
        $bucket['same_floor']++;
    }

    $deviation = sqrt(
        ($back->getLat() - $source->getLat()) ** 2 +
        ($back->getLng() - $source->getLng()) ** 2
    );
    $bucket['max_dev'] = max($bucket['max_dev'], $deviation);
    if ($sameFloor && $deviation <= $tolerance) {
        $bucket['within_tol']++;
    }
    unset($bucket);
}

echo sprintf(
    "Round-trip verify: dungeon %s, new mv %d, reference mv %d (%d enemies)\n",
    $dungeon->key,
    $newMappingVersion->id,
    $referenceMappingVersion->id,
    $enemies->count()
);

$grandTotal = 0;
$grandOk    = 0;
foreach ($perFloor as $floorId => $b) {
    $grandTotal += $b['total'];
    $grandOk    += $b['within_tol'];
    echo sprintf(
        "  floor %d %-45s: %d enemies | projected %d | same-floor %d | within %.1f %d | worst dev %.3f\n",
        $floorId,
        $b['floor'],
        $b['total'],
        $b['projected'],
        $b['same_floor'],
        $tolerance,
        $b['within_tol'],
        $b['max_dev']
    );
}
echo sprintf("  TOTAL: %d/%d enemies round-trip to the same floor within %.1f map unit\n", $grandOk, $grandTotal, $tolerance);
