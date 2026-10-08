<?php

namespace Tests\Feature\Mapping;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('Mapping')]
#[Group('EasternKingdoms')]
final class EasternKingdomsMappingTest extends TestCase
{
    #[Test]
    public function floors_givenEasternKingdoms_returnsEveryZoneWithBoundsAndOneFacade(): void
    {
        // Arrange
        $dungeon = $this->getEasternKingdoms();

        // Act
        $realFloors   = $dungeon->floors->where('facade', false);
        $facadeFloors = $dungeon->floors->where('facade', true);

        // Assert
        $this->assertSame('dungeons.classic.eastern_kingdoms.abbreviation', $dungeon->abbreviation);
        $this->assertCount(26, $realFloors);
        $this->assertCount(1, $facadeFloors);
        $this->assertSame('dungeons.classic.eastern_kingdoms.floors.elwynn_forest', $dungeon->floors->firstWhere('default', true)->name);
        $this->assertSame(27, $facadeFloors->first()->index);
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
    public function mappingVersion_givenEasternKingdoms_returnsForeverGameVersionWithFacadeEnabled(): void
    {
        // Arrange
        $dungeon     = $this->getEasternKingdoms();
        $gameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_FOREVER)->firstOrFail();

        // Act
        $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        // Assert
        $this->assertNotNull($mappingVersion);
        $this->assertTrue((bool)$mappingVersion->facade_enabled);
    }

