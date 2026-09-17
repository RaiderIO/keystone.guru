<?php

namespace Tests\Feature\App\Service\CombatLogEvent\Dtos;

use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\User;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventSearchResult;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesCombatLogEvent;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLogEvent')]
final class CombatLogEventSearchResultTest extends PublicTestCase
{
    use CreatesDungeon;
    use CreatesCombatLogEvent;

    /**
     * A mapping version without a facade of its own renders the real floors even for a viewer whose map style is
     * facade - Dungeon::floorsForMapFacade() falls back. Re-homing an event onto a facade floor the map is not
     * showing would put it on a floor nothing draws.
     */
    #[Test]
    public function toArray_givenFacadeViewerOnAMappingVersionWithoutFacade_keepsTheRealFloor(): void
    {
        // Arrange - the dungeon owns a facade floor, so only facade_enabled (false by default) decides
        $dungeon = $this->createDungeon();
        $floor   = $dungeon->floors()->firstOrFail();
        $floor->update(['ui_map_id' => 1001]);
        $facadeFloor = Floor::create([
            'dungeon_id' => $dungeon->id,
            'index'      => 2,
            'name'       => 'Test Facade Floor',
            'default'    => false,
            'facade'     => true,
            'ui_map_id'  => 1002,
        ]);
        $dungeon->unsetRelation('floors');

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        FloorUnion::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $facadeFloor->id,
            'target_floor_id'    => $floor->id,
            'lat'                => 0,
            'lng'                => 0,
            'size'               => 1,
            'rotation'           => 0,
        ]);

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

            $event = $this->createCombatLogEvent([
                'id'        => 1,
                'ui_map_id' => $floor->ui_map_id,
                'pos_x'     => ($floor->ingame_min_x + $floor->ingame_max_x) / 2,
                'pos_y'     => ($floor->ingame_min_y + $floor->ingame_max_y) / 2,
            ]);

            $result = new CombatLogEventSearchResult(app(CoordinatesServiceInterface::class), $filter, collect([$event]), 0);

            // Act
            $array = $result->toArray();

            // Assert - deliberately not touching useFacade, so the toArray() call's own decision is what is asserted
            $data = array_values($array['data']);
            $this->assertCount(1, $data);
            $this->assertSame($floor->id, $data[0]['floor_id']);
            $this->assertNotSame($facadeFloor->id, $data[0]['floor_id']);
        } finally {
            $user->update(['map_facade_style' => $originalMapFacadeStyle]);
        }
    }
}
