<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesNpclessCombatLogDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * #4354: the command reported "- Created mapping version 3 (Black Temple, 999, 0 enemies)" as a success
 * when the dungeon had no NPCs attached and every enemy in the log was therefore skipped.
 */
#[Group('CombatLog')]
#[Group('MappingVersion')]
final class CreateMappingVersionTest extends PublicTestCase
{
    use CreatesNpclessCombatLogDungeon;

    /** A map id no seeded dungeon uses, so the ZONE_CHANGE in the synthetic log resolves to the test dungeon. */
    private const int TEST_MAP_ID = 987655;

    #[Test]
    public function handle_givenDungeonWithoutNpcs_printsAnActionableErrorAndImportsNothing(): void
    {
        // Arrange
        $combatLogPath = $this->createZoneChangeCombatLog(self::TEST_MAP_ID);

        /** @var Dungeon|null $dungeon */
        $dungeon = null;
        /** @var Floor|null $floor */
        $floor = null;

        try {
            $dungeon = $this->createDungeonWithoutNpcs(self::TEST_MAP_ID, 'test_dungeon_without_npcs_command');
            $floor   = $this->createConfiguredDefaultFloor($dungeon);

            // Act
            $exitCode = Artisan::call('combatlog:createmappingversion', [
                'filePath'    => $combatLogPath,
                'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
            ]);
            $output = Artisan::output();

            // Assert
            $this->assertSame(0, $exitCode, $output);
            $this->assertStringContainsString('has no NPCs attached to it', $output);
            $this->assertStringContainsString('combatlog:extractdata', $output);
            $this->assertStringNotContainsString('0 enemies', $output);
            $this->assertSame(0, MappingVersion::query()->where('dungeon_id', $dungeon->id)->count());
        } finally {
            $floor?->delete();
            $this->deleteDungeon($dungeon);
            unlink($combatLogPath);
        }
    }

    #[Test]
    public function handle_givenNoneOfTheLogsNpcsBelongToTheDungeon_printsAnErrorRatherThanSuccess(): void
    {
        // Arrange
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
            $dungeon = $this->createDungeonWithoutNpcs(self::TEST_MAP_ID, 'test_dungeon_without_npcs_command');
            $floor   = $this->createConfiguredDefaultFloor($dungeon);

            /** @var Npc $npc */
            $npc = Npc::query()->firstOrFail();

            // The dungeon has an NPC, so the import runs - but the log holds no enemies of its own, which is
            // the partial version of the same trap: a mapping version is created with nothing in it.
            $npcDungeon = NpcDungeon::create([
                'npc_id'     => $npc->id,
                'dungeon_id' => $dungeon->id,
            ]);

            // Act
            $exitCode = Artisan::call('combatlog:createmappingversion', [
                'filePath'    => $combatLogPath,
                'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
            ]);
            $output = Artisan::output();

            // Assert
            $mappingVersion = MappingVersion::query()->where('dungeon_id', $dungeon->id)->first();

            $this->assertSame(0, $exitCode, $output);
            $this->assertNotNull($mappingVersion);
            $this->assertStringContainsString('0 enemies', $output);
            $this->assertStringContainsString('No enemies were imported!', $output);
        } finally {
            $mappingVersion?->delete();
            $npcDungeon?->delete();
            $floor?->delete();
            $this->deleteDungeon($dungeon);
            unlink($combatLogPath);
        }
    }

    #[Test]
    public function handle_givenAnExistingMappingVersionThatAlreadyHasEnemies_stillReportsAnImportOfNothingAsAnError(): void
    {
        // Arrange
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
            $dungeon = $this->createDungeonWithoutNpcs(self::TEST_MAP_ID, 'test_dungeon_without_npcs_command');
            $floor   = $this->createConfiguredDefaultFloor($dungeon);

            /** @var Npc $npc */
            $npc = Npc::query()->firstOrFail();

            $npcDungeon = NpcDungeon::create([
                'npc_id'     => $npc->id,
                'dungeon_id' => $dungeon->id,
            ]);

            /** @var GameVersion $gameVersion */
            $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

            $mappingVersion = MappingVersion::create([
                'dungeon_id'            => $dungeon->id,
                'game_version_id'       => $gameVersion->id,
                'version'               => 1,
                'enemy_forces_required' => 0,
                'timer_max_seconds'     => 0,
            ]);

            // Enemies are appended to a mapping version, so the ones it already holds must not be mistaken
            // for enemies that this combat log contributed - it contributes none.
            Enemy::create([
                'floor_id'           => $floor->id,
                'mapping_version_id' => $mappingVersion->id,
                'npc_id'             => $npc->id,
                'lat'                => -10,
                'lng'                => 10,
                'required'           => 0,
                'skippable'          => 0,
            ]);

            // Act
            $exitCode = Artisan::call('combatlog:createmappingversion', [
                'filePath'         => $combatLogPath,
                'gameVersion'      => GameVersion::GAME_VERSION_RETAIL,
                '--mappingVersion' => $mappingVersion->id,
            ]);
            $output = Artisan::output();

            // Assert
            $this->assertSame(0, $exitCode, $output);
            $this->assertStringContainsString('1 enemies', $output);
            $this->assertStringContainsString('No enemies were imported!', $output);
        } finally {
            $mappingVersion?->delete();
            $npcDungeon?->delete();
            $floor?->delete();
            $this->deleteDungeon($dungeon);
            unlink($combatLogPath);
        }
    }
}
