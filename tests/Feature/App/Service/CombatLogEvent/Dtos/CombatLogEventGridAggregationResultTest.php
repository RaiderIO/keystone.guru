<?php

namespace Tests\Feature\App\Service\CombatLogEvent\Dtos;

use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Floor\Floor;
use App\Models\User;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesCombatLogEvent;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLogEvent')]
final class CombatLogEventGridAggregationResultTest extends PublicTestCase
{
    use CreatesDungeon;
    use CreatesCombatLogEvent;

    /**
     * A mapping version without a facade of its own renders the real floors even for a viewer whose map style is
     * facade - Dungeon::floorsForMapFacade() falls back. Re-homing every cell onto a facade floor the map is not
     * showing would silently merge the grid onto a floor nothing draws.
     */
    #[Test]
    public function toArray_givenFacadeViewerOnAMappingVersionWithoutFacade_keepsTheRealFloor(): void
    {
        // Arrange - the dungeon owns a facade floor, so only facade_enabled (false by default) decides
        $dungeon = $this->createDungeon();
        $floor   = $dungeon->floors()->firstOrFail();
        Floor::create([
            'dungeon_id' => $dungeon->id,
            'index'      => 2,
            'name'       => 'Test Facade Floor',
            'default'    => false,
            'facade'     => true,
        ]);
        $dungeon->unsetRelation('floors');

        $user                   = User::findOrFail(1);
        $originalMapFacadeStyle = $user->map_facade_style;

        try {
            $user->update(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);
            $this->be($user);

            $filter = new CombatLogEventFilter(
                app(SeasonServiceInterface::class),
                $dungeon,
                CombatLogEventEventType::NpcDeath,
                CombatLogEventDataType::PlayerPosition,
            );

            $results = $this->createGridAggregationResult($dungeon, 2);

            $result = new CombatLogEventGridAggregationResult(app(CoordinatesServiceInterface::class), $filter, $results, 1);

            // Act - deliberately not touching useFacade, so the constructor's own decision is what is asserted
            $array = $result->toArray();

            // Assert
            $floorIds = array_column($array['data'], 'floor_id');
            $this->assertContains($floor->id, $floorIds);
        } finally {
            $user->update(['map_facade_style' => $originalMapFacadeStyle]);
        }
    }
}
