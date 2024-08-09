<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\CombatLog\CombatLogEvent;
use App\Models\Dungeon;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;
use Tests\Fixtures\ServiceFixtures;
use Tests\Fixtures\Traits\CreatesCombatLogEvent;

final class AjaxHeatmapControllerTest extends DungeonRouteTestBase
{
    use CreatesCombatLogEvent;

    const EVENT_TYPE = CombatLogEvent::EVENT_TYPE_ENEMY_KILLED;
    const DATA_TYPE  = CombatLogEvent::DATA_TYPE_PLAYER_POSITION;

    #[Test]
    #[Group('Controller')]
    #[Group('HeatmapController')]
    public function getData_givenSimpleFilter_shouldReturnData(): void
    {
        // Arrange
        $rowCountPerFloor = 10;
        $runCount         = 20;
        $dungeon          = Dungeon::firstWhere('key', Dungeon::DUNGEON_FREEHOLD);
        $this->setUpTestForDungeon($dungeon, $rowCountPerFloor, $runCount);

        // Act
        $response = $this->post(route('ajax.heatmap.data'), [
            'event_type' => self::EVENT_TYPE,
            'data_type'  => self::DATA_TYPE,
            'dungeon_id' => $dungeon->id,
        ]);

        // Assert
        $response->assertOk();

        $responseArr = json_decode($response->content(), true);

        foreach ($responseArr['data'] as $floorRow) {
            $this->assertCount($rowCountPerFloor, $floorRow['lat_lngs']);
        }
        $this->assertEquals($runCount, $responseArr['run_count']);
        $this->assertEquals(self::DATA_TYPE, $responseArr['data_type']);
    }

    #[Test]
    #[Group('Controller')]
    #[Group('HeatmapController')]
    public function getData_givenDungeonWithFacade_shouldReturnData(): void
    {
        // Arrange
        $rowCountPerFloor = 10;
        $runCount         = 20;
        $dungeon          = Dungeon::firstWhere('key', Dungeon::DUNGEON_HALLS_OF_INFUSION);
        $this->setUpTestForDungeon($dungeon, $rowCountPerFloor, $runCount, true);

        // Act
        $response = $this->post(route('ajax.heatmap.data'), [
            'event_type' => self::EVENT_TYPE,
            'data_type'  => self::DATA_TYPE,
            'dungeon_id' => $dungeon->id,
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
        $this->assertEquals(self::DATA_TYPE, $responseArr['data_type']);
    }

    private function setUpTestForDungeon(Dungeon $dungeon, int $rowCountPerFloor, int $runCount, bool $useFacade = false): void
    {
        $combatLogEventFilter = new CombatLogEventFilter(
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
