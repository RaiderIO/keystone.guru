<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTMapPOI;
use App\Logic\MDT\Entity\MDTMapPOIType;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCases\PublicTestCase;

/**
 * #4112 (MDT 6.2.3 update, The Blinding Vale): importMapPOIs() unconditionally clears every "generic item"
 * map icon (see Conversion::MAP_POI_GENERIC_ITEM_SPELL_ID_MAP_ICON_TYPE_MAPPING) cloned into the new mapping
 * version so it can re-create the complete set fresh from MDT - MDT owns these icons. But the existence
 * check gating that re-creation was reading $currentMappingVersion, i.e. the version whose clone into
 * $newMappingVersion had just been deleted, not $newMappingVersion. Since $currentMappingVersion still had
 * its own copy near the same location (it was the source of the clone), the check always found "an existing
 * icon" and skipped re-creating it in $newMappingVersion, silently dropping the icon on every re-import after
 * the first. Cold-reviewer caught this by diffing The Blinding Vale's two mapping versions directly.
 */
#[Group('MDT')]
#[Group('MappingVersion')]
final class MDTMappingImportMapPOIsGenericItemMapIconTest extends PublicTestCase
{
    #[Test]
    public function importMapPOIs_givenGenericItemMapIconClonedFromCurrentMappingVersion_recreatesItInNewMappingVersionInsteadOfSkippingIt(): void
    {
        // Arrange
        $mappingService             = $this->app->make(MappingServiceInterface::class);
        $mappingImportService       = $this->app->make(MDTMappingImportServiceInterface::class);
        [$spellId, $mapIconTypeKey] = $this->getGenericItemSpellIdAndMapIconTypeKey();
        $mapIconTypeId              = MapIconType::ALL[$mapIconTypeKey];

        [$dungeon, $sourceMappingVersion, $floor] = $this->getNonFacadeDungeonWithFloor();

        $latLng            = new LatLng(10.0, 20.0, $floor);
        $existingMapIcon   = null;
        $newMappingVersion = null;

        try {
            $existingMapIcon = MapIcon::create([
                'mapping_version_id' => $sourceMappingVersion->id,
                'floor_id'           => $floor->id,
                'map_icon_type_id'   => $mapIconTypeId,
                'lat'                => $latLng->getLat(),
                'lng'                => $latLng->getLng(),
            ]);

            // Refresh the eager-loaded relation - it was loaded before the icon above was created.
            $sourceMappingVersion->load('mapIcons');

            $newMappingVersion = $mappingService->copyMappingVersionToDungeon($sourceMappingVersion, $dungeon);
            $mappingService->copyMappingVersionContentsToDungeon($sourceMappingVersion, $newMappingVersion);
            // Force-disable the facade so convertFacadeMapLocationToMapLocation() is a no-op regardless of
            // which dungeon the fixture picked, keeping this test focused on the re-creation guard.
            $newMappingVersion->update(['facade_enabled' => false]);
            $newMappingVersion->load('mapIcons');

            $this->assertSame(
                1,
                $newMappingVersion->mapIcons->where('map_icon_type_id', $mapIconTypeId)->count(),
                'Sanity check on the fixture/clone: the new mapping version must start with the cloned generic item icon for this test to prove anything.',
            );

            $mdtCoordinate = Conversion::convertLatLngToMDTCoordinate($latLng);
            $mdtMapPOI     = new MDTMapPOI($floor->index, [
                'type' => MDTMapPOIType::GenericItem->value,
                'x'    => $mdtCoordinate['x'],
                'y'    => $mdtCoordinate['y'],
                'info' => ['spellId' => $spellId],
            ]);

            $mdtDungeon = $this->createMockPublic(MDTDungeon::class);
            $mdtDungeon->method('getMDTMapPOIs')->willReturn(collect([$mdtMapPOI]));

            // Act - simulates a routine re-import: $currentMappingVersion still has the icon that was just
            // cloned (then deleted) from it into $newMappingVersion, and MDT still reports the same POI.
            $importMapPOIs = new ReflectionMethod($mappingImportService, 'importMapPOIs');
            $importMapPOIs->invokeArgs($mappingImportService, [
                $sourceMappingVersion,
                $newMappingVersion,
                $mdtDungeon,
                $dungeon,
            ]);

            // Assert
            $this->assertSame(
                1,
                MapIcon::query()->where('mapping_version_id', $newMappingVersion->id)->where('map_icon_type_id', $mapIconTypeId)->count(),
                'importMapPOIs() must re-create the generic item map icon in the new mapping version - checking ' .
                'the old mapping version (whose icon is not what is being cloned into or displayed for the new ' .
                'one) must not cause it to be silently skipped.',
            );
        } finally {
            $newMappingVersion?->delete();
            $existingMapIcon?->delete();
        }
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function getGenericItemSpellIdAndMapIconTypeKey(): array
    {
        $spellId = array_key_first(Conversion::MAP_POI_GENERIC_ITEM_SPELL_ID_MAP_ICON_TYPE_MAPPING);

        return [$spellId, Conversion::MAP_POI_GENERIC_ITEM_SPELL_ID_MAP_ICON_TYPE_MAPPING[$spellId]];
    }

    /**
     * A non-facade dungeon (so importMapPOIs()'s facade-location conversion is a no-op, keeping this test
     * focused on the re-creation guard) with a floor that has no `mdt_sub_level` override, so
     * findFloorByMdtSubLevel() resolves it by plain floor `index`, matching the fake MDTMapPOI built above.
     *
     * @return array{0: Dungeon, 1: MappingVersion, 2: Floor}
     */
    private function getNonFacadeDungeonWithFloor(): array
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->with(['floors', 'mappingVersions.dungeonFloorSwitchMarkers', 'mappingVersions.mapIcons', 'mappingVersions.mountableAreas', 'mappingVersions.floorUnions.floorUnionAreas'])
            ->get()
            ->first(function (Dungeon $dungeon): bool {
                return $dungeon->getFacadeFloor() === null
                    && $dungeon->floors->contains(fn(Floor $floor) => $floor->mdt_sub_level === null && $floor->active)
                    && $dungeon->mappingVersions->isNotEmpty();
            });

        if ($dungeon === null) {
            $this->fail('No non-facade dungeon found with a floor with no mdt_sub_level override.');
        }

        $mappingVersion = $dungeon->mappingVersions->sortByDesc('id')->first();
        $floor          = $dungeon->floors->first(fn(Floor $floor) => $floor->mdt_sub_level === null && $floor->active);

        return [$dungeon, $mappingVersion, $floor];
    }
}
