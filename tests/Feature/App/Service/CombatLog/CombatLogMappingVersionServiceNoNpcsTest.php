<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\Service\CombatLog\CombatLogMappingVersionServiceInterface;
use App\Service\CombatLog\Exceptions\DungeonHasNoNpcsException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesNpclessCombatLogDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * #4354: creating a mapping version from a combat log for a dungeon that has no NPCs attached to it
 * reported a successful import of 0 enemies - every creature in the log is matched against the dungeon's
 * NPCs (via the npc_dungeons pivot), so without them all of them are silently skipped. The missing step
 * is `combatlog:extractdata`, which creates those NPCs, and nothing said so.
 */
#[Group('MappingVersion')]
#[Group('CombatLog')]
final class CombatLogMappingVersionServiceNoNpcsTest extends PublicTestCase
{
    use CreatesNpclessCombatLogDungeon;

    /** A map id no seeded dungeon uses, so the ZONE_CHANGE in the synthetic log resolves to the test dungeon. */
    private const int TEST_MAP_ID = 987654;

    #[Test]
    public function createMappingVersionFromDungeonOrRaid_givenDungeonWithoutNpcs_throwsDungeonHasNoNpcsException(): void
    {
        // Arrange
        $service       = $this->app->make(CombatLogMappingVersionServiceInterface::class);
        $gameVersion   = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $combatLogPath = $this->createZoneChangeCombatLog(self::TEST_MAP_ID);

        /** @var Dungeon|null $dungeon */
        $dungeon = null;
        /** @var Floor|null $floor */
        $floor = null;

        try {
            $dungeon = $this->createDungeonWithoutNpcs(self::TEST_MAP_ID, 'test_dungeon_without_npcs_service');
            $floor   = $this->createConfiguredDefaultFloor($dungeon);

            // Act
            $caughtException = null;

            try {
                $service->createMappingVersionFromDungeonOrRaid($combatLogPath, $gameVersion);
            } catch (DungeonHasNoNpcsException $dungeonHasNoNpcsException) {
                $caughtException = $dungeonHasNoNpcsException;
            }

            // Assert
            $this->assertNotNull(
                $caughtException,
                'A dungeon without NPCs must abort the import instead of quietly creating a mapping version with 0 enemies.',
            );
            $this->assertStringContainsString(
                'combatlog:extractdata',
                $caughtException->getMessage(),
                'The error must name the command that creates the missing NPCs.',
            );
            $this->assertSame(
                0,
                MappingVersion::query()->where('dungeon_id', $dungeon->id)->count(),
                'The mapping version created up-front must be cleaned up when the import aborts, not left behind as an empty orphan.',
            );
        } finally {
            $floor?->delete();
            $this->deleteDungeon($dungeon);
            unlink($combatLogPath);
        }
    }

    #[Test]
    public function createMappingVersionFromDungeonOrRaid_givenNoEnemiesInTheLogAndEnemyConnections_createsAnEmptyMappingVersion(): void
    {
        // Arrange
        $service       = $this->app->make(CombatLogMappingVersionServiceInterface::class);
        $gameVersion   = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $combatLogPath = $this->createZoneChangeCombatLog(self::TEST_MAP_ID);

        /** @var Dungeon|null $dungeon */
        $dungeon = null;
        /** @var Floor|null $floor */
        $floor = null;
        /** @var NpcDungeon|null $npcDungeon */
        $npcDungeon = null;
        /** @var MappingVersion|null $mappingVersion */
        $mappingVersion = null;

        try {
            $dungeon = $this->createDungeonWithoutNpcs(self::TEST_MAP_ID, 'test_dungeon_without_npcs_service');
            $floor   = $this->createConfiguredDefaultFloor($dungeon);

            /** @var Npc $npc */
            $npc = Npc::query()->firstOrFail();

            // The dungeon now has an NPC, so the import proceeds - but the log contains no enemies at all,
            // which used to make the --enemyConnections gradient divide 100 by 0.
            $npcDungeon = NpcDungeon::create([
                'npc_id'     => $npc->id,
                'dungeon_id' => $dungeon->id,
            ]);

            // Act
            $mappingVersion = $service->createMappingVersionFromDungeonOrRaid(
                $combatLogPath,
                $gameVersion,
                null,
                true,
            );

            // Assert
            $this->assertNotNull($mappingVersion);
            $this->assertSame($dungeon->id, $mappingVersion->dungeon_id);
            $this->assertSame(0, $mappingVersion->enemies()->count());
        } finally {
            $mappingVersion?->delete();
            $npcDungeon?->delete();
            $floor?->delete();
            $this->deleteDungeon($dungeon);
            unlink($combatLogPath);
        }
    }
}
