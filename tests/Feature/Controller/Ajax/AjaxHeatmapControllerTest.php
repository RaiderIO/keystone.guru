<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Dungeon;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Models\CombatLogEventSearchResult;
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
        $combatLogEventCount = 10;
        $dungeonRouteCount   = 10;

        $combatLogEventService = ServiceFixtures::getCombatLogEventServiceMock($this, ['getCombatLogEvents']);
        $combatLogEventService->method('getCombatLogEvents')
            ->willReturn(
                new CombatLogEventSearchResult(
                    $this->createCombatLogEvents($combatLogEventCount),
                    $dungeonRouteCount
                )
            );
        app()->bind(CombatLogEventServiceInterface::class, fn() => $combatLogEventService);

        // Act
        $response = $this->post(route('ajax.heatmap.data'), [
            'dungeon_id' => Dungeon::firstWhere('key', Dungeon::DUNGEON_HALLS_OF_INFUSION)->id,
        ]);

        // Assert
        $response->assertOk();

        $responseArr = json_decode($response->content(), true);
        $this->assertCount($combatLogEventCount, $responseArr['data']);
        $this->assertEquals($dungeonRouteCount, $responseArr['dungeon_route_count']);
    }
}
