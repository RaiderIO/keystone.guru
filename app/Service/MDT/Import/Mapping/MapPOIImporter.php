<?php

namespace App\Service\MDT\Import\Mapping;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTMapPOIType;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonStart;
use App\Models\Floor\Floor;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Logging\MDTMappingImportServiceLoggingInterface;
use Exception;
use Str;

class MapPOIImporter
{
    public function __construct(
        private readonly CoordinatesServiceInterface             $coordinatesService,
        private readonly MDTMappingImportServiceLoggingInterface $log,
    ) {
    }

    /**
     * @throws Exception
     */
    public function importMapPOIs(
        ?MappingVersion $currentMappingVersion,
        MappingVersion  $newMappingVersion,
        MDTDungeon      $mdtDungeon,
        Dungeon         $dungeon,
    ): void {
        try {
            $this->log->importMapPOIsStart();
            $mdtMapPOIs = $mdtDungeon->getMDTMapPOIs();

            // Whether $newMappingVersion already has floor switch markers of its own - either cloned from
            // $currentMappingVersion by copyMappingVersionContentsToDungeon(), or (#3757/#3762) cloned from a
            // prior game version's facade-source mapping version by
            // MappingService::createNewMappingVersionFromMDTMapping() on a genuinely first-ever import (where
            // $currentMappingVersion is null). Querying $newMappingVersion directly - rather than inspecting
            // $currentMappingVersion, which is null in exactly the case that now pre-populates markers - is
            // what keeps this from creating duplicates on top of a first-ever import's cloned markers.
            $newMappingVersionHasDungeonFloorSwitchMarkers = $newMappingVersion->dungeonFloorSwitchMarkers()->exists();

            // We place the dungeon's start marker where the party actually starts running, which is rarely where MDT
            // draws its DungeonEntrance POI - so matching on position (the check every other map icon type uses)
            // finds nothing and adds MDT's on top of ours. One start marker anywhere in the mapping version is enough
            // to mean we already have one.
            $newMappingVersionHasDungeonStart = $newMappingVersion->dungeonStarts()->exists();

            if ($mdtMapPOIs->isNotEmpty()) {
                $this->log->importMapPOIsMDTHasMapPOIs();

                $genericItemMapIconTypeIds = $this->getGenericItemMapIconTypeIds();
                $this->deleteClonedGenericItemMapIcons($newMappingVersion, $genericItemMapIconTypeIds);

                foreach ($mdtMapPOIs as $mdtMapPOI) {
                    $floor = $this->findFloorByMdtSubLevel($dungeon, $newMappingVersion, $mdtMapPOI->getSubLevel());

                    $latLng = Conversion::convertMDTCoordinateToLatLng([
                        'x' => $mdtMapPOI->getX(),
                        'y' => $mdtMapPOI->getY(),
                    ], $floor);
                    $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation($newMappingVersion, $latLng);

                    $mapIconTypeKey = Conversion::convertMDTMapPOIToMapIconTypeKey($mdtMapPOI);

                    if ($mdtMapPOI->getType() === MDTMapPOIType::DungeonEntrance) {
                        if ($newMappingVersionHasDungeonStart) {
                            $this->log->importMapPOIsHaveExistingDungeonStart($latLng->toArray());

                            continue;
                        }

                        $dungeonStart = DungeonStart::create(array_merge([
                            'mapping_version_id' => $newMappingVersion->id,
                        ], $latLng->toArrayWithFloor()));
                        $newMappingVersionHasDungeonStart = true;

                        $this->log->importMapPOIsCreatedNewDungeonStart($dungeonStart->id, $dungeonStart->floor_id);
                    } elseif ($mapIconTypeKey !== null) {
                        $mapIconTypeId = MapIconType::ALL[$mapIconTypeKey];

                        // Generic item icons were just unconditionally cleared from $newMappingVersion above
                        // (deleteClonedGenericItemMapIcons) so they can be re-created fresh from MDT below -
                        // checking $currentMappingVersion here would find the old version's now-deleted-in-
                        // $newMappingVersion copy and skip re-creating it, silently dropping the icon (#4112).
                        // No predecessor to match against - always create a new icon (#3757).
                        $existingMapIcon = in_array($mapIconTypeId, $genericItemMapIconTypeIds, true)
                            ? $newMappingVersion->getMapIconNearLocation($latLng, $mapIconTypeId)
                            : $currentMappingVersion?->getMapIconNearLocation($latLng, $mapIconTypeId);
                        if ($existingMapIcon === null) {
                            $translationKey = null;
                            if ($mdtMapPOI->getType() === MDTMapPOIType::GenericAssignablePOI &&
                                isset($mdtMapPOI->getInfo()['name'])) {
                                $translationKey = sprintf('mapping.map_icons.mdt.%s', Str::snake($mdtMapPOI->getInfo()['name']));

                                if (__($translationKey) === $translationKey) {
                                    // No translation found, fallback to the raw name
                                    $this->log->importMapPOIsMissingTranslation($translationKey);
                                }
                            }

                            $mapIcon = MapIcon::create(array_merge(array_filter([
                                'mapping_version_id' => $newMappingVersion->id,
                                'map_icon_type_id'   => $mapIconTypeId,
                                'comment'            => $translationKey,
                            ]), $latLng->toArrayWithFloor()));

                            $this->log->importMapPOIsCreatedNewMapIcon(
                                $mapIcon->id,
                                $mapIcon->floor_id,
                                $mapIcon->map_icon_type_id,
                            );
                        } else {
                            $this->log->importMapPOIsMapIconAlreadyExists(
                                $existingMapIcon->id,
                                $latLng->toArray(),
                                $mdtMapPOI->getType()->value,
                            );
                        }
                    } elseif ($mdtMapPOI->getType() === MDTMapPOIType::MapLink) {
                        // So because of the linked_floor_switch_id we cannot re-import dungeon floor switches
                        // We cannot for sure map the floor switches between different versions to one another
                        // We could use coordinates but if they change it's iffy.
                        // They also don't change between mapping versions, it's not really something Blizzard _can_ change
                        // Only create fresh markers when $newMappingVersion doesn't already have any - whether
                        // because copyMappingVersionContentsToDungeon() cloned them from $currentMappingVersion,
                        // or because a first-ever import for this game version cloned them from a prior game
                        // version's facade-source mapping version (#3757/#3762) - to avoid creating duplicates
                        // on top of either.
                        if (!$newMappingVersionHasDungeonFloorSwitchMarkers) {
                            $floor = $this->findFloorByMdtSubLevel($dungeon, $newMappingVersion, $mdtMapPOI->getSubLevel());

                            $latLng = Conversion::convertMDTCoordinateToLatLng([
                                'x' => $mdtMapPOI->getX(),
                                'y' => $mdtMapPOI->getY(),
                            ], $floor);
                            $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation($newMappingVersion, $latLng);

                            $dungeonFloorSwitchMarker = DungeonFloorSwitchMarker::create(array_merge([
                                'mapping_version_id' => $newMappingVersion->id,
                                'floor_id'           => $floor->id,
                                'target_floor_id'    => $this->findFloorByMdtSubLevel($dungeon, $newMappingVersion, $mdtMapPOI->getTarget())->id,
                            ], $latLng->toArray()));
                            $this->log->importMapPOIsNewDungeonFloorSwitchMarkerOK(
                                $dungeonFloorSwitchMarker->id,
                                $dungeonFloorSwitchMarker->floor_id,
                                $dungeonFloorSwitchMarker->target_floor_id,
                            );
                        } else {
                            $this->log->importMapPOIsHaveExistingFloorSwitchMarkers(
                                $newMappingVersion->dungeonFloorSwitchMarkers()->count(),
                            );
                        }
                    } elseif (Conversion::isMDTMapPOIUnhandled($mdtMapPOI)) {
                        // MDT draws something we have no map icon type for - our map will be missing it.
                        $this->log->importMapPOIsUnhandledMapPOI(
                            $mdtMapPOI->getType()->value,
                            $mdtMapPOI->getSpellId(),
                            $mdtMapPOI->getTextureFileDataId(),
                            $mdtMapPOI->getSubLevel(),
                        );
                    }
                }
            }
        } finally {
            $this->log->importMapPOIsEnd();
        }
    }

