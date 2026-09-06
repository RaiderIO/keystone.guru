<?php

namespace Tests\Feature\Service\EnemyForces;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Service\EnemyForces\EnemyForcesDb2ServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Runs the diff against DB2 CSVs placed in the download cache, so the service reads the files it would
 * have downloaded without ever reaching wago.tools. The tables are generated from the dungeon's own
 * seeded enemy forces, so "identical" stays true no matter what the mapping is retuned to.
 */
#[Group('EnemyForces')]
final class EnemyForcesDb2ServiceTest extends PublicTestCase
{
    private const string BUILD = '0.0.0.00002';

    private const string DUNGEON_KEY = 'den_of_nalorakk';

    /** An NPC no mapping version places an enemy of - the client's tree keeps retired affix creatures. */
    private const int UNMAPPED_NPC_ID = 999999801;

    private const int SCENARIO_ID = 999001;

    private const int ROOT_CRITERIA_TREE_ID = 999100;

    private const int FORCES_CRITERIA_TREE_ID = 999101;

    private const int SECOND_FORCES_CRITERIA_TREE_ID = 999102;

    private const int BOSS_CRITERIA_ID = 999500;

    private const int DUNGEON_ENCOUNTER_ID = 999900;

    #[\Override]
    protected function tearDown(): void
    {
        $this->removeDb2Tables();

        parent::tearDown();
    }

    #[Test]
    public function diffEnemyForces_givenTheEnemyForcesWeAlreadyHave_reportsTheDungeonAsIdentical(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables($dungeon, $mappingVersion->enemy_forces_required, $ourEnemyForcesByNpcId);

        // Act
        $report = $this->diffEnemyForces($dungeon);

        // Assert
        $dungeonDiff = $report->dungeonDiffs[$dungeon->id];

        $this->assertNull($dungeonDiff->unresolvedReason);
        $this->assertSame(self::SCENARIO_ID, $dungeonDiff->scenarioId);
        $this->assertSame(self::FORCES_CRITERIA_TREE_ID, $dungeonDiff->criteriaTreeId);
        $this->assertSame($mappingVersion->enemy_forces_required, $dungeonDiff->db2EnemyForcesRequired);
        $this->assertTrue($dungeonDiff->matches());
        $this->assertSame([], $report->getDivergingDungeonDiffs());
    }

    #[Test]
    public function diffEnemyForces_givenADifferentTotal_reportsTheDungeonAsDiverging(): void
    {
        // Arrange - this is the shape of a server side hotfix the live client has not caught up with
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables($dungeon, $mappingVersion->enemy_forces_required + 31, $ourEnemyForcesByNpcId);

        // Act
        $report = $this->diffEnemyForces($dungeon);

        // Assert
        $dungeonDiff = $report->dungeonDiffs[$dungeon->id];

        $this->assertSame($mappingVersion->enemy_forces_required + 31, $dungeonDiff->db2EnemyForcesRequired);
        $this->assertFalse($dungeonDiff->hasMatchingEnemyForcesRequired());
        $this->assertSame([], $dungeonDiff->getMismatchedNpcDiffs());
        $this->assertArrayHasKey($dungeon->id, $report->getDungeonDiffsWithDivergingEnemyForcesRequired());
    }

    #[Test]
    public function diffEnemyForces_givenADifferentAmountForAnNpc_reportsThatNpc(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $retunedNpcId          = (int)array_key_first($ourEnemyForcesByNpcId);
        $db2EnemyForcesByNpcId = $ourEnemyForcesByNpcId;
        $db2EnemyForcesByNpcId[$retunedNpcId] *= 2;

        $this->writeDb2Tables($dungeon, $mappingVersion->enemy_forces_required, $db2EnemyForcesByNpcId);

        // Act
        $report = $this->diffEnemyForces($dungeon);

        // Assert
        $dungeonDiff       = $report->dungeonDiffs[$dungeon->id];
        $mismatchedNpcDiff = $dungeonDiff->getMismatchedNpcDiffs();

        $this->assertTrue($dungeonDiff->hasMatchingEnemyForcesRequired());
        $this->assertSame([$retunedNpcId], array_keys($mismatchedNpcDiff));
        $this->assertSame($ourEnemyForcesByNpcId[$retunedNpcId] * 2, $mismatchedNpcDiff[$retunedNpcId]->db2EnemyForces);
        $this->assertSame($ourEnemyForcesByNpcId[$retunedNpcId], $mismatchedNpcDiff[$retunedNpcId]->ourEnemyForces);
    }

    #[Test]
    public function diffEnemyForces_givenAnNpcTheBuildDoesNotAwardForcesFor_reportsItWithoutADb2Amount(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $droppedNpcId          = (int)array_key_first($ourEnemyForcesByNpcId);
        $db2EnemyForcesByNpcId = $ourEnemyForcesByNpcId;
        unset($db2EnemyForcesByNpcId[$droppedNpcId]);

        $this->writeDb2Tables($dungeon, $mappingVersion->enemy_forces_required, $db2EnemyForcesByNpcId);

        // Act
        $report = $this->diffEnemyForces($dungeon);

        // Assert
        $mismatchedNpcDiff = $report->dungeonDiffs[$dungeon->id]->getMismatchedNpcDiffs();

        $this->assertSame([$droppedNpcId], array_keys($mismatchedNpcDiff));
        $this->assertNull($mismatchedNpcDiff[$droppedNpcId]->db2EnemyForces);
        $this->assertSame($ourEnemyForcesByNpcId[$droppedNpcId], $mismatchedNpcDiff[$droppedNpcId]->ourEnemyForces);
    }

