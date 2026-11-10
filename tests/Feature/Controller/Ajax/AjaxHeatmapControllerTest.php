<?php

namespace Tests\Feature\Controller\Ajax;

use App;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Models\User;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\Exceptions\InvalidApiResponseException;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Teapot\StatusCode;
use Tests\Feature\Controller\DungeonRouteTestBase;
use Tests\Fixtures\ServiceFixtures;
use Tests\Fixtures\Traits\CreatesCombatLogEvent;
use Tests\Fixtures\Traits\CreatesDungeon;

#[Group('Controller')]
#[Group('HeatmapController')]
final class AjaxHeatmapControllerTest extends DungeonRouteTestBase
{
    use CreatesCombatLogEvent;
    use CreatesDungeon;

    const EVENT_TYPE = CombatLogEventEventType::NpcDeath;
    const DATA_TYPE  = CombatLogEventDataType::PlayerPosition;

    /**
     * @throws Exception
     */
    #[Test]
    public function getData_givenSimpleFilter_shouldReturnData(): void
    {
        // Arrange
        $rowCountPerFloor = 10;
        $runCount         = 20;
        $dungeon          = Dungeon::firstWhere('key', DungeonKey::THE_STONEVAULT->value);
        $this->setUpTestForDungeon($dungeon, $rowCountPerFloor, $runCount);

        // Act
        $response = $this->get(route('ajax.heatmap.data', [
            'type'      => self::EVENT_TYPE->value,
            'dataType'  => self::DATA_TYPE->value,
            'dungeonId' => $dungeon->id,
        ]));

        // Assert
        $response->assertOk();

        $responseArr = json_decode($response->content(), true);

        $this->assertCount($dungeon->floors()->where('facade', false)->count(), $responseArr['data']);
        foreach ($responseArr['data'] as $floorRow) {
            $this->assertCount($rowCountPerFloor, $floorRow['lat_lngs']);
        }
        $this->assertEquals($runCount, $responseArr['run_count']);
        $this->assertEquals(self::DATA_TYPE, CombatLogEventDataType::from($responseArr['data_type']));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getData_givenDungeonWithFacade_shouldReturnData(): void
    {
        // Arrange
        $rowCountPerFloor = 10;
        $runCount         = 20;
        $dungeon          = Dungeon::firstWhere('key', DungeonKey::THE_NECROTIC_WAKE->value);
        $this->setUpTestForDungeon($dungeon, $rowCountPerFloor, $runCount, true);

        // Act
        $response = $this->get(route('ajax.heatmap.data', [
            'type'      => self::EVENT_TYPE->value,
            'dataType'  => self::DATA_TYPE->value,
            'dungeonId' => $dungeon->id,
        ]));

        // Assert
        $response->assertOk();

        $responseArr = json_decode($response->content(), true);

        // Just one floor!
        $this->assertCount(1, $responseArr['data']);
        $this->assertCount(
            $rowCountPerFloor * $dungeon->floors()->where('facade', false)->count(),
            $responseArr['data'][0]['lat_lngs'],
        );
        $this->assertEquals($runCount, $responseArr['run_count']);
        $this->assertEquals(self::DATA_TYPE, CombatLogEventDataType::from($responseArr['data_type']));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getData_givenTimerFractionFilterAndDungeonWithoutTimer_returnsBadRequest(): void
    {
        // Arrange
        $dungeon = $this->createDungeon(mappingVersionAttributes: ['timer_max_seconds' => 0]);

        $this->setUpTestForDungeon($dungeon, 10, 20);

        // Act
        $response = $this->get(route('ajax.heatmap.data', [
            'type'             => self::EVENT_TYPE->value,
            'dataType'         => self::DATA_TYPE->value,
            'dungeonId'        => $dungeon->id,
            'minTimerFraction' => 0.0,
            'maxTimerFraction' => 1.0,
        ]));

        // Assert
        $response->assertStatus(StatusCode::BAD_REQUEST);
        $this->assertSame(
            'Mapping version does not have a timer max seconds value',
            json_decode($response->content(), true)['message'],
        );
    }

    #[Test]
    public function getData_givenMissingDungeonId_returnsUnprocessableEntity(): void
    {
        // Arrange
        $combatLogEventService = $this->createMock(CombatLogEventServiceInterface::class);
        $combatLogEventService->expects($this->never())->method('getGridAggregation');
        app()->instance(CombatLogEventServiceInterface::class, $combatLogEventService);

        // Act
        $response = $this->getJson(route('ajax.heatmap.data', [
            'type'     => self::EVENT_TYPE->value,
            'dataType' => self::DATA_TYPE->value,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['dungeonId']);
    }

    #[Test]
    #[DataProvider('getData_givenFilterValueWithQuerySeparators_returnsUnprocessableEntity_dataProvider')]
    public function getData_givenFilterValueWithQuerySeparators_returnsUnprocessableEntity(string $field, string $value): void
    {
        // Arrange
        $dungeon            = Dungeon::firstWhere('key', DungeonKey::THE_STONEVAULT->value);
        $raiderIOApiService = $this->createMock(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->expects($this->never())->method('getHeatmapData');
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        // Act
        $response = $this->getJson(route('ajax.heatmap.data', [
            'type'      => self::EVENT_TYPE->value,
            'dataType'  => self::DATA_TYPE->value,
            'dungeonId' => $dungeon->id,
            $field      => $value,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([$field]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function getData_givenFilterValueWithQuerySeparators_returnsUnprocessableEntity_dataProvider(): array
    {
        return [
            'excludeAffixIds'            => ['excludeAffixIds', '3&challengeModeId=1'],
            'excludeClassIds'            => ['excludeClassIds', '2&season=season-tww-1'],
            'excludeSpecIds'             => ['excludeSpecIds', '5=6'],
            'excludePlayerDeathSpecIds'  => ['excludePlayerDeathSpecIds', '7,8&type=player_death'],
            'excludePlayerDeathClassIds' => ['excludePlayerDeathClassIds', '1#'],
            'token'                      => ['token', 'abc&season=season-tww-1'],
        ];
    }

    #[Test]
    public function getData_givenWellFormedPassThroughFilters_passesThemToTheService(): void
    {
        // Arrange
        $dungeon            = Dungeon::firstWhere('key', DungeonKey::THE_STONEVAULT->value);
        $token              = 'eyJtYXhUaW1lckZyYWN0aW9uIjo5OTl9+/_-==';
        $raiderIOApiService = $this->createMock(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->expects($this->once())
            ->method('getHeatmapData')
            ->with($this->callback(fn(HeatmapDataFilter $filter): bool => $filter->getExcludeAffixIds() === '3,4' &&
                $filter->getExcludeClassIds() === '2' &&
                $filter->getExcludeSpecIds() === '5,6' &&
                $filter->getExcludePlayerDeathSpecIds() === '7' &&
                $filter->getExcludePlayerDeathClassIds() === '8,9' &&
                $filter->getToken() === $token))
            ->willThrowException(new InvalidApiResponseException('Invalid response from Raider.IO API'));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        // Act
        $response = $this->getJson(route('ajax.heatmap.data', [
            'type'                       => self::EVENT_TYPE->value,
            'dataType'                   => self::DATA_TYPE->value,
            'dungeonId'                  => $dungeon->id,
            'excludeAffixIds'            => '3,4',
            'excludeClassIds'            => '2',
            'excludeSpecIds'             => '5,6',
            'excludePlayerDeathSpecIds'  => '7',
            'excludePlayerDeathClassIds' => '8,9',
            'token'                      => $token,
        ]));

        // Assert
        $response->assertStatus(StatusCode::INTERNAL_SERVER_ERROR);
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function getData_givenContinentOnSplitFloors_returnsEveryZoneOnItsOwnFloor(DungeonKey $dungeonKey): void
    {
        // Arrange
        $dungeon      = Dungeon::where('key', $dungeonKey->value)->firstOrFail();
        $gridsByFloor = $this->getContinentFixtureGridsByFloor($dungeon);

        $user = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);

        try {
            $this->be($user);

            // Act
            $response = $this->get(route('ajax.heatmap.data', [
                'type'      => CombatLogEventEventType::PlayerDeath->value,
                'dataType'  => self::DATA_TYPE->value,
                'dungeonId' => $dungeon->id,
            ]));
        } finally {
            $user->delete();
        }

        // Assert
        $response->assertOk();
        $latLngsByFloorId = array_column($response->json('data'), 'lat_lngs', 'floor_id');
        $this->assertEqualsCanonicalizing(array_keys($gridsByFloor), array_keys($latLngsByFloorId));
        foreach ($gridsByFloor as $floorId => $grid) {
            $this->assertCount(count($grid) / 3, $latLngsByFloorId[$floorId]);
            $this->assertLatLngWeight($this->getZoneLatLng($dungeon, $floorId, $grid), $grid[2], $latLngsByFloorId[$floorId][0]);
        }
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function getData_givenContinentOnSplitFloors_convertsEveryCellLikeTheRegularConversion(DungeonKey $dungeonKey): void
    {
        // Arrange
        $dungeon      = Dungeon::where('key', $dungeonKey->value)->firstOrFail();
        $gridsByFloor = $this->getContinentFixtureGridsByFloor($dungeon);

        $user = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);

        try {
            $this->be($user);

            // Act
            $response = $this->get(route('ajax.heatmap.data', [
                'type'      => CombatLogEventEventType::PlayerDeath->value,
                'dataType'  => self::DATA_TYPE->value,
                'dungeonId' => $dungeon->id,
            ]));
        } finally {
            $user->delete();
        }

        // Assert
        $response->assertOk();
        $latLngsByFloorId = array_column($response->json('data'), 'lat_lngs', 'floor_id');
        foreach ($gridsByFloor as $floorId => $grid) {
            $floor = $dungeon->floors()->findOrFail($floorId);
            foreach (array_chunk($grid, 3) as $index => [$x, $y, $count]) {
                $expected = app(CoordinatesServiceInterface::class)->calculateMapLocationForIngameLocation(new IngameXY($x, $y, $floor));
                $actual   = $latLngsByFloorId[$floorId][$index];

                // Both round to two decimals, so a value on a rounding boundary may land one step apart
                $this->assertEqualsWithDelta($expected->getLat(), $actual['lat'], 0.0051);
                $this->assertEqualsWithDelta($expected->getLng(), $actual['lng'], 0.0051);
                $this->assertSame($count, $actual['weight']);
            }
        }
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function getData_givenContinentOnFacade_sumsEveryZoneOntoTheFacadeGridAndKeepsEachZoneNavigable(DungeonKey $dungeonKey): void
    {
        // Arrange
        $dungeon      = Dungeon::where('key', $dungeonKey->value)->firstOrFail();
        $gridsByFloor = $this->getContinentFixtureGridsByFloor($dungeon);
        /** @var Floor $facadeFloor */
        $facadeFloor  = $dungeon->floors()->where('facade', true)->firstOrFail();
        $firstFloorId = array_key_first($gridsByFloor);
        $firstGrid    = $gridsByFloor[$firstFloorId];
        $gridSizeX    = 128;
        $gridSizeY    = 192;
        config([
            'keystoneguru.heatmap.continent.facade_grid_size_x' => $gridSizeX,
            'keystoneguru.heatmap.continent.facade_grid_size_y' => $gridSizeY,
        ]);

        $user = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);

        try {
            $this->be($user);

            // Act
            $response = $this->get(route('ajax.heatmap.data', [
                'type'      => CombatLogEventEventType::PlayerDeath->value,
                'dataType'  => self::DATA_TYPE->value,
                'dungeonId' => $dungeon->id,
            ]));
        } finally {
            $user->delete();
        }

        // Assert
        $response->assertOk();
        $floorDataByFloorId = array_column($response->json('data'), null, 'floor_id');
        $this->assertEqualsCanonicalizing(array_merge([$facadeFloor->id], array_keys($gridsByFloor)), array_keys($floorDataByFloorId));

        $facadeData = $floorDataByFloorId[$facadeFloor->id];
        $this->assertSame($gridSizeX, $facadeData['grid_size_x']);
        $this->assertSame($gridSizeY, $facadeData['grid_size_y']);
        $this->assertSame(max(array_column($facadeData['lat_lngs'], 'weight')), $facadeData['weight_max']);
        // Summed, not dropped: every zone lies on the facade map
        $this->assertSame(
            array_sum(array_map(static fn(array $grid) => array_sum(array_column(array_chunk($grid, 3), 2)), $gridsByFloor)),
            array_sum(array_column($facadeData['lat_lngs'], 'weight')),
        );
        // Fewer cells than the zones had, or nothing was summed
        $this->assertLessThan(array_sum(array_map('count', $gridsByFloor)) / 3, count($facadeData['lat_lngs']));

        $facadeLatLng = app(CoordinatesServiceInterface::class)->convertMapLocationToFacadeMapLocation(
            $dungeon->getCurrentMappingVersion(),
            $this->getZoneLatLng($dungeon, $firstFloorId, $firstGrid),
        );
        $this->assertSame($facadeFloor->id, $facadeLatLng->getFloor()->id);
        $expectedCellLat = (floor($facadeLatLng->getLat() / CoordinatesService::MAP_MAX_LAT * $gridSizeX) + 0.5) / $gridSizeX * CoordinatesService::MAP_MAX_LAT;
        $expectedCellLng = (floor($facadeLatLng->getLng() / CoordinatesService::MAP_MAX_LNG * $gridSizeY) + 0.5) / $gridSizeY * CoordinatesService::MAP_MAX_LNG;
        $facadeCell      = collect($facadeData['lat_lngs'])->first(
            static fn(array $latLng) => abs($latLng['lat'] - round($expectedCellLat, 2)) < 0.001 && abs($latLng['lng'] - round($expectedCellLng, 2)) < 0.001,
        );
        $this->assertNotNull($facadeCell, 'The first zone cell lands in the facade cell around its facade location');
        $this->assertGreaterThanOrEqual($firstGrid[2], $facadeCell['weight']);

        $this->assertArrayNotHasKey('grid_size_x', $floorDataByFloorId[$firstFloorId]);
        $this->assertLatLngWeight($this->getZoneLatLng($dungeon, $firstFloorId, $firstGrid), $firstGrid[2], $floorDataByFloorId[$firstFloorId]['lat_lngs'][0]);
    }

    #[Test]
    public function getData_givenDungeonWithFacade_returnsEveryCellOnTheResponseGrid(): void
    {
        // Arrange
        $rowCountPerFloor = 10;
        $dungeon          = Dungeon::firstWhere('key', DungeonKey::THE_NECROTIC_WAKE->value);
        $this->setUpTestForDungeon($dungeon, $rowCountPerFloor, 20, true);
        config([
            'keystoneguru.heatmap.continent.facade_grid_size_x' => 1,
            'keystoneguru.heatmap.continent.facade_grid_size_y' => 1,
        ]);

        // Act
        $response = $this->get(route('ajax.heatmap.data', [
            'type'      => self::EVENT_TYPE->value,
            'dataType'  => self::DATA_TYPE->value,
            'dungeonId' => $dungeon->id,
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame(config('keystoneguru.heatmap.service.data.player.size_x'), $response->json('grid_size_x'));
        $this->assertSame(config('keystoneguru.heatmap.service.data.player.size_y'), $response->json('grid_size_y'));
        $floorData = $response->json('data.0');
        $this->assertSame(['floor_id', 'lat_lngs'], array_keys($floorData));
        $this->assertCount($rowCountPerFloor * $dungeon->floors()->where('facade', false)->count(), $floorData['lat_lngs']);
    }

    /**
     * @return array<string, array{0: DungeonKey}>
     */
    public static function continentProvider(): array
    {
        return [
            'kalimdor'         => [DungeonKey::KALIMDOR],
            'eastern kingdoms' => [DungeonKey::EASTERN_KINGDOMS],
        ];
    }

    #[Test]
    public function getData_givenContinentAndMockFixtureDirectory_readsTheContinentFromThatDirectory(): void
    {
        // Arrange
        $dungeon   = Dungeon::with('floors')->where('key', DungeonKey::KALIMDOR->value)->firstOrFail();
        /** @var Floor $zoneFloor */
        $zoneFloor = $dungeon->floors->where('facade', false)->sortByDesc('id')->first();
        $fixtureDir = storage_path(sprintf('framework/testing/continent-heatmap-%s', uniqid()));
        File::ensureDirectoryExists($fixtureDir);
        File::put(sprintf('%s/heatmap_grid_map_%d.json', $fixtureDir, $dungeon->map_id), (string)json_encode([
            CombatLogEventEventType::PlayerDeath->value => [
                'gridsByFloor'     => [$zoneFloor->id => [$zoneFloor->ingame_min_x, $zoneFloor->ingame_min_y, 42]],
                'numRuns'          => 42,
                'maxSamplesInGrid' => 42,
            ],
        ]));
        config(['keystoneguru.raiderio.mock_continent_heatmap_fixture_directory' => $fixtureDir]);

        $user = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);

        try {
            $this->be($user);

            // Act
            $response = $this->get(route('ajax.heatmap.data', [
                'type'      => CombatLogEventEventType::PlayerDeath->value,
                'dataType'  => self::DATA_TYPE->value,
                'dungeonId' => $dungeon->id,
            ]));
        } finally {
            $user->delete();
            File::deleteDirectory($fixtureDir);
        }

        // Assert
        $response->assertOk();
        $this->assertSame(42, $response->json('run_count'));
        $this->assertSame([$zoneFloor->id], array_column($response->json('data'), 'floor_id'));
        $this->assertSame(42, $response->json('data.0.lat_lngs.0.weight'));
    }

    #[Test]
    public function getData_givenContinentWithMythicPlusFilters_ignoresThem(): void
    {
        // Arrange - a continent's mapping version has no timer, so a timer fraction that got through would be a 400
        $dungeon = Dungeon::where('key', DungeonKey::KALIMDOR->value)->firstOrFail();

        // Act
        $response = $this->get(route('ajax.heatmap.data', [
            'type'             => CombatLogEventEventType::PlayerDeath->value,
            'dataType'         => self::DATA_TYPE->value,
            'dungeonId'        => $dungeon->id,
            'minMythicLevel'   => 10,
            'maxMythicLevel'   => 12,
            'minTimerFraction' => 0.0,
            'maxTimerFraction' => 1.0,
            'season'           => 'season-mn-1',
        ]));

        // Assert
        $response->assertOk();
        $this->assertNotEmpty($response->json('data'));
    }

    #[Test]
    public function getData_givenContinentAndEventTypeWithoutMockData_returnsAnEmptyGrid(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', DungeonKey::EASTERN_KINGDOMS->value)->firstOrFail();

        $user = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);

        try {
            $this->be($user);

            // Act
            $response = $this->get(route('ajax.heatmap.data', [
                'type'      => CombatLogEventEventType::NpcDeath->value,
                'dataType'  => self::DATA_TYPE->value,
                'dungeonId' => $dungeon->id,
            ]));
        } finally {
            $user->delete();
        }

        // Assert
        $response->assertOk();
        $this->assertSame([], $response->json('data'));
        $this->assertSame(0, $response->json('run_count'));
    }

    /**
     * @return array<int, array<int, int|float>>
     */
    private function getContinentFixtureGridsByFloor(Dungeon $dungeon): array
    {
        // The committed fixture, whatever a local environment points the mock at
        config(['keystoneguru.raiderio.mock_continent_heatmap_fixture_directory' => database_path('data/raiderio_mock')]);
        $fixture = json_decode(
            (string)file_get_contents(database_path(sprintf('data/raiderio_mock/heatmap_grid_map_%d.json', $dungeon->map_id))),
            true,
        );

        $gridsByFloor = $fixture[CombatLogEventEventType::PlayerDeath->value]['gridsByFloor'];
        // Several zones, or the facade fold proves nothing
        $this->assertGreaterThan(1, count($gridsByFloor));

        return $gridsByFloor;
    }

    /**
     * @param array<int, int|float> $grid
     */
    private function getZoneLatLng(Dungeon $dungeon, int $floorId, array $grid): LatLng
    {
        return app(CoordinatesServiceInterface::class)->calculateMapLocationForIngameLocation(
            new IngameXY($grid[0], $grid[1], $dungeon->floors()->findOrFail($floorId)),
        );
    }

    /**
     * @param array{lat: float, lng: float, weight: int} $actual
     */
    private function assertLatLngWeight(LatLng $expected, int $expectedWeight, array $actual): void
    {
        $this->assertEqualsWithDelta(round($expected->getLat(), 2), $actual['lat'], 0.001);
        $this->assertEqualsWithDelta(round($expected->getLng(), 2), $actual['lng'], 0.001);
        $this->assertSame($expectedWeight, $actual['weight']);
    }

    /**
     * @throws Exception
     */
    private function setUpTestForDungeon(Dungeon $dungeon, int $rowCountPerFloor, int $runCount, bool $useFacade = false): void
    {
        $combatLogEventFilter = new CombatLogEventFilter(
            App::make(SeasonServiceInterface::class),
            $dungeon,
            self::EVENT_TYPE,
            self::DATA_TYPE,
        );

        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        $combatLogEventService = ServiceFixtures::getCombatLogEventServiceMock(
            $this,
            ['getGridAggregation'],
            $coordinatesService,
        );

        $combatLogEventService->method('getGridAggregation')
            ->willReturn(
                new CombatLogEventGridAggregationResult(
                    $coordinatesService,
                    $combatLogEventFilter,
                    $this->createGridAggregationResult($dungeon, $rowCountPerFloor),
                    $runCount,
                )->setUseFacade($useFacade),
            );
        app()->bind(CombatLogEventServiceInterface::class, fn() => $combatLogEventService);
    }
}