    public function findFloorByMdtSubLevel(Dungeon $dungeon, MappingVersion $mappingVersion, int $mdtSubLevel): Floor
    {
        $activeFloors = $dungeon->floors()->active()->get();

        // A facade floor is only a usable host for a coordinate when the mapping version actually has its
        // facade enabled - convertFacadeMapLocationToMapLocation() is a no-op otherwise, which would leave
        // the coordinate stranded on the facade floor and blow up in CoordinatesService. Facade floors are
        // seeded with mdt_sub_level = 1, so without this they win the lookup for sub level 1 on every
        // dungeon that has a facade floor but has not enabled it - Throne of the Tides today (#3742),
        // and every historical mapping version from before its dungeon's facade was switched on.
        if (!$mappingVersion->facade_enabled) {
            $activeFloors = $activeFloors->filter(static fn(Floor $floor) => !$floor->facade);
        }

        // First check for mdt_sub_level, if that isn't found just match on our own index
        return $activeFloors->first(static fn(
            Floor $floor,
        ) => $floor->mdt_sub_level === $mdtSubLevel) ?? $activeFloors->first(static fn(
            Floor $floor,
        ) => $floor->index === $mdtSubLevel);
    }

    /**
     * MDT owns the item icons it draws - every map icon type in
     * {@see Conversion::MAP_POI_GENERIC_ITEM_SPELL_ID_MAP_ICON_TYPE_MAPPING} exists for one specific MDT
     * `genericItem` POI and nothing else, so the import below re-creates the complete set from MDT.
     *
     * Until this import existed those items could only be placed by hand, and
     * `copyMappingVersionContentsToDungeon()` clones such an icon into every subsequent mapping version -
     * where it would now sit next to the imported copy as a duplicate. `getMapIconNearLocation()` does not
     * catch it: its window is +/-5 lat/lng, while Maisara Caverns' hand-placed Hearty Vilebranch Stew is
     * 5.3 off MDT's position and Seat of the Triumvirate's Void Infusion 13.9 (#3993).
     *
     * @param array<int> $genericItemMapIconTypeIds
     */
    private function deleteClonedGenericItemMapIcons(MappingVersion $newMappingVersion, array $genericItemMapIconTypeIds): void
    {
        $deletedCount = $newMappingVersion->mapIcons()
            ->whereIn('map_icon_type_id', $genericItemMapIconTypeIds)
            ->delete();

        if ($deletedCount > 0) {
            $this->log->importMapPOIsDeletedClonedGenericItemMapIcons($deletedCount);
        }
    }

    /**
     * @return array<int>
     */
    private function getGenericItemMapIconTypeIds(): array
    {
        return array_map(
            static fn(string $mapIconTypeKey): int => MapIconType::ALL[$mapIconTypeKey],
            array_values(Conversion::MAP_POI_GENERIC_ITEM_SPELL_ID_MAP_ICON_TYPE_MAPPING),
        );
    }
}