    #[Test]
    public function shouldUseFacadeNavigation_givenEasternKingdomsInFacadeStyle_returnsTrue(): void
    {
        // Arrange
        $mappingVersion = $this->getMappingVersion();

        // Act
        $shouldUseFacadeNavigation = User::shouldUseFacadeNavigation($mappingVersion, User::MAP_FACADE_STYLE_FACADE);

        // Assert
        $this->assertTrue($shouldUseFacadeNavigation);
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenIronforgeOnDunMorogh_returnsInsideTheMap(): void
    {
        // Arrange
        $dunMorogh = $this->getFloor('dun_morogh');
        $ironforge = $this->getFloor('ironforge');

        // Act
        $latLng = $this->getFloorCenterOn($ironforge, $dunMorogh);

        // Assert
        $this->assertLessThan(0, $latLng->getLat());
        $this->assertGreaterThan(-256, $latLng->getLat());
        $this->assertGreaterThan(0, $latLng->getLng());
        $this->assertLessThan(384, $latLng->getLng());
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenStormwindOnElwynnForest_returnsNorthWest(): void
    {
        // Arrange
        $elwynnForest  = $this->getFloor('elwynn_forest');
        $stormwindCity = $this->getFloor('stormwind_city');

        // Act
        $latLng = $this->getFloorCenterOn($stormwindCity, $elwynnForest);

        // Assert
        $this->assertGreaterThan(-128, $latLng->getLat());
        $this->assertLessThan(192, $latLng->getLng());
    }

    #[Test]
    public function calculateMapLocationForIngameLocation_givenZonesOnFacade_returnsNorthUpEastRight(): void
    {
        // Arrange
        $facade = $this->getEasternKingdoms()->floors->firstWhere('facade', true);

        // Act
        $tirisfalGlades     = $this->getFloorCenterOn($this->getFloor('tirisfal_glades'), $facade);
        $stranglethornVale  = $this->getFloorCenterOn($this->getFloor('stranglethorn_vale'), $facade);
        $silverpineForest   = $this->getFloorCenterOn($this->getFloor('silverpine_forest'), $facade);
        $easternPlaguelands = $this->getFloorCenterOn($this->getFloor('eastern_plaguelands'), $facade);

        // Assert
        $this->assertGreaterThan($stranglethornVale->getLat(), $tirisfalGlades->getLat());
        $this->assertGreaterThan($silverpineForest->getLng(), $easternPlaguelands->getLng());
    }

    #[Test]
    public function convertMapLocationToFacadeMapLocation_givenEveryZone_matchesTheFacadeBounds(): void
    {
        // Arrange
        $coordinatesService = app(CoordinatesServiceInterface::class);
        $dungeon            = $this->getEasternKingdoms();
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
            'Goldshire'           => ['elwynn_forest', 42, 65],
            'Strahnbrad'          => ['alterac_mountains', 61, 44],
            'Refuge Pointe'       => ['arathi_highlands', 45, 46],
            'Angor Fortress'      => ['badlands', 43, 30],
            'Nethergarde Keep'    => ['blasted_lands', 66, 20],
            'Pillar of Ash'       => ['burning_steppes', 50, 55],
            'Karazhan'            => ['deadwind_pass', 46, 70],
            'Kharanos'            => ['dun_morogh', 47, 52],
            'Darkshire'           => ['duskwood', 75, 47],
            "Light's Hope Chapel" => ['eastern_plaguelands', 81, 59],
            'Southshore'          => ['hillsbrad_foothills', 50, 57],
            'Ironforge'           => ['ironforge', 50, 50],
            'Thelsamar'           => ['loch_modan', 35, 47],
            'Lakeshire'           => ['redridge_mountains', 27, 45],
            "Rog'mar"             => ['riverglades', 63, 47],
            'The Cauldron'        => ['searing_gorge', 55, 45],
            'The Sepulcher'       => ['silverpine_forest', 45, 40],
            'Stormwind City'      => ['stormwind_city', 50, 50],
            'Booty Bay'           => ['stranglethorn_vale', 27, 77],
            'Stonard'             => ['swamp_of_sorrows', 46, 53],
            'Aerie Peak'          => ['the_hinterlands', 14, 48],
            'Brill'               => ['tirisfal_glades', 61, 52],
            'Undercity'           => ['undercity', 50, 50],
            'Hearthglen'          => ['western_plaguelands', 45, 18],
            'Sentinel Hill'       => ['westfall', 56, 47],
            'Dun Modr'            => ['wetlands', 48, 17],
        ];
    }

    #[Test]
    public function dungeonFloorSwitchMarkers_givenEasternKingdoms_returnsLinkedPairsDirectedByTheirFloorCoupling(): void
    {
        // Arrange
        $mappingVersion = $this->getMappingVersion();
        $dungeon        = $this->getEasternKingdoms();
        $facade         = $dungeon->floors->firstWhere('facade', true);

        // Act
        $markers = DungeonFloorSwitchMarker::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->get()
            ->keyBy('id');

        // Assert
        $this->assertCount(74, $markers);
        foreach ($markers as $marker) {
            /** @var DungeonFloorSwitchMarker $marker */
            $linkedMarker = $markers->get($marker->linked_dungeon_floor_switch_marker_id);
            $this->assertNotNull($linkedMarker, (string)$marker->id);
            $this->assertSame($marker->id, $linkedMarker->linked_dungeon_floor_switch_marker_id);
            $this->assertSame($marker->floor_id, $linkedMarker->target_floor_id);
            $this->assertSame($marker->target_floor_id, $linkedMarker->floor_id);
            $this->assertNotSame($facade->id, $marker->floor_id);
            $this->assertNull($marker->direction, (string)$marker->id);
            $this->assertContains(
                $marker->floor_coupling_direction,
                [FloorCoupling::DIRECTION_UP, FloorCoupling::DIRECTION_DOWN, FloorCoupling::DIRECTION_LEFT, FloorCoupling::DIRECTION_RIGHT],
                (string)$marker->id,
            );
            $this->assertEqualsWithDelta(-128, $marker->lat, 128, (string)$marker->id);
            $this->assertEqualsWithDelta(192, $marker->lng, 192, (string)$marker->id);
        }
        $this->assertSame(
            $dungeon->floors->where('facade', false)->pluck('id')->sort()->values()->all(),
            $markers->pluck('floor_id')->unique()->sort()->values()->all(),
        );
    }

    private function getEasternKingdoms(): Dungeon
    {
        return Dungeon::with('floors')->where('key', DungeonKey::EASTERN_KINGDOMS->value)->firstOrFail();
    }

    private function getMappingVersion(): MappingVersion
    {
        $gameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_FOREVER)->firstOrFail();

        return $this->getEasternKingdoms()->getCurrentMappingVersionForGameVersion($gameVersion);
    }

    private function getFloor(string $zoneKey): Floor
    {
        return $this->getEasternKingdoms()->floors->firstWhere('name', sprintf('dungeons.classic.eastern_kingdoms.floors.%s', $zoneKey));
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
