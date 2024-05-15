<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\CombatLog\CombatLogEvent;
use App\Models\Dungeon;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Models\CombatLogEventFilter;
use App\Service\CombatLogEvent\Models\CombatLogEventGridAggregationResult;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;
use Tests\Fixtures\ServiceFixtures;
use Tests\Fixtures\Traits\CreatesCombatLogEvent;

final class AjaxHeatmapControllerTest extends DungeonRouteTestBase
{
    use CreatesCombatLogEvent;

    #[Test]
    #[Group('Controller')]
    #[Group('HeatmapController')]
    public function getData_givenSimpleFilter_shouldReturnData(): void
    {
        // Arrange
        $eventType            = CombatLogEvent::EVENT_TYPE_ENEMY_KILLED;
        $rowCountPerFloor     = 10;
        $runCount             = 20;
        $dungeon              = Dungeon::firstWhere('key', Dungeon::DUNGEON_HALLS_OF_INFUSION);
        $combatLogEventFilter = new CombatLogEventFilter(
            $dungeon,
            $eventType
        );

        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        $combatLogEventService = ServiceFixtures::getCombatLogEventServiceMock(
            $this,
            ['getGridAggregation'],
            $coordinatesService
        );
        $combatLogEventService->method('getGridAggregation')
            ->willReturn(
                new CombatLogEventGridAggregationResult(
                    $coordinatesService,
                    $combatLogEventFilter,
                    $this->createGridAggregationResult($dungeon, $rowCountPerFloor),
                    $runCount
                )
            );
        app()->bind(CombatLogEventServiceInterface::class, fn() => $combatLogEventService);

        // Act
        $response = $this->post(route('ajax.heatmap.data'), [
            'event_type' => $eventType,
            'dungeon_id' => $combatLogEventFilter->getDungeon()->id,
        ]);

        // Assert
        $response->assertOk();

        $responseArr = json_decode($response->content(), true);

        foreach ($responseArr['data'] as $floorRow) {
            $this->assertCount($rowCountPerFloor, $floorRow['lat_lngs']);
        }
        $this->assertEquals($runCount, $responseArr['run_count']);
    }
}
