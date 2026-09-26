<?php

namespace Tests\Fixtures\Traits;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Service\WagoTools\GameLocale;
use Tests\TestCase;

/**
 * Places the DB2 CSVs of one dungeon's challenge mode scenario in the download cache, so the enemy forces
 * service reads the files it would have downloaded without ever reaching wago.tools. Generate them from the
 * dungeon's own seeded enemy forces ({@see getDungeonEnemyForces()}) and "identical" stays true no matter
 * what the mapping is retuned to.
 *
 * Call {@see removeDb2Tables()} in tearDown().
 *
 * @mixin TestCase
 */
trait WritesEnemyForcesDb2Tables
{
    private const string DUNGEON_KEY = 'den_of_nalorakk';

    private const int SCENARIO_ID = 999001;

    private const int ROOT_CRITERIA_TREE_ID = 999100;

    private const int FORCES_CRITERIA_TREE_ID = 999101;

    private const int SECOND_FORCES_CRITERIA_TREE_ID = 999102;

    private const int BOSS_CRITERIA_ID = 999500;

    private const int DUNGEON_ENCOUNTER_ID = 999900;

    /** The client names the challenge mode and its scenario the same, most of the time. */
    private const string SCENARIO_NAME = 'A dungeon';

    private const int SHARED_MAP_SCENARIO_ID = 999002;

    private const int SHARED_MAP_ROOT_CRITERIA_TREE_ID = 999200;

    private const int SHARED_MAP_FORCES_CRITERIA_TREE_ID = 999201;

    /** The build directory the tables are written to - one per test class, so parallel runs do not collide. */
    abstract protected function getDb2Build(): string;

    /** @return array{0: Dungeon, 1: MappingVersion, 2: array<int, int>} */
    private function getDungeonEnemyForces(): array
    {
        $dungeon = Dungeon::firstWhere('key', self::DUNGEON_KEY);
        $this->assertNotNull($dungeon, sprintf('The seeded database has no %s', self::DUNGEON_KEY));

        $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion(
            GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL),
        );
        $this->assertNotNull($mappingVersion);

        // Only the NPCs this mapping version actually places an enemy of - the ones it does not are what
        // the "unmapped" tests cover
        $mappedNpcIds = Enemy::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereNotNull('npc_id')
            ->distinct()
            ->pluck('npc_id')
            ->all();

        $ourEnemyForcesByNpcId = NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereIn('npc_id', $mappedNpcIds)
            ->pluck('enemy_forces', 'npc_id')
            ->map(static fn(int $enemyForces): int => $enemyForces)
            ->all();

        $this->assertNotEmpty($ourEnemyForcesByNpcId, sprintf('%s has no enemy forces to compare', self::DUNGEON_KEY));

