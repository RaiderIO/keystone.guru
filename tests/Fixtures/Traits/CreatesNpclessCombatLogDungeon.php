<?php

namespace Tests\Fixtures\Traits;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use Database\Factories\FloorFactory;
use Tests\TestCase;

/**
 * Fixtures for #4354: a dungeon that a synthetic combat log resolves to, with no NPCs attached to it.
 *
 * @mixin TestCase
 */
trait CreatesNpclessCombatLogDungeon
{
    /**
     * A minimal synthetic combat log. A single ZONE_CHANGE is enough for
     * CombatLogMappingVersionService to resolve the dungeon, which is where both the missing-NPC check
     * and the 0-enemy outcome are decided. The `_events.txt` suffix is required by the command.
     */
    private function createZoneChangeCombatLog(int $mapId): string
    {
        $combatLogPath = sprintf('%s/%s_events.txt', sys_get_temp_dir(), uniqid('combatlog_test_', true));

        file_put_contents(
            $combatLogPath,
            sprintf("5/15 21:20:10.941  ZONE_CHANGE,%d,\"Test Dungeon\",14\n", $mapId),
        );

        return $combatLogPath;
    }

    private function createDungeonWithoutNpcs(int $mapId, string $key): Dungeon
    {
        $this->assertSame(
            0,
            Dungeon::query()->where('map_id', $mapId)->count(),
            sprintf('Map id %d must not be used by a seeded dungeon for this test to be meaningful.', $mapId),
        );

        /** @var Expansion $expansion */
        $expansion = Expansion::query()->firstOrFail();

        return Dungeon::create([
            'expansion_id' => $expansion->id,
            'active'       => false,
            'zone_id'      => $mapId,
            'map_id'       => $mapId,
            'name'         => 'Test Dungeon Without Npcs',
            'key'          => $key,
            'slug'         => $key,
        ]);
    }

    /**
     * The dungeon's floors are validated for their ingame bounding boxes before its NPCs are ever looked
     * at, so this floor must be fully configured or the tests pass for the wrong reason.
     */
    private function createConfiguredDefaultFloor(Dungeon $dungeon): Floor
    {
        return FloorFactory::new()->create([
            'dungeon_id' => $dungeon->id,
            'index'      => 1,
            'name'       => 'Test Floor',
            'default'    => true,
        ]);
    }

    /**
     * Dungeon::boot() blocks `deleting` outright, so a test dungeon can only be cleaned up through the
     * query builder - which fires no model events, hence the manual cache flush.
     */
    private function deleteDungeon(?Dungeon $dungeon): void
    {
        if ($dungeon === null) {
            return;
        }

        Dungeon::query()->where('id', $dungeon->id)->delete();

        new Dungeon()->flushCache();
    }
}
