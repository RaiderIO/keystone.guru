<?php

namespace Tests\Feature\Controller\Ajax;

use App;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Teapot\StatusCode;
use Tests\Feature\Controller\DungeonRouteTestBase;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\Fixtures\ServiceFixtures;
use Tests\Fixtures\Traits\CreatesCombatLogEvent;

final class AjaxHeatmapControllerTest extends DungeonRouteTestBase
{
    use CreatesCombatLogEvent;
    use ProvidesDungeon;

    const EVENT_TYPE = CombatLogEventEventType::NpcDeath;
    const DATA_TYPE  = CombatLogEventDataType::PlayerPosition;

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('Controller')]
    #[Group('HeatmapController')]
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
    #[Group('Controller')]
    #[Group('HeatmapController')]
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
     * PHP-LARAVEL-TT: a timer-fraction filter against a dungeon whose current mapping version has
     * no timer set (timer_max_seconds <= 0, the column's NOT NULL DEFAULT) used to bubble up
     * CombatLogEventFilter::fromHeatmapDataFilter()'s InvalidArgumentException as an uncaught 500,
     * instead of a clean 400 - this is a reachable user state (e.g. legacy dungeons), not a server
     * error.
     *
     * @throws Exception
     */
    #[Test]
    #[Group('Controller')]
    #[Group('HeatmapController')]
    public function getData_givenTimerFractionFilterAndDungeonWithoutTimer_returnsBadRequest(): void
    {
        // Arrange - pick a dungeon where every mapping version already has no timer set, rather
        // than mutating a random seeded row in the shared persistent test DB. Constrained on every
        // mapping version, not just the "current" one findDungeon() resolved: getCurrentMappingVersion()
        // falls back across game versions depending on the acting user, so a dungeon with a mix of
        // timer/no-timer mapping versions across game versions would make this test flaky.
        [$dungeon] = $this->findDungeon(
            constraint: static fn(Builder $query) => $query->whereDoesntHave(
                'mappingVersions',
                static fn(Builder $query) => $query->where('timer_max_seconds', '>', 0),
            ),
        );

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

    /**
     * @throws Exception
     */
    private function setUpTestForDungeon(Dungeon $dungeon, int $rowCountPerFloor, int $runCount, bool $useFacade = false): void
    {
        $combatLogEventFilter = new CombatLogEventFilter(
            App::make(SeasonServiceInterface::class),
            App::make(SeasonAffixGroupServiceInterface::class),
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
