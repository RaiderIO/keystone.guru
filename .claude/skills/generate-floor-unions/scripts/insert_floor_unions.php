<?php

/**
 * Tinker script: insert generated FloorUnions + FloorUnionAreas into a fresh
 * BARE mapping version, so the result can be compared against (and does not
 * disturb) the existing mapping.
 *
 * Usage (from the repo root, inside the app container):
 *   KSG_FLOOR_UNION_IMPORT=storage/app/floor_union_import_<dungeon>.json \
 *     php artisan tinker .claude/skills/generate-floor-unions/scripts/insert_floor_unions.php
 *
 * Import JSON shape (built from the register_floors.py areas.json output by
 * adding a target_floor_id to every placement):
 * {
 *   "dungeon_key": "skyreach",
 *   "facade_floor_id": 424,
 *   "placements": [
 *     {
 *       "target_floor_id": 417,
 *       "lat": -132.27, "lng": 92.47, "size": 300.4, "rotation": 0.0,
 *       "areas": [[{"lat": ..., "lng": ...}, ...], ...]
 *     }
 *   ]
 * }
 *
 * The bare mapping version is created with MappingVersion::insertGetId() on
 * purpose: MappingVersion::create() fires the created() boot hook which clones
 * the ENTIRE previous mapping (enemies, packs, unions, ...) into the new
 * version - the quiet insert produces a version with no relationships at all.
 * (MappingService::createNewBareMappingVersion() uses create() and therefore
 * clones once a previous version exists, so it is not usable here.)
 *
 * NOTE: the previous mapping itself is left untouched, but because the new
 * version has the highest version number it BECOMES the dungeon's current
 * mapping version - with no enemies attached. Intended for dev review only;
 * delete the created version to revert.
 */

use App\Models\Dungeon;
use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Carbon;

$importPath = getenv('KSG_FLOOR_UNION_IMPORT');
if ($importPath === false || !file_exists($importPath)) {
    throw new RuntimeException('Set KSG_FLOOR_UNION_IMPORT to the import JSON path');
}

$import = json_decode(file_get_contents($importPath), true, 512, JSON_THROW_ON_ERROR);

$dungeon = Dungeon::query()->where('key', $import['dungeon_key'])->firstOrFail();

$dungeonFloors = $dungeon->floors;

$facadeFloor = $dungeonFloors->first(
    fn($floor) => $floor->id === $import['facade_floor_id'] && $floor->facade
);
if ($facadeFloor === null) {
    throw new RuntimeException(
        sprintf('facade_floor_id %d is not a facade floor of dungeon %s', $import['facade_floor_id'], $dungeon->key)
    );
}

// Scoped by game version - a raw orderByDesc(version) could pick a version
// belonging to a different game version of the same dungeon.
$latestMappingVersion = $dungeon->getCurrentMappingVersion();
if ($latestMappingVersion === null) {
    throw new RuntimeException(sprintf('Dungeon %s has no mapping version yet', $dungeon->key));
}

$dungeonFloorIds = $dungeonFloors->pluck('id');
foreach ($import['placements'] as $index => $placement) {
    foreach (['target_floor_id', 'lat', 'lng', 'size', 'rotation', 'areas'] as $requiredKey) {
        if (!isset($placement[$requiredKey])) {
            throw new RuntimeException(
                sprintf('placement %d is missing "%s" - check the jq floor mapping for unmapped floor_image#instance keys', $index, $requiredKey)
            );
        }
    }

    if (!$dungeonFloorIds->contains($placement['target_floor_id'])) {
        throw new RuntimeException(
            sprintf('placement %d: target_floor_id %d does not belong to dungeon %s', $index, $placement['target_floor_id'], $dungeon->key)
        );
    }

    if (empty($placement['areas'])) {
        throw new RuntimeException(
            sprintf('placement %d (target floor %d) has no area polygons - a FloorUnion without areas can never match a facade point', $index, $placement['target_floor_id'])
        );
    }
}

$now = Carbon::now()->toDateTimeString();

// Quiet insert - see the docblock, MappingVersion::create() would clone the previous mapping.
$newMappingVersionId = MappingVersion::insertGetId([
    'dungeon_id'        => $dungeon->id,
    'game_version_id'   => $latestMappingVersion->game_version_id,
    'mdt_mapping_hash'  => null,
    'mdt_addon_version' => null,
    'version'           => $latestMappingVersion->version + 1,
    'facade_enabled'    => true,
    'created_at'        => $now,
    'updated_at'        => $now,
]);

echo sprintf(
    "Created bare mapping version %d (version %d) for %s\n",
    $newMappingVersionId,
    $latestMappingVersion->version + 1,
    $dungeon->key
);

foreach ($import['placements'] as $placement) {
    $floorUnion = FloorUnion::create([
        'mapping_version_id' => $newMappingVersionId,
        'floor_id'           => $facadeFloor->id,
        'target_floor_id'    => $placement['target_floor_id'],
        'lat'                => $placement['lat'],
        'lng'                => $placement['lng'],
        'size'               => $placement['size'],
        'rotation'           => $placement['rotation'],
    ]);

    $areaIds = [];
    foreach ($placement['areas'] as $vertices) {
        $floorUnionArea = FloorUnionArea::create([
            'mapping_version_id' => $newMappingVersionId,
            'floor_id'           => $facadeFloor->id,
            'floor_union_id'     => $floorUnion->id,
            'vertices_json'      => json_encode($vertices),
        ]);
        $areaIds[] = $floorUnionArea->id;
    }

    echo sprintf(
        "FloorUnion %d -> target floor %d (lat=%.2f lng=%.2f size=%.1f rotation=%.1f), areas [%s]\n",
        $floorUnion->id,
        $placement['target_floor_id'],
        $placement['lat'],
        $placement['lng'],
        $placement['size'],
        $placement['rotation'],
        implode(', ', $areaIds)
    );
}

echo "Done.\n";