    #[Test]
    public function diffEnemyForces_givenAnNpcTheMappingVersionHasNoEnemyOf_skipsItInsteadOfCallingItADifference(): void
    {
        // Arrange - King's Rest' tree still awards forces for nine retired seasonal affix creatures
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables(
            $dungeon,
            $mappingVersion->enemy_forces_required,
            $ourEnemyForcesByNpcId + [self::UNMAPPED_NPC_ID => 4],
        );

        // Act
        $report = $this->diffEnemyForces($dungeon);

        // Assert
        $dungeonDiff = $report->dungeonDiffs[$dungeon->id];

        $this->assertSame([self::UNMAPPED_NPC_ID => 4], $dungeonDiff->unmappedNpcEnemyForces);
        $this->assertArrayNotHasKey(self::UNMAPPED_NPC_ID, $dungeonDiff->npcDiffs);
        $this->assertTrue($dungeonDiff->matches());
    }

    #[Test]
    public function diffEnemyForces_givenACriteriaThatIsNotACreatureKill_reportsItWithoutAttributingItToAnNpc(): void
    {
        // Arrange - every Midnight forces node carries a handful of scenario game event criteria
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables(
            $dungeon,
            $mappingVersion->enemy_forces_required,
            $ourEnemyForcesByNpcId,
            nonCreatureCriteria: [['criteriaId' => 999700, 'type' => 92, 'asset' => 77283, 'amount' => 59]],
        );

        // Act
        $report = $this->diffEnemyForces($dungeon);

        // Assert
        $dungeonDiff         = $report->dungeonDiffs[$dungeon->id];
        $nonCreatureCriteria = $dungeonDiff->nonCreatureCriteria;

        $this->assertCount(1, $nonCreatureCriteria);
        $this->assertSame(92, $nonCreatureCriteria[0]->type);
        $this->assertSame(77283, $nonCreatureCriteria[0]->asset);
        $this->assertSame(59, $nonCreatureCriteria[0]->amount);
        $this->assertArrayNotHasKey(77283, $dungeonDiff->npcDiffs);
        $this->assertTrue($dungeonDiff->matches());
    }

    #[Test]
    public function diffEnemyForces_givenAScenarioWithTwoForcesNodes_leavesTheDungeonUnresolved(): void
    {
        // Arrange - a scenario with a normal and a teeming step says nothing about which one M+ runs
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables(
            $dungeon,
            $mappingVersion->enemy_forces_required,
            $ourEnemyForcesByNpcId,
            withSecondForcesNode: true,
        );

        // Act
        $report = $this->diffEnemyForces($dungeon);

        // Assert
        $dungeonDiff = $report->dungeonDiffs[$dungeon->id];

        $this->assertFalse($dungeonDiff->isResolved());
        $this->assertStringContainsString('2 enemy forces nodes', (string)$dungeonDiff->unresolvedReason);
        $this->assertSame([], $report->getResolvedDungeonDiffs());
    }

    #[Test]
    public function diffEnemyForces_givenAScenarioWhoseBossesAreOnAnotherMap_leavesTheDungeonUnresolved(): void
    {
        // Arrange - the boss encounters are the only thing tying a scenario to a dungeon, so a scenario
        // that names another map is not this dungeon's, however much its name looks like it
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables(
            $dungeon,
            $mappingVersion->enemy_forces_required,
            $ourEnemyForcesByNpcId,
            dungeonEncounterMapId: $dungeon->map_id + 1,
        );

        // Act
        $report = $this->diffEnemyForces($dungeon);

        // Assert
        $dungeonDiff = $report->dungeonDiffs[$dungeon->id];

        $this->assertFalse($dungeonDiff->isResolved());
        $this->assertStringContainsString(sprintf('no challenge mode enemy forces tree for map %d', $dungeon->map_id), (string)$dungeonDiff->unresolvedReason);
    }

    private function diffEnemyForces(Dungeon $dungeon): \App\Service\EnemyForces\Dtos\EnemyForcesDb2Report
    {
        /** @var EnemyForcesDb2ServiceInterface $enemyForcesDb2Service */
        $enemyForcesDb2Service = app(EnemyForcesDb2ServiceInterface::class);

        $report = $enemyForcesDb2Service->diffEnemyForces(
            'wow',
            GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL),
            self::BUILD,
            $dungeon,
        );

        $this->assertNotNull($report);

        return $report;
    }

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
        // the "unmapped" test covers
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

        $this->writeDb2Table('MapChallengeMode', 'ID,Name_lang,MapID', [
            sprintf('%d,"A dungeon",%d', $dungeon->challenge_mode_id, $dungeon->map_id),
        ]);
        $this->writeDb2Table('Scenario', 'ID,Name_lang,Type,Flags', [
            sprintf('%d,"A dungeon",1,0', self::SCENARIO_ID),
        ]);
        $this->writeDb2Table('ScenarioStep', 'ID,ScenarioID,CriteriatreeID', [
            sprintf('%d,%d,%d', self::SCENARIO_ID + 100, self::SCENARIO_ID, self::ROOT_CRITERIA_TREE_ID),
        ]);
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
    }

    private function getDb2Directory(): string
    {
        return storage_path(sprintf('app/db2/%s', self::BUILD));
    }
}
