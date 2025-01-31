<?php

namespace Tests\Feature\Controller\Ajax;

use App;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\Feature\Controller\DungeonRouteTestBase;
use Tests\Fixtures\ServiceFixtures;
use Tests\Fixtures\Traits\CreatesCombatLogEvent;

final class AjaxHeatmapControllerTest extends DungeonRouteTestBase
{
    use CreatesCombatLogEvent;

    const EVENT_TYPE = CombatLogEventEventType::NpcDeath;
    const DATA_TYPE = CombatLogEventDataType::PlayerPosition;

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
        $dungeon          = Dungeon::firstWhere('key', Dungeon::DUNGEON_THE_STONEVAULT);
        $this->setUpTestForDungeon($dungeon, $rowCountPerFloor, $runCount);

        // Act
        $response = $this->post(route('ajax.heatmap.data'), [
            'type' => self::EVENT_TYPE->value,
            'dataType' => self::DATA_TYPE->value,
            'dungeonId' => $dungeon->id,
        ]);

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
        $dungeon          = Dungeon::firstWhere('key', Dungeon::DUNGEON_THE_NECROTIC_WAKE);
        $this->setUpTestForDungeon($dungeon, $rowCountPerFloor, $runCount, true);

        // Act
        $response = $this->post(route('ajax.heatmap.data'), [
            'type' => self::EVENT_TYPE->value,
            'dataType' => self::DATA_TYPE->value,
            'dungeonId' => $dungeon->id,
        ]);

        // Assert
        $response->assertOk();

        $responseArr = json_decode($response->content(), true);

        // Just one floor!
        $this->assertCount(1, $responseArr['data']);
        $this->assertCount(
            $rowCountPerFloor * $dungeon->floors()->where('facade', false)->count(),
            $responseArr['data'][0]['lat_lngs']
        );
        $this->assertEquals($runCount, $responseArr['run_count']);
        $this->assertEquals(self::DATA_TYPE, CombatLogEventDataType::from($responseArr['data_type']));
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
            self::DATA_TYPE
        );

        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        $combatLogEventService = ServiceFixtures::getCombatLogEventServiceMock(
            $this,
            ['getGridAggregation'],
            $coordinatesService
        );

        $combatLogEventService->method('getGridAggregation')
            ->willReturn(
                (new CombatLogEventGridAggregationResult(
                    $coordinatesService,
                    $combatLogEventFilter,
                    $this->createGridAggregationResult($dungeon, $rowCountPerFloor),
                    $runCount
                ))->setUseFacade($useFacade)
            );
        app()->bind(CombatLogEventServiceInterface::class, fn() => $combatLogEventService);
    }
}
