<?php

namespace Tests\Feature\Console\Commands\WagoTools;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Drives the command against DB2 CSVs placed in the download cache, so it reads the files it would have
 * downloaded without ever reaching wago.tools.
 */
#[Group('EnemyForces')]
final class DiffEnemyForcesTest extends PublicTestCase
{
    private const string BUILD = '0.0.0.00003';

    private const string DUNGEON_KEY = 'den_of_nalorakk';

    private const int SCENARIO_ID = 999001;

    private const int ROOT_CRITERIA_TREE_ID = 999100;

    private const int FORCES_CRITERIA_TREE_ID = 999101;

    private const int BOSS_CRITERIA_ID = 999500;

    private const int DUNGEON_ENCOUNTER_ID = 999900;

    #[\Override]
    protected function tearDown(): void
    {
        $this->removeDb2Tables();

        parent::tearDown();
    }

    #[Test]
    public function handle_givenTheEnemyForcesWeAlreadyHave_reportsThemAsIdentical(): void
    {
        // Arrange
        $dungeon = $this->writeDb2Tables();

        // Act & Assert
        $this->artisan('wagotools:diffenemyforces', ['--build' => self::BUILD, '--dungeon' => self::DUNGEON_KEY])
            ->expectsOutputToContain(sprintf('(scenario %d, criteria tree %d', self::SCENARIO_ID, self::FORCES_CRITERIA_TREE_ID))
            ->expectsOutputToContain('All 1 resolved dungeons match this build.')
            ->assertSuccessful();

        $this->assertSame(self::DUNGEON_KEY, $dungeon->key);
    }

    #[Test]
    public function handle_givenABuildResolvingNoneOfOurDungeons_fails(): void
    {
        // Arrange - a build we read wrong must not be able to report that everything matches
        $dungeon = Dungeon::firstWhere('key', self::DUNGEON_KEY);
        $this->assertNotNull($dungeon);

        $this->writeDb2Tables(dungeonEncounterMapId: $dungeon->map_id + 1);

        // Act & Assert
        $this->artisan('wagotools:diffenemyforces', ['--build' => self::BUILD, '--dungeon' => self::DUNGEON_KEY])
            ->expectsOutputToContain('Not one dungeon could be resolved in this build')
            ->assertFailed();
    }

    #[Test]
    public function handle_givenAnUnknownDungeon_fails(): void
    {
        // Act & Assert
        $this->artisan('wagotools:diffenemyforces', ['--build' => self::BUILD, '--dungeon' => 'not-a-dungeon'])
            ->expectsOutputToContain('Unknown dungeon not-a-dungeon')
            ->assertFailed();
    }

    #[Test]
    public function handle_givenAnUnknownGameVersion_fails(): void
    {
        // Act & Assert
        $this->artisan('wagotools:diffenemyforces', ['--build' => self::BUILD, '--gameVersion' => 'not-a-game-version'])
            ->expectsOutputToContain('Unknown game version not-a-game-version')
            ->assertFailed();
    }

    /** The rows the game client would ship for this dungeon's challenge mode scenario, as we have them. */
    private function writeDb2Tables(?int $dungeonEncounterMapId = null): Dungeon
    {
        $dungeon = Dungeon::firstWhere('key', self::DUNGEON_KEY);
        $this->assertNotNull($dungeon, sprintf('The seeded database has no %s', self::DUNGEON_KEY));

        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion(
            GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL),
        );

        $criteriaTreeRows = [
            sprintf('%d,0,"12.1 Dungeon (Challenge)",0,4,0,0', self::ROOT_CRITERIA_TREE_ID),
            sprintf('%d,%d,"Defeat the boss",1,0,%d,0', self::ROOT_CRITERIA_TREE_ID + 10, self::ROOT_CRITERIA_TREE_ID, self::BOSS_CRITERIA_ID),
            sprintf('%d,%d,"Enemy Forces",%d,9,0,1', self::FORCES_CRITERIA_TREE_ID, self::ROOT_CRITERIA_TREE_ID, $mappingVersion->enemy_forces_required),
        ];
        $criteriaRows = [sprintf('%d,165,%d,0', self::BOSS_CRITERIA_ID, self::DUNGEON_ENCOUNTER_ID)];

        $criteriaId            = 999600;
        $ourEnemyForcesByNpcId = NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->pluck('enemy_forces', 'npc_id');

        foreach ($ourEnemyForcesByNpcId as $npcId => $enemyForces) {
            $criteriaTreeRows[] = sprintf('%d,%d,"",%d,0,%d,0', ++$criteriaId + 1000, self::FORCES_CRITERIA_TREE_ID, $enemyForces, $criteriaId);
            $criteriaRows[]     = sprintf('%d,0,%d,0', $criteriaId, $npcId);
        }

        $this->writeDb2Table('MapChallengeMode', 'ID,Name_lang,MapID', [
            sprintf('%d,"A dungeon",%d', $dungeon->challenge_mode_id, $dungeon->map_id),
        ]);
        $this->writeDb2Table('Scenario', 'ID,Name_lang,Type,Flags', [sprintf('%d,"A dungeon",1,0', self::SCENARIO_ID)]);
        $this->writeDb2Table('ScenarioStep', 'ID,ScenarioID,CriteriatreeID', [
            sprintf('%d,%d,%d', self::SCENARIO_ID + 100, self::SCENARIO_ID, self::ROOT_CRITERIA_TREE_ID),
        ]);
        $this->writeDb2Table('CriteriaTree', 'ID,Parent,Description_lang,Amount,Operator,CriteriaID,OrderIndex', $criteriaTreeRows);
        $this->writeDb2Table('Criteria', 'ID,Type,Asset,Modifier_tree_ID', $criteriaRows);
        $this->writeDb2Table('DungeonEncounter', 'ID,MapID', [
            sprintf('%d,%d', self::DUNGEON_ENCOUNTER_ID, $dungeonEncounterMapId ?? $dungeon->map_id),
        ]);

        return $dungeon;
    }

    /** @param array<int, string> $rows */
    private function writeDb2Table(string $table, string $header, array $rows): void
    {
        $directory = $this->getDb2Directory();

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(sprintf('%s/%s.csv', $directory, $table), implode("\n", [$header, ...$rows]));
    }

    private function removeDb2Tables(): void
    {
        foreach (glob(sprintf('%s/*.csv', $this->getDb2Directory())) ?: [] as $filePath) {
            unlink($filePath);
        }

        if (is_dir($this->getDb2Directory())) {
            rmdir($this->getDb2Directory());
        }
    }

    private function getDb2Directory(): string
    {
        return storage_path(sprintf('app/db2/%s', self::BUILD));
    }
}
