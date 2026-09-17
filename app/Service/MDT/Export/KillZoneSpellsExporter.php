<?php

namespace App\Service\MDT\Export;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\Spell\Spell;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Import\ObjectImporter;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class KillZoneSpellsExporter implements MDTObjectExporterInterface
{
    /**
     * @var int Map units between a pull's lowest enemy and its spells note. MDT draws an enemy portrait ~13 and a
     *          note pin 12 of its own units wide (1 map unit = 2.185 MDT units), so this keeps the two apart.
     */
    private const int KILL_ZONE_SPELLS_NOTE_DISTANCE = 6;

    /**
     * @var int Keeps the note well within ObjectImporter::IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS of its pull. Six map
     *          units exceed that on the largest floors (Nokhud Offensive: ~20 yards per unit).
     */
    private const int KILL_ZONE_SPELLS_NOTE_MAX_DISTANCE_YARDS = 25;

    /** @var array<int, array{0: int, 1: int}> The lat/lng directions a spells note is tried in: below, above, left, right */
    private const array KILL_ZONE_SPELLS_NOTE_DIRECTIONS = [[-1, 0], [1, 0], [0, -1], [0, 1]];

    /** @var array<int, float> Fractions of KILL_ZONE_SPELLS_NOTE_DISTANCE a spells note is tried at, in order */
    private const array KILL_ZONE_SPELLS_NOTE_DISTANCE_FACTORS = [1.0, 0.5];

    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
    ) {
    }

    /**
     * MDT has no concept of spells assigned to a pull. For each kill zone with spells, extract one MDT note that
     * lists the (translated) names of its spells, one per line.
     *
     * @param  Collection<int, ImportWarning> $warnings
     * @return array<int, mixed>
     */
    public function export(DungeonRoute $dungeonRoute, Collection $warnings): array
    {
        $objects = [];

        /** @var EloquentCollection<int, KillZone> $killZonesWithSpells */
        $killZonesWithSpells = $dungeonRoute->loadMissing(['killZones.enemies.floor', 'killZones.floor'])->killZones
            ->filter(static fn(KillZone $killZone): bool => $killZone->spells->isNotEmpty());

        if ($killZonesWithSpells->isEmpty()) {
            return $objects;
        }

        // KillZone eager loads its spells without their name
        $killZonesWithSpells->load([
            'spells' => static fn(Relation $query) => $query->orderBy('kill_zone_spells.id'),
        ]);

        $enemyIngameLocationsByFloorId = $this->getKillZoneEnemyIngameLocationsByFloorId($dungeonRoute);

        foreach ($killZonesWithSpells as $killZone) {
            $latLng = $this->getKillZoneSpellsNoteLatLng($dungeonRoute, $killZone, $enemyIngameLocationsByFloorId);

            if ($latLng === null) {
                $warnings->push(new ImportWarning(
                    sprintf(__('services.mdt.io.export_string.category.pull'), $killZone->index),
                    __('services.mdt.io.export_string.unable_to_place_kill_zone_spells_note'),
                ));

                continue;
            }

            $floor          = $latLng->getFloor();
            $mdtCoordinates = Conversion::convertLatLngToMDTCoordinateString($latLng);

            $objects[] = [
                'n' => true,
                'd' => [
                    1 => $mdtCoordinates['x'],
                    2 => $mdtCoordinates['y'],
                    3 => $floor->mdt_sub_level ?? $floor->index,
                    4 => true,
                    5 => $killZone->spells
                        ->map(static fn(Spell $spell): string => __($spell->name))
                        ->unique()
                        ->implode("\n"),
                ],
            ];
        }

        return $objects;
    }

    /**
     * @return array<int, array<int, array{killZoneId: int, ingameXY: IngameXY}>> The ingame location of every enemy
     *                                                                            of every pull, by floor ID.
     */
    private function getKillZoneEnemyIngameLocationsByFloorId(DungeonRoute $dungeonRoute): array
    {
        $result = [];

        foreach ($dungeonRoute->killZones as $killZone) {
            foreach ($killZone->getEnemies() as $enemy) {
                $result[$enemy->floor_id][] = [
                    'killZoneId' => $killZone->id,
                    'ingameXY'   => $this->coordinatesService->calculateIngameLocationForMapLocation(
                        new LatLng($enemy->lat, $enemy->lng, $enemy->floor),
                    ),
                ];
            }
        }

        return $result;
    }

    /**
     * Where the spells note of a kill zone goes, as exported: next to one of the pull's enemies on its dominant floor
     * - preferably below the lowest one, which keeps it clear of the description note above the pull - at the first
     * spot where re-importing the string attaches the note to this pull and no other. Right on top of the pull's
     * lowest enemy when no spot next to its enemies does. A pull without enemies uses its kill area.
     *
     * @param array<int, array<int, array{killZoneId: int, ingameXY: IngameXY}>> $enemyIngameLocationsByFloorId
     */
    private function getKillZoneSpellsNoteLatLng(DungeonRoute $dungeonRoute, KillZone $killZone, array $enemyIngameLocationsByFloorId): ?LatLng
    {
        $enemies = $killZone->getEnemies();

        if ($enemies->isEmpty()) {
            if (!$killZone->hasKillArea()) {
                return null;
            }

            $killAreaLatLng = new LatLng($killZone->lat, $killZone->lng, $killZone->floor);

            return $this->convertKillZoneSpellsNoteLatLngToExportedLatLng($dungeonRoute, $killAreaLatLng, $killAreaLatLng);
        }

        $dominantFloorId = $killZone->getDominantFloor(true)?->id;
        $enemiesOnFloor  = $enemies->where('floor_id', $dominantFloorId);

        // The dominant floor is the kill area's floor when one is set, which need not have any of the enemies on it
        if ($enemiesOnFloor->isEmpty()) {
            $enemiesOnFloor = $enemies->where('floor_id', $enemies->countBy('floor_id')->sortDesc()->keys()->first());
        }

        /** @var Collection<int, LatLng> $enemyLatLngs */
        $enemyLatLngs = $enemiesOnFloor
            ->sortBy('lat')
            ->map(static fn(Enemy $enemy): LatLng => new LatLng($enemy->lat, $enemy->lng, $enemy->floor))
            ->values();

        foreach (self::KILL_ZONE_SPELLS_NOTE_DISTANCE_FACTORS as $distanceFactor) {
            foreach ($enemyLatLngs as $enemyLatLng) {
                foreach (self::KILL_ZONE_SPELLS_NOTE_DIRECTIONS as [$latDirection, $lngDirection]) {
                    $candidateLatLng = $this->getKillZoneSpellsNoteLatLngNextTo(
                        $enemyLatLng,
                        $latDirection * $distanceFactor * self::KILL_ZONE_SPELLS_NOTE_DISTANCE,
                        $lngDirection * $distanceFactor * self::KILL_ZONE_SPELLS_NOTE_DISTANCE,
                    );

                    if ($candidateLatLng === null) {
                        continue;
                    }

                    $exportedLatLng = $this->convertKillZoneSpellsNoteLatLngToExportedLatLng($dungeonRoute, $candidateLatLng, $enemyLatLng);
                    if ($this->getKillZoneIdsClaimingNoteOnImport($dungeonRoute, $exportedLatLng, $enemyIngameLocationsByFloorId) === [$killZone->id]) {
                        return $exportedLatLng;
                    }
                }
            }
        }

        /** @var LatLng $lowestEnemyLatLng */
        $lowestEnemyLatLng = $enemyLatLngs->first();

        return $this->convertKillZoneSpellsNoteLatLngToExportedLatLng($dungeonRoute, $lowestEnemyLatLng, $lowestEnemyLatLng);
    }

    /**
     * Offsets $latLng by the given amount of map units, scaled down to at most KILL_ZONE_SPELLS_NOTE_MAX_DISTANCE_YARDS
     * ingame. Null when that leaves the map.
     */
    private function getKillZoneSpellsNoteLatLngNextTo(LatLng $latLng, float $latOffset, float $lngOffset): ?LatLng
    {
        $distanceYards = $this->coordinatesService->distanceIngameXY(
            $this->coordinatesService->calculateIngameLocationForMapLocation($latLng),
            $this->coordinatesService->calculateIngameLocationForMapLocation(
                new LatLng($latLng->getLat() + $latOffset, $latLng->getLng() + $lngOffset, $latLng->getFloor()),
            ),
        );

        if ($distanceYards > self::KILL_ZONE_SPELLS_NOTE_MAX_DISTANCE_YARDS) {
            $latOffset *= self::KILL_ZONE_SPELLS_NOTE_MAX_DISTANCE_YARDS / $distanceYards;
            $lngOffset *= self::KILL_ZONE_SPELLS_NOTE_MAX_DISTANCE_YARDS / $distanceYards;
        }

        $result = new LatLng($latLng->getLat() + $latOffset, $latLng->getLng() + $lngOffset, $latLng->getFloor());

        if ($result->getLat() < CoordinatesService::MAP_MAX_LAT || $result->getLat() > 0 ||
            $result->getLng() < 0 || $result->getLng() > CoordinatesService::MAP_MAX_LNG) {
            return null;
        }

        return $result;
    }

    /**
     * On facade routes the note must land on the same facade floor as $anchorLatLng, even if it sits just outside
     * that floor union's area.
     */
    private function convertKillZoneSpellsNoteLatLngToExportedLatLng(DungeonRoute $dungeonRoute, LatLng $latLng, LatLng $anchorLatLng): LatLng
    {
        $mappingVersion = $dungeonRoute->mappingVersion;

        if (!$mappingVersion->facade_enabled || $latLng->getFloor()?->facade) {
            return $latLng;
        }

        return $this->coordinatesService->convertMapLocationToFacadeMapLocation(
            $mappingVersion,
            $latLng,
            $mappingVersion->getFloorUnionForLatLng($this->coordinatesService, $anchorLatLng),
        );
    }

    /**
     * Mirrors ObjectImporter attaching an imported note to a pull: through the rounded MDT coordinates the note is
     * exported with and back, to the pull with the enemy nearest to it on its floor, within the importer's radius.
     *
     * @param  array<int, array<int, array{killZoneId: int, ingameXY: IngameXY}>> $enemyIngameLocationsByFloorId
     * @return array<int, int>                                                    The IDs of the kill zones tied for
     *                                                                            nearest; empty when none is in range.
     */
    private function getKillZoneIdsClaimingNoteOnImport(DungeonRoute $dungeonRoute, LatLng $exportedLatLng, array $enemyIngameLocationsByFloorId): array
    {
        $importedLatLng = Conversion::convertMDTCoordinateToLatLng(
            Conversion::convertLatLngToMDTCoordinate($exportedLatLng),
            $exportedLatLng->getFloor(),
        );

        if ($importedLatLng->getFloor()?->facade) {
            $importedLatLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation(
                $dungeonRoute->mappingVersion,
                $importedLatLng,
            );

            if ($importedLatLng->getFloor()?->facade) {
                return [];
            }
        }

        $ingameXY        = $this->coordinatesService->calculateIngameLocationForMapLocation($importedLatLng);
        $nearestDistance = (float)ObjectImporter::IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS;
        $killZoneIds     = [];

        foreach ($enemyIngameLocationsByFloorId[$importedLatLng->getFloor()?->id] ?? [] as $enemyIngameLocation) {
            $distance = $this->coordinatesService->distanceIngameXY($enemyIngameLocation['ingameXY'], $ingameXY);

            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $killZoneIds     = [$enemyIngameLocation['killZoneId']];
            } elseif ($killZoneIds !== [] && $distance === $nearestDistance && !in_array($enemyIngameLocation['killZoneId'], $killZoneIds, true)) {
                $killZoneIds[] = $enemyIngameLocation['killZoneId'];
            }
        }

        return $killZoneIds;
    }
}
