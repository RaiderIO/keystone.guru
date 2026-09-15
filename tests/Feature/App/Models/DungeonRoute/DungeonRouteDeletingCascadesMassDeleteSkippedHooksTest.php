<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\MapObjectToAwakenedObeliskLink;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards #3873: {@see DungeonRoute::boot()}'s `deleting` listener used to mass-delete its
 * {@see ChallengeModeRun} and {@see MapIcon} relations via the query builder, which never fires
 * those models' own `deleting` hooks and silently orphaned their child rows.
 */
#[Group('DungeonRoute')]
final class DungeonRouteDeletingCascadesMassDeleteSkippedHooksTest extends PublicTestCase
{
    #[Test]
    public function delete_givenRouteWithChallengeModeRun_deletesItThroughItsOwnDeletingHook(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();

        $challengeModeRun = ChallengeModeRun::create([
            'dungeon_id'       => $dungeonRoute->dungeon_id,
            'dungeon_route_id' => $dungeonRoute->id,
            'level'            => 10,
            'success'          => true,
            'total_time_ms'    => 1000,
            'duplicate'        => false,
        ]);

        // ChallengeModeRun::deleting is the hook a mass delete on the relation would skip - it's
        // responsible for cleaning this row up, so assert on it rather than on challenge_mode_runs
        // itself (that row is deleted by the mass delete either way, hook or no hook)
        $challengeModeRunData = ChallengeModeRunData::create([
            'challenge_mode_run_id' => $challengeModeRun->id,
            'run_id'                => 'test-run-id',
            'correlation_id'        => 'test-correlation-id',
            'processed'             => false,
        ]);

        try {
            // Act
            $dungeonRoute->delete();

            // Assert
            $this->assertDatabaseMissing(
                'challenge_mode_runs',
                ['id' => $challengeModeRun->id],
                'combatlog',
            );
            $this->assertDatabaseMissing(
                'challenge_mode_run_data',
                ['id' => $challengeModeRunData->id],
                'combatlog',
            );
        } finally {
            ChallengeModeRunData::query()->where('id', $challengeModeRunData->id)->delete();
            ChallengeModeRun::query()->where('id', $challengeModeRun->id)->delete();
            DungeonRoute::query()->where('id', $dungeonRoute->id)->delete();
        }
    }

    #[Test]
    public function delete_givenRouteWithMapIconLinkedToAnAwakenedObelisk_deletesTheLinkThroughItsOwnDeletingHook(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();

        $mapIcon = MapIcon::factory()->create([
            'mapping_version_id' => null,
            'dungeon_route_id'   => $dungeonRoute->id,
            'floor_id'           => $dungeonRoute->dungeon->floors->first()->id,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC],
        ]);

        $link = MapObjectToAwakenedObeliskLink::create([
            'source_map_object_id'           => $mapIcon->id,
            'source_map_object_class_name'   => MapIcon::class,
            'target_map_icon_type_id'        => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED],
            'target_map_icon_seasonal_index' => 0,
        ]);

        try {
            // Act
            $dungeonRoute->delete();

            // Assert
            $this->assertDatabaseMissing('map_icons', ['id' => $mapIcon->id]);
            $this->assertDatabaseMissing('map_object_to_awakened_obelisk_links', ['id' => $link->id]);
        } finally {
            MapObjectToAwakenedObeliskLink::query()->where('id', $link->id)->delete();
            MapIcon::query()->where('id', $mapIcon->id)->delete();
            DungeonRoute::query()->where('id', $dungeonRoute->id)->delete();
        }
    }
}
