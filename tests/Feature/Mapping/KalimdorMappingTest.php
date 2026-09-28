<?php

namespace Tests\Feature\Mapping;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonKey;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('Mapping')]
#[Group('Kalimdor')]
final class KalimdorMappingTest extends TestCase
{
    #[Test]
    public function floors_givenKalimdor_returnsEveryZoneWithBoundsAndOneFacade(): void
    {
        // Arrange
        $dungeon = $this->getKalimdor();

        // Act
        $realFloors   = $dungeon->floors->where('facade', false);
        $facadeFloors = $dungeon->floors->where('facade', true);

        // Assert
        $this->assertCount(23, $realFloors);
        $this->assertCount(1, $facadeFloors);
        $this->assertSame('dungeons.classic.kalimdor.floors.the_barrens', $dungeon->floors->firstWhere('default', true)->name);
        $this->assertSame($realFloors->count(), $realFloors->pluck('ui_map_id')->unique()->count());
        foreach ($dungeon->floors as $floor) {
            /** @var Floor $floor */
            $this->assertNotEquals($floor->ingame_min_x, $floor->ingame_max_x, $floor->name);
            $this->assertNotEquals($floor->ingame_min_y, $floor->ingame_max_y, $floor->name);
            if (!$floor->facade) {
                $this->assertGreaterThan(0, $floor->ui_map_id, $floor->name);
            }
        }
    }

    #[Test]
    public function mappingVersion_givenKalimdor_returnsInactiveForeverGameVersion(): void
    {
        // Arrange
        $dungeon = $this->getKalimdor();

        // Act
        $gameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_FOREVER)->firstOrFail();

        // Assert
        $this->assertFalse((bool)$gameVersion->active);
        $this->assertSame(Expansion::ALL[Expansion::EXPANSION_CLASSIC], $gameVersion->expansion_id);
        $this->assertNotNull($dungeon->getCurrentMappingVersionForGameVersion($gameVersion));
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenOrgrimmarOnDurotar_returnsTopOfTheMap(): void
    {
        // Arrange
        $durotar   = $this->getFloor('durotar');
        $orgrimmar = $this->getFloor('orgrimmar');

        // Act
        $latLng = $this->getFloorCenterOn($orgrimmar, $durotar);

        // Assert: Orgrimmar sits against Durotar's northern edge
        $this->assertGreaterThan(-64, $latLng->getLat());
        $this->assertGreaterThan(0, $latLng->getLng());
        $this->assertLessThan(384, $latLng->getLng());
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenZonesOnFacade_returnsNorthUpEastRight(): void
    {
        // Arrange
        $facade = $this->getKalimdor()->floors->firstWhere('facade', true);

        // Act
        $teldrassil = $this->getFloorCenterOn($this->getFloor('teldrassil'), $facade);
        $tanaris    = $this->getFloorCenterOn($this->getFloor('tanaris'), $facade);
        $durotar    = $this->getFloorCenterOn($this->getFloor('durotar'), $facade);
        $mulgore    = $this->getFloorCenterOn($this->getFloor('mulgore'), $facade);

        // Assert
        $this->assertGreaterThan($tanaris->getLat(), $teldrassil->getLat());
        $this->assertGreaterThan($mulgore->getLng(), $durotar->getLng());
    }

    #[Test]
    public function convertMapLocationToFacadeMapLocation_givenEveryZone_matchesTheFacadeBounds(): void
    {
        // Arrange
        $coordinatesService = app(CoordinatesServiceInterface::class);
        $dungeon            = $this->getKalimdor();
        $facade             = $dungeon->floors->firstWhere('facade', true);
        $mappingVersion     = $this->getMappingVersion();

        /** @var Floor $floor */
        foreach ($dungeon->floors->where('facade', false) as $floor) {
            foreach ([[0.5, 0.5], [0.1, 0.9], [0.9, 0.2]] as [$fractionX, $fractionY]) {
                $ingameXY = new IngameXY(
                    $floor->ingame_min_x + ($floor->ingame_max_x - $floor->ingame_min_x) * $fractionX,
                    $floor->ingame_min_y + ($floor->ingame_max_y - $floor->ingame_min_y) * $fractionY,
                    $floor,
                );

                // Act
                $viaFloorUnion = $coordinatesService->convertMapLocationToFacadeMapLocation(
                    $mappingVersion,
                    $coordinatesService->calculateMapLocationForIngameLocation($ingameXY),
                );
                $direct = $coordinatesService->calculateMapLocationForIngameLocation(
                    new IngameXY($ingameXY->getX(), $ingameXY->getY(), $facade),
                );

                // Assert
                $this->assertSame($facade->id, $viaFloorUnion->getFloor()->id, $floor->name);
                $this->assertEqualsWithDelta($direct->getLat(), $viaFloorUnion->getLat(), 0.1, $floor->name);
                $this->assertEqualsWithDelta($direct->getLng(), $viaFloorUnion->getLng(), 0.1, $floor->name);
            }
        }
    }

    #[Test]
    public function dungeonFloorSwitchMarkers_givenKalimdor_returnsLinkedPairsInsideTheirFloors(): void
    {
        // Arrange
        $mappingVersion = $this->getMappingVersion();
        $facade         = $this->getKalimdor()->floors->firstWhere('facade', true);

        // Act
        $markers = DungeonFloorSwitchMarker::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->get()
            ->keyBy('id');

        // Assert
        $this->assertCount(44, $markers);
        foreach ($markers as $marker) {
            /** @var DungeonFloorSwitchMarker $marker */
            $linkedMarker = $markers->get($marker->linked_dungeon_floor_switch_marker_id);
            $this->assertNotNull($linkedMarker, (string)$marker->id);
            $this->assertSame($marker->id, $linkedMarker->linked_dungeon_floor_switch_marker_id);
            $this->assertSame($marker->floor_id, $linkedMarker->target_floor_id);
            $this->assertSame($marker->target_floor_id, $linkedMarker->floor_id);
            $this->assertNotSame($facade->id, $marker->floor_id);
            $this->assertEqualsWithDelta(-128, $marker->lat, 128, (string)$marker->id);
            $this->assertEqualsWithDelta(192, $marker->lng, 192, (string)$marker->id);
        }
    }

    private function getKalimdor(): Dungeon
    {
        return Dungeon::with('floors')->where('key', DungeonKey::KALIMDOR->value)->firstOrFail();
    }

    private function getMappingVersion(): MappingVersion
    {
        $gameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_FOREVER)->firstOrFail();

        return $this->getKalimdor()->getCurrentMappingVersionForGameVersion($gameVersion);
    }

    private function getFloor(string $zoneKey): Floor
    {
        return $this->getKalimdor()->floors->firstWhere('name', sprintf('dungeons.classic.kalimdor.floors.%s', $zoneKey));
    }

    private function getFloorCenterOn(Floor $floor, Floor $onFloor): LatLng
    {
        $coordinatesService = app(CoordinatesServiceInterface::class);

        return $coordinatesService->calculateMapLocationForIngameLocation(new IngameXY(
            ($floor->ingame_min_x + $floor->ingame_max_x) / 2,
            ($floor->ingame_min_y + $floor->ingame_max_y) / 2,
            $onFloor,
        ));
    }
}
