<?php

namespace Tests\Feature\Mapping;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonKey;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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
    public function mappingVersion_givenKalimdor_returnsInactiveForeverGameVersionWithFacadeEnabled(): void
    {
        // Arrange
        $dungeon = $this->getKalimdor();

        // Act
        $gameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_FOREVER)->firstOrFail();

        // Assert
        $this->assertFalse((bool)$gameVersion->active);
        $this->assertSame(Expansion::ALL[Expansion::EXPANSION_CLASSIC], $gameVersion->expansion_id);
        $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);
        $this->assertNotNull($mappingVersion);
        $this->assertTrue((bool)$mappingVersion->facade_enabled);
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
    #[DataProvider('convertFacadeMapLocationToMapLocation_givenTownOnTheFacade_returnsItsZone_dataProvider')]
    public function convertFacadeMapLocationToMapLocation_givenTownOnTheFacade_returnsItsZone(
        string $zoneKey,
        float  $zonePercentX,
        float  $zonePercentY,
    ): void {
        // Arrange
        $coordinatesService = app(CoordinatesServiceInterface::class);
        $mappingVersion     = $this->getMappingVersion();
        $zone               = $this->getFloor($zoneKey);
        $latLng             = new LatLng($zonePercentY / 100 * -256, $zonePercentX / 100 * 384, $zone);
        $facadeLatLng       = $coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $latLng);

        // Act
        $result = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $facadeLatLng);

        // Assert
        $this->assertTrue((bool)$facadeLatLng->getFloor()->facade);
        $this->assertSame($zone->id, $result->getFloor()->id);
        $this->assertEqualsWithDelta($latLng->getLat(), $result->getLat(), 0.01);
        $this->assertEqualsWithDelta($latLng->getLng(), $result->getLng(), 0.01);
    }

    /**
     * A town per zone, as a percentage of that zone's own map.
     *
     * @return array<string, array{string, float, float}>
     */
    public static function convertFacadeMapLocationToMapLocation_givenTownOnTheFacade_returnsItsZone_dataProvider(): array
    {
        return [
            'Crossroads'        => ['the_barrens', 51.5, 30],
            'Ashenvale'         => ['ashenvale', 36, 50],
            'Azshara'           => ['azshara', 22, 49],
            'Auberdine'         => ['darkshore', 37, 43],
            'Darnassus'         => ['darnassus', 40, 50],
            'Desolace'          => ['desolace', 66, 8],
            'Razor Hill'        => ['durotar', 52, 43],
            'Dustwallow Marsh'  => ['dustwallow_marsh', 66, 48],
            'Emerald Sanctuary' => ['felwood', 51, 82],
            'Camp Mojache'      => ['feralas', 75, 45],
            'Nighthaven'        => ['moonglade', 51, 44],
            'Mount Hyjal'       => ['mount_hyjal', 52, 43],
            'Mulgore'           => ['mulgore', 47, 60],
            'Orgrimmar'         => ['orgrimmar', 50, 50],
            "Shen'dralas"       => ['shendralas', 46, 70],
            'Cenarion Hold'     => ['silithus', 50, 35],
            'Sun Rock Retreat'  => ['stonetalon_mountains', 47, 61],
            'Gadgetzan'         => ['tanaris', 52, 28],
            'Dolanaar'          => ['teldrassil', 56, 60],
            'Freewind Post'     => ['thousand_needles', 46, 51],
            'Thunder Bluff'     => ['thunder_bluff', 45, 50],
            "Marshal's Refuge"  => ['ungoro_crater', 44, 10],
            'Everlook'          => ['winterspring', 61, 38],
        ];
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
        $this->assertCount(56, $markers);
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

    #[Test]
    public function dungeonFloorSwitchMarkers_givenKalimdor_useTheirFloorCouplingDirectionUnlessOverridden(): void
    {
        // Arrange
        $mappingVersion = $this->getMappingVersion();
        $floorIds       = $this->getKalimdor()->floors->pluck('id');

        // Act
        $markers = DungeonFloorSwitchMarker::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->get();
        $couplings = FloorCoupling::query()
            ->whereIn('floor1_id', $floorIds)
            ->get()
            ->keyBy(static fn(FloorCoupling $floorCoupling): string => sprintf('%d-%d', $floorCoupling->floor1_id, $floorCoupling->floor2_id));

        // Assert
        $this->assertCount($markers->unique(static fn(DungeonFloorSwitchMarker $marker): string => sprintf('%d-%d', $marker->floor_id, $marker->target_floor_id))->count(), $couplings);
        foreach ($markers as $marker) {
            /** @var DungeonFloorSwitchMarker $marker */
            $floorCoupling = $couplings->get(sprintf('%d-%d', $marker->floor_id, $marker->target_floor_id));
            $this->assertNotNull($floorCoupling, (string)$marker->id);
            $this->assertNotSame($floorCoupling->direction, $marker->direction, (string)$marker->id);
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
