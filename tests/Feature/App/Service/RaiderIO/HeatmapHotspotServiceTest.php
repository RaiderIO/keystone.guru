<?php

namespace Tests\Feature\App\Service\RaiderIO;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\HeatmapHotspotServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('RaiderIO')]
#[Group('HeatmapHotspotService')]
final class HeatmapHotspotServiceTest extends PublicTestCase
{
    private const int UNKNOWN_NPC_ID = 2147483000;

    private CoordinatesServiceInterface $coordinatesService;

    private HeatmapHotspotServiceInterface $heatmapHotspotService;

    private Dungeon $dungeon;

    private MappingVersion $mappingVersion;

    private Floor $zoneFloor;

    private Floor $facadeFloor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->coordinatesService    = app(CoordinatesServiceInterface::class);
        $this->heatmapHotspotService = app(HeatmapHotspotServiceInterface::class);
        $this->dungeon               = Dungeon::with('floors')->where('key', DungeonKey::KALIMDOR->value)->firstOrFail();
        $this->mappingVersion        = $this->dungeon->getCurrentMappingVersion();
        $this->facadeFloor           = $this->dungeon->floors->firstOrFail(static fn(Floor $floor): bool => $floor->facade);
        // A zone whose centre the facade places through a floor union
        $this->zoneFloor = $this->dungeon->floors->firstOrFail(
            fn(Floor $floor): bool => !$floor->facade && $this->mappingVersion->getFloorUnionForLatLng(
                $this->coordinatesService,
                $this->coordinatesService->calculateMapLocationForIngameLocation($this->getZoneCentre($floor)),
            ) !== null,
        );
    }

    #[Test]
    public function convertHotspotsByFloor_givenSplitFloors_returnsZoneLatLngsAndTheTopNpcs(): void
    {
        // Arrange
        $npcs    = Npc::query()->orderBy('id')->limit(3)->get();
        $centre  = $this->getZoneCentre($this->zoneFloor);
        $polygon = $this->makeSquare($centre, 1000);

        // Act
        $result = $this->heatmapHotspotService->convertHotspotsByFloor(
            $this->dungeon,
            $this->mappingVersion,
            [
                (string)$this->zoneFloor->id => [[
                    'polygon' => $polygon,
                    'npcs'    => [
                        ['npcId' => $npcs[2]->id, 'deaths' => 5],
                        ['npcId' => $npcs[0]->id, 'deaths' => 50],
                        ['npcId' => self::UNKNOWN_NPC_ID, 'deaths' => 30],
                        ['npcId' => $npcs[1]->id, 'deaths' => 15],
                    ],
                ]],
            ],
            false,
            false,
        );

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame($this->zoneFloor->id, $result[0]['floor_id']);
        $this->assertCount(1, $result[0]['hotspots']);

        $hotspot = $result[0]['hotspots'][0];
        $this->assertCount(4, $hotspot['lat_lngs']);
        foreach ($polygon as $index => $vertex) {
            $this->assertLatLng(
                $this->coordinatesService->calculateMapLocationForIngameLocation(new IngameXY($vertex[0], $vertex[1], $this->zoneFloor)),
                $hotspot['lat_lngs'][$index],
            );
        }
        $this->assertLatLng($this->coordinatesService->calculateMapLocationForIngameLocation($centre), $hotspot['center']);
        $this->assertSame(100, $hotspot['deaths']);
        $this->assertSame([
            [
                'npc_id'       => $npcs[0]->id,
                'name'         => __($npcs[0]->name),
                'portrait_url' => $npcs[0]->enemy_portrait_url,
                'deaths'       => 50,
                'percentage'   => 50.0,
            ],
            [
                'npc_id'       => self::UNKNOWN_NPC_ID,
                'name'         => null,
                'portrait_url' => null,
                'deaths'       => 30,
                'percentage'   => 30.0,
            ],
            [
                'npc_id'       => $npcs[1]->id,
                'name'         => __($npcs[1]->name),
                'portrait_url' => $npcs[1]->enemy_portrait_url,
                'deaths'       => 15,
                'percentage'   => 15.0,
            ],
        ], $hotspot['npcs']);
    }

    #[Test]
    public function convertHotspotsByFloor_givenFacadeWithoutNavigation_returnsTheHotspotOnTheFacadeOnly(): void
    {
        // Arrange
        $centre  = $this->getZoneCentre($this->zoneFloor);
        $polygon = $this->makeSquare($centre, 1000);

        // Act
        $result = $this->heatmapHotspotService->convertHotspotsByFloor(
            $this->dungeon,
            $this->mappingVersion,
            [$this->zoneFloor->id => [['polygon' => $polygon, 'npcs' => [['npcId' => self::UNKNOWN_NPC_ID, 'deaths' => 7]]]]],
            true,
            false,
        );

        // Assert
        $this->assertSame([$this->facadeFloor->id], array_column($result, 'floor_id'));
        $this->assertCount(1, $result[0]['hotspots']);

        $hotspot      = $result[0]['hotspots'][0];
        $facadeCentre = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
            $this->mappingVersion,
            $this->coordinatesService->calculateMapLocationForIngameLocation($centre),
        );
        $this->assertSame($this->facadeFloor->id, $facadeCentre->getFloor()?->id);
        $this->assertLatLng($facadeCentre, $hotspot['center']);
        $this->assertLatLng(
            $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                $this->mappingVersion,
                $this->coordinatesService->calculateMapLocationForIngameLocation(new IngameXY($polygon[0][0], $polygon[0][1], $this->zoneFloor)),
            ),
            $hotspot['lat_lngs'][0],
        );
    }

    #[Test]
    public function convertHotspotsByFloor_givenFacadeWithNavigationAndATinyHotspot_hidesItOnTheFacadeOnly(): void
    {
        // Arrange - 50 by 50 yards is far below the facade threshold of a continent, 1000 by 1000 far above it
        $centre = $this->getZoneCentre($this->zoneFloor);
        $large  = ['polygon' => $this->makeSquare($centre, 1000), 'npcs' => [['npcId' => self::UNKNOWN_NPC_ID, 'deaths' => 7]]];
        $tiny   = [
            'polygon' => $this->makeSquare(new IngameXY($centre->getX() + 700, $centre->getY(), $this->zoneFloor), 50),
            'npcs'    => [['npcId' => self::UNKNOWN_NPC_ID, 'deaths' => 70]],
        ];

        // Act
        $result = $this->heatmapHotspotService->convertHotspotsByFloor(
            $this->dungeon,
            $this->mappingVersion,
            [$this->zoneFloor->id => [$large, $tiny]],
            true,
            true,
        );

        // Assert
        $hotspotsByFloorId = array_column($result, 'hotspots', 'floor_id');
        $this->assertEqualsCanonicalizing([$this->facadeFloor->id, $this->zoneFloor->id], array_keys($hotspotsByFloorId));
        $this->assertSame([7], array_column($hotspotsByFloorId[$this->facadeFloor->id], 'deaths'));
        $this->assertEqualsCanonicalizing([7, 70], array_column($hotspotsByFloorId[$this->zoneFloor->id], 'deaths'));
    }

    #[Test]
    public function convertHotspotsByFloor_givenOverlappingHotspots_keepsTheDeadliestOfThem(): void
    {
        // Arrange - the second hotspot's centre lies inside the first, the third stands apart
        $centre     = $this->getZoneCentre($this->zoneFloor);
        $lessDeadly = ['polygon' => $this->makeSquare($centre, 1000), 'npcs' => [['npcId' => self::UNKNOWN_NPC_ID, 'deaths' => 10]]];
        $mostDeadly = [
            'polygon' => $this->makeSquare(new IngameXY($centre->getX() + 200, $centre->getY(), $this->zoneFloor), 800),
            'npcs'    => [['npcId' => self::UNKNOWN_NPC_ID, 'deaths' => 90]],
        ];
        $standsApart = [
            'polygon' => $this->makeSquare(new IngameXY($centre->getX() + 3000, $centre->getY(), $this->zoneFloor), 1000),
            'npcs'    => [['npcId' => self::UNKNOWN_NPC_ID, 'deaths' => 5]],
        ];

        // Act
        $result = $this->heatmapHotspotService->convertHotspotsByFloor(
            $this->dungeon,
            $this->mappingVersion,
            [$this->zoneFloor->id => [$lessDeadly, $mostDeadly, $standsApart]],
            false,
            false,
        );

        // Assert
        $this->assertSame([90, 5], array_column($result[0]['hotspots'], 'deaths'));
    }

    #[Test]
    public function convertHotspotsByFloor_givenUnknownFloorsAndUnusableHotspots_skipsThem(): void
    {
        // Arrange
        $centre = $this->getZoneCentre($this->zoneFloor);
        $usable = ['polygon' => $this->makeSquare($centre, 1000), 'npcs' => [['npcId' => self::UNKNOWN_NPC_ID, 'deaths' => 7]]];

        // Act
        $result = $this->heatmapHotspotService->convertHotspotsByFloor(
            $this->dungeon,
            $this->mappingVersion,
            [
                0                      => [$usable],
                $this->facadeFloor->id => [$usable],
                $this->zoneFloor->id   => [
                    'not a hotspot',
                    ['polygon' => [[0, 0], [10, 0]], 'npcs' => $usable['npcs']],
                    ['polygon' => $usable['polygon'], 'npcs' => []],
                    $usable,
                ],
            ],
            false,
            false,
        );

        // Assert
        $this->assertSame([$this->zoneFloor->id], array_column($result, 'floor_id'));
        $this->assertSame([7], array_column($result[0]['hotspots'], 'deaths'));
    }

    #[Test]
    public function convertHotspotsByFloor_givenNoHotspots_returnsAnEmptyList(): void
    {
        // Arrange
        $hotspotsByFloor = [];

        // Act
        $result = $this->heatmapHotspotService->convertHotspotsByFloor($this->dungeon, $this->mappingVersion, $hotspotsByFloor, false, false);

        // Assert
        $this->assertSame([], $result);
    }

    private function getZoneCentre(Floor $floor): IngameXY
    {
        return new IngameXY(
            ($floor->ingame_min_x + $floor->ingame_max_x) / 2,
            ($floor->ingame_min_y + $floor->ingame_max_y) / 2,
            $floor,
        );
    }

    /**
     * @return list<array{0: float, 1: float}>
     */
    private function makeSquare(IngameXY $centre, float $size): array
    {
        $half = $size / 2;

        return [
            [$centre->getX() - $half, $centre->getY() - $half],
            [$centre->getX() + $half, $centre->getY() - $half],
            [$centre->getX() + $half, $centre->getY() + $half],
            [$centre->getX() - $half, $centre->getY() + $half],
        ];
    }

    /**
     * @param array{lat: float, lng: float} $actual
     */
    private function assertLatLng(LatLng $expected, array $actual): void
    {
        $this->assertEqualsWithDelta(round($expected->getLat(), 2), $actual['lat'], 0.001);
        $this->assertEqualsWithDelta(round($expected->getLng(), 2), $actual['lng'], 0.001);
    }
}