        return [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId];
    }

    /**
     * The rows the game client would ship for one dungeon's challenge mode scenario.
     *
     * @param array<int, int>                                                        $enemyForcesByNpcId
     * @param array<int, array{criteriaId: int, type: int, asset: int, amount: int}> $nonCreatureCriteria
     */
    private function writeDb2Tables(
        Dungeon $dungeon,
        int     $enemyForcesRequired,
        array   $enemyForcesByNpcId,
        array   $nonCreatureCriteria = [],
        bool    $withSecondForcesNode = false,
        ?int    $dungeonEncounterMapId = null,
        ?string $sharedMapScenarioName = null,
        bool    $sharedMapScenarioIsAmbiguous = false,
    ): void {
        $criteriaTreeRows = [
            sprintf('%d,0,"12.1 Dungeon (Challenge)",0,4,0,0', self::ROOT_CRITERIA_TREE_ID),
            sprintf('%d,%d,"Defeat the boss",1,0,%d,0', self::ROOT_CRITERIA_TREE_ID + 10, self::ROOT_CRITERIA_TREE_ID, self::BOSS_CRITERIA_ID),
            sprintf('%d,%d,"Enemy Forces",%d,9,0,1', self::FORCES_CRITERIA_TREE_ID, self::ROOT_CRITERIA_TREE_ID, $enemyForcesRequired),
        ];
        $criteriaRows = [
            sprintf('%d,165,%d,0', self::BOSS_CRITERIA_ID, self::DUNGEON_ENCOUNTER_ID),
        ];

        if ($withSecondForcesNode) {
            $criteriaTreeRows[] = sprintf(
                '%d,%d,"Enemy Forces",%d,9,0,2',
                self::SECOND_FORCES_CRITERIA_TREE_ID,
                self::ROOT_CRITERIA_TREE_ID,
                (int)round($enemyForcesRequired * 1.2),
            );
        }

        $criteriaId = 999600;
        foreach ($enemyForcesByNpcId as $npcId => $enemyForces) {
            $criteriaTreeRows[] = sprintf('%d,%d,"",%d,0,%d,0', ++$criteriaId + 1000, self::FORCES_CRITERIA_TREE_ID, $enemyForces, $criteriaId);
            $criteriaRows[]     = sprintf('%d,0,%d,0', $criteriaId, $npcId);
        }

        foreach ($nonCreatureCriteria as $index => $criteria) {
            $criteriaTreeRows[] = sprintf('%d,%d,"",%d,0,%d,0', 999800 + $index, self::FORCES_CRITERIA_TREE_ID, $criteria['amount'], $criteria['criteriaId']);
            $criteriaRows[]     = sprintf('%d,%d,%d,0', $criteria['criteriaId'], $criteria['type'], $criteria['asset']);
        }

        $scenarioRows = [sprintf('%d,"%s",1,0', self::SCENARIO_ID, self::SCENARIO_NAME)];
        $stepRows     = [sprintf('%d,%d,%d', self::SCENARIO_ID + 100, self::SCENARIO_ID, self::ROOT_CRITERIA_TREE_ID)];

        // A second dungeon on the same map - the other wing of a split dungeon, or the retired scenario of
        // a reworked one. Its bosses are the same encounter, so only its name tells it apart.
        if ($sharedMapScenarioName !== null) {
            $scenarioRows[] = sprintf('%d,"%s",1,0', self::SHARED_MAP_SCENARIO_ID, $sharedMapScenarioName);
            $stepRows[]     = sprintf('%d,%d,%d', self::SHARED_MAP_SCENARIO_ID + 100, self::SHARED_MAP_SCENARIO_ID, self::SHARED_MAP_ROOT_CRITERIA_TREE_ID);

            $criteriaTreeRows[] = sprintf('%d,0,"Another dungeon (Challenge)",0,4,0,0', self::SHARED_MAP_ROOT_CRITERIA_TREE_ID);
            $criteriaTreeRows[] = sprintf('%d,%d,"Defeat the boss",1,0,%d,0', self::SHARED_MAP_ROOT_CRITERIA_TREE_ID + 10, self::SHARED_MAP_ROOT_CRITERIA_TREE_ID, self::BOSS_CRITERIA_ID);
            $criteriaTreeRows[] = sprintf('%d,%d,"Enemy Forces",%d,9,0,1', self::SHARED_MAP_FORCES_CRITERIA_TREE_ID, self::SHARED_MAP_ROOT_CRITERIA_TREE_ID, $enemyForcesRequired + 100);

            if ($sharedMapScenarioIsAmbiguous) {
                $criteriaTreeRows[] = sprintf('%d,%d,"Enemy Forces",%d,9,0,2', self::SHARED_MAP_FORCES_CRITERIA_TREE_ID + 1, self::SHARED_MAP_ROOT_CRITERIA_TREE_ID, $enemyForcesRequired + 200);
            }
        }

        $this->writeDb2Table('MapChallengeMode', 'ID,Name_lang,MapID', [
            sprintf('%d,"%s",%d', $dungeon->challenge_mode_id, self::SCENARIO_NAME, $dungeon->map_id),
        ]);
        $this->writeDb2Table('Scenario', 'ID,Name_lang,Type,Flags', $scenarioRows);
        $this->writeDb2Table('ScenarioStep', 'ID,ScenarioID,CriteriatreeID', $stepRows);
        $this->writeDb2Table('CriteriaTree', 'ID,Parent,Description_lang,Amount,Operator,CriteriaID,OrderIndex', $criteriaTreeRows);
        $this->writeDb2Table('Criteria', 'ID,Type,Asset,Modifier_tree_ID', $criteriaRows);
        $this->writeDb2Table('DungeonEncounter', 'ID,MapID', [
            sprintf('%d,%d', self::DUNGEON_ENCOUNTER_ID, $dungeonEncounterMapId ?? $dungeon->map_id),
        ]);
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

        $buildDirectory = storage_path(sprintf('app/db2/%s', $this->getDb2Build()));

        if (is_dir($buildDirectory)) {
            rmdir($buildDirectory);
        }
    }

    /** The cache is per locale; these tables carry no `_lang` column, so they are read as English. */
    private function getDb2Directory(): string
    {
        return storage_path(sprintf('app/db2/%s/%s', $this->getDb2Build(), GameLocale::English->value));
    }
}
