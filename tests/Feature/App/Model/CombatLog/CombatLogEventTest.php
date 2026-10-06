<?php

namespace Tests\Feature\App\Model\CombatLog;

use App\Models\CombatLog\CombatLogEvent;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
final class CombatLogEventTest extends PublicTestCase
{
    #[Test]
    public function with_givenPersistedEvent_loadsMainDatabaseRelations(): void
    {
        // Arrange
        /** @var Floor $floor */
        $floor = Floor::query()
            ->whereNotNull('ui_map_id')
            ->whereIn('dungeon_id', Dungeon::query()->whereNotNull('challenge_mode_id')->select('id'))
            ->firstOrFail();
        $dungeon = $floor->dungeon;

        $event = null;

        try {
            $event = CombatLogEvent::create([
                'run_id'             => 'with-givenPersistedEvent',
                'keystone_run_id'    => 1,
                'logged_run_id'      => 1,
                'period'             => 1,
                'season'             => 'season-tww-1',
                'region_id'          => 1,
                'realm_type'         => 'live',
                'wow_instance_id'    => $dungeon->map_id ?? 0,
                'challenge_mode_id'  => $dungeon->challenge_mode_id,
                'level'              => 10,
                'affix_ids'          => '[]',
                'success'            => true,
                'start'              => now(),
                'end'                => now(),
                'duration_ms'        => 1,
                'par_time_ms'        => 1,
                'timer_fraction'     => 1,
                'num_deaths'         => 0,
                'ui_map_id'          => $floor->ui_map_id,
                'pos_x'              => 0,
                'pos_y'              => 0,
                'pos_grid_x'         => 0,
                'pos_grid_y'         => 0,
                'num_members'        => 5,
                'average_item_level' => 600,
                'event_type'         => 'player_death',
                'characters'         => '[]',
                'context'            => '[]',
            ]);

            // Act
            $retrieved = CombatLogEvent::with(['dungeon', 'floor'])->findOrFail($event->id);

            // Assert
            $this->assertSame($dungeon->challenge_mode_id, $retrieved->getRelation('dungeon')?->challenge_mode_id);
            $this->assertSame($floor->ui_map_id, $retrieved->getRelation('floor')?->ui_map_id);
        } finally {
            $event?->delete();
        }
    }
}
