<?php

namespace Tests\Feature\Service\EnemyForces;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Service\EnemyForces\Dtos\DungeonEnemyForcesDiff;
use App\Service\EnemyForces\Dtos\EnemyForcesDb2Report;
use App\Service\EnemyForces\EnemyForcesDb2ServiceInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\Fixtures\Traits\WritesEnemyForcesDb2Tables;
use Tests\TestCases\PublicTestCase;

#[Group('EnemyForces')]
final class EnemyForcesDb2ServiceTest extends PublicTestCase
{
    use CreatesDungeon;
    use WritesEnemyForcesDb2Tables;

    private const string BUILD = '0.0.0.00002';

    /** An NPC no mapping version places an enemy of - the client's tree keeps retired affix creatures. */
    private const int UNMAPPED_NPC_ID = 999999801;

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

    #[Test]
    public function diffEnemyForces_givenAnotherDungeonOnTheSameMap_picksTheScenarioNamedAfterTheChallengeMode(): void
    {
        // Arrange - both Dawn of the Infinite wings are map 2579, with a scenario each
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables(
            $dungeon,
            $mappingVersion->enemy_forces_required,
            $ourEnemyForcesByNpcId,
            sharedMapScenarioName: 'The other wing',
        );

        // Act
        $report = $this->diffEnemyForces($dungeon);

        // Assert - the other wing requires 100 more, so picking it would have read as a moved total
        $dungeonDiff = $report->dungeonDiffs[$dungeon->id];

        $this->assertSame(self::SCENARIO_ID, $dungeonDiff->scenarioId);
        $this->assertSame($mappingVersion->enemy_forces_required, $dungeonDiff->db2EnemyForcesRequired);
        $this->assertTrue($dungeonDiff->matches());
    }

    #[Test]
    public function diffEnemyForces_givenAnotherDungeonOnTheSameMapTheClientNamesDifferently_leavesTheDungeonUnresolved(): void
    {
        // Arrange - the client calls Operation Mechagon: Junkyard "Mechagon Junkyard", so neither of the
        // two scenarios on its map is named after the challenge mode
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables(
            $dungeon,
            $mappingVersion->enemy_forces_required,
            $ourEnemyForcesByNpcId,
            sharedMapScenarioName: 'The other wing',
        );
        $this->writeDb2Table('MapChallengeMode', 'ID,Name_lang,MapID', [
            sprintf('%d,"A dungeon spelled another way",%d', $dungeon->challenge_mode_id, $dungeon->map_id),
        ]);

        // Act
        $report = $this->diffEnemyForces($dungeon);

        // Assert
        $dungeonDiff = $report->dungeonDiffs[$dungeon->id];

        $this->assertFalse($dungeonDiff->isResolved());
        $this->assertStringContainsString('carries 2 challenge mode scenarios', (string)$dungeonDiff->unresolvedReason);
    }

    #[Test]
    public function diffEnemyForces_givenARetiredScenarioOnTheSameMap_picksTheOneWithASingleForcesNode(): void
    {
        // Arrange - Siege of Boralus keeps its retired scenario 1685, whose steps left it four forces nodes,
        // alongside the reworked 2486. Both are named the same, so the name cannot tell them apart.
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables(
            $dungeon,
            $mappingVersion->enemy_forces_required,
            $ourEnemyForcesByNpcId,
            sharedMapScenarioName: self::SCENARIO_NAME,
            sharedMapScenarioIsAmbiguous: true,
        );

        // Act
        $report = $this->diffEnemyForces($dungeon);

        // Assert
        $dungeonDiff = $report->dungeonDiffs[$dungeon->id];

        $this->assertSame(self::SCENARIO_ID, $dungeonDiff->scenarioId);
        $this->assertTrue($dungeonDiff->matches());
    }

    #[Test]
    public function diffEnemyForces_givenAnOlderDungeonOnTheSameChallengeMode_leavesTheOlderOneUnresolved(): void
    {
        // Arrange - Algeth'ar Academy is challenge mode 402 in Dragonflight and in Midnight, and the build only
        // holds the Midnight tuning
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $olderExpansion = Expansion::query()
            ->where('released_at', '<', $dungeon->expansion->released_at)
            ->orderByDesc('released_at')
            ->firstOrFail();
        $olderDungeon = $this->createDungeon([
            'challenge_mode_id' => $dungeon->challenge_mode_id,
            'expansion_id'      => $olderExpansion->id,
        ]);

        $this->writeDb2Tables($dungeon, $mappingVersion->enemy_forces_required, $ourEnemyForcesByNpcId);

        // Act
        $olderDungeonDiff = $this->diffEnemyForces($olderDungeon)->dungeonDiffs[$olderDungeon->id];
        $dungeonDiff      = $this->diffEnemyForces($dungeon)->dungeonDiffs[$dungeon->id];

        // Assert
        $this->assertFalse($olderDungeonDiff->isResolved());
        $this->assertStringContainsString(
            sprintf('Shares challenge mode %d with %s', $dungeon->challenge_mode_id, __($dungeon->name)),
            (string)$olderDungeonDiff->unresolvedReason,
        );
        $this->assertTrue($dungeonDiff->isResolved());
        $this->assertTrue($dungeonDiff->matches());
    }

    #[Test]
    public function writeEnemyForces_givenAMovedTotalAndARetunedNpc_writesBothOntoTheMappingVersion(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $retunedNpcId          = (int)array_key_first($ourEnemyForcesByNpcId);
        $untouchedNpcId        = (int)array_key_last($ourEnemyForcesByNpcId);
        $db2EnemyForcesByNpcId = $ourEnemyForcesByNpcId;
        $db2EnemyForcesByNpcId[$retunedNpcId] *= 2;

        $this->writeDb2Tables($dungeon, $mappingVersion->enemy_forces_required + 31, $db2EnemyForcesByNpcId);

        $mappingVersionBefore     = MappingVersion::findOrFail($mappingVersion->id);
        $retunedNpcEnemyForcesRow = $this->getNpcEnemyForcesRow($mappingVersion, $retunedNpcId);
        $this->assertNotNull($retunedNpcEnemyForcesRow);

        try {
            // Act
            $this->writeEnemyForces($dungeon);

            // Assert
            $mappingVersionAfter     = MappingVersion::findOrFail($mappingVersion->id);
            $retunedNpcEnemyForces   = $this->getNpcEnemyForcesRow($mappingVersion, $retunedNpcId);
            $untouchedNpcEnemyForces = $this->getNpcEnemyForcesRow($mappingVersion, $untouchedNpcId);

            $this->assertSame($mappingVersion->enemy_forces_required + 31, $mappingVersionAfter->enemy_forces_required);
            $this->assertSame($mappingVersionBefore->enemy_forces_required_teeming, $mappingVersionAfter->enemy_forces_required_teeming);
            $this->assertSame($mappingVersionBefore->enemy_forces_shrouded, $mappingVersionAfter->enemy_forces_shrouded);
            $this->assertSame($mappingVersionBefore->enemy_forces_shrouded_zul_gamux, $mappingVersionAfter->enemy_forces_shrouded_zul_gamux);
            $this->assertNotNull($retunedNpcEnemyForces);
            $this->assertSame($ourEnemyForcesByNpcId[$retunedNpcId] * 2, $retunedNpcEnemyForces->enemy_forces);
            $this->assertSame($retunedNpcEnemyForcesRow->enemy_forces_teeming, $retunedNpcEnemyForces->enemy_forces_teeming);
            $this->assertSame($ourEnemyForcesByNpcId[$untouchedNpcId], $untouchedNpcEnemyForces?->enemy_forces);
        } finally {
            $this->restoreEnemyForces($mappingVersion, $ourEnemyForcesByNpcId);
        }
    }

    #[Test]
    public function writeEnemyForces_givenAMappedNpcWeHoldNoForcesFor_createsItsRow(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $npcIdWithoutEnemyForces = Enemy::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereNotNull('npc_id')
            ->whereNotIn('npc_id', NpcEnemyForces::query()->where('mapping_version_id', $mappingVersion->id)->select('npc_id'))
            ->value('npc_id');
        $this->assertNotNull($npcIdWithoutEnemyForces, sprintf('Every enemy of %s is worth enemy forces', self::DUNGEON_KEY));

        $this->writeDb2Tables(
            $dungeon,
            $mappingVersion->enemy_forces_required,
            $ourEnemyForcesByNpcId + [$npcIdWithoutEnemyForces => 12],
        );

        try {
            // Act
            $this->writeEnemyForces($dungeon);

            // Assert
            $createdNpcEnemyForces = $this->getNpcEnemyForcesRow($mappingVersion, $npcIdWithoutEnemyForces);

            $this->assertNotNull($createdNpcEnemyForces);
            $this->assertSame(12, $createdNpcEnemyForces->enemy_forces);
            $this->assertNull($createdNpcEnemyForces->enemy_forces_teeming);
        } finally {
            NpcEnemyForces::query()
                ->where('mapping_version_id', $mappingVersion->id)
                ->where('npc_id', $npcIdWithoutEnemyForces)
                ->delete();
        }
    }

    #[Test]
    public function writeEnemyForces_givenAnNpcTheBuildAwardsNothingFor_keepsOurRow(): void
    {
        // Arrange - mostly Shrouded affix creatures, whose forces come from the mapping version instead
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $droppedNpcId          = (int)array_key_first($ourEnemyForcesByNpcId);
        $db2EnemyForcesByNpcId = $ourEnemyForcesByNpcId;
        unset($db2EnemyForcesByNpcId[$droppedNpcId]);

        $this->writeDb2Tables($dungeon, $mappingVersion->enemy_forces_required + 31, $db2EnemyForcesByNpcId);

        try {
            // Act
            $dungeonDiff = $this->writeEnemyForces($dungeon);

            // Assert
            $this->assertSame([$droppedNpcId], array_keys($dungeonDiff->getNpcDiffsOnlyWeHold()));
            $this->assertSame($ourEnemyForcesByNpcId[$droppedNpcId], $this->getNpcEnemyForcesRow($mappingVersion, $droppedNpcId)?->enemy_forces);
        } finally {
            $this->restoreEnemyForces($mappingVersion, $ourEnemyForcesByNpcId);
        }
    }

    #[Test]
    public function writeEnemyForces_givenAnUnmappedNpcAndANonCreatureCriteria_writesNeither(): void
    {
        // Arrange
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables(
            $dungeon,
            $mappingVersion->enemy_forces_required + 31,
            $ourEnemyForcesByNpcId + [self::UNMAPPED_NPC_ID => 4],
            nonCreatureCriteria: [['criteriaId' => 999700, 'type' => 92, 'asset' => 77283, 'amount' => 59]],
        );

        $npcEnemyForcesCountBefore = NpcEnemyForces::query()->where('mapping_version_id', $mappingVersion->id)->count();

        try {
            // Act
            $this->writeEnemyForces($dungeon);

            // Assert
            $this->assertSame($npcEnemyForcesCountBefore, NpcEnemyForces::query()->where('mapping_version_id', $mappingVersion->id)->count());
            $this->assertNull($this->getNpcEnemyForcesRow($mappingVersion, self::UNMAPPED_NPC_ID));
            $this->assertNull($this->getNpcEnemyForcesRow($mappingVersion, 77283));
        } finally {
            $this->restoreEnemyForces($mappingVersion, $ourEnemyForcesByNpcId);
        }
    }

    #[Test]
    public function writeEnemyForces_givenAnUnresolvedDiff_throwsInvalidArgumentException(): void
    {
        // Arrange - a scenario with two forces nodes says nothing about which one M+ runs
        [$dungeon, $mappingVersion, $ourEnemyForcesByNpcId] = $this->getDungeonEnemyForces();

        $this->writeDb2Tables(
            $dungeon,
            $mappingVersion->enemy_forces_required + 31,
            $ourEnemyForcesByNpcId,
            withSecondForcesNode: true,
        );

        $dungeonDiff = $this->diffEnemyForces($dungeon)->dungeonDiffs[$dungeon->id];

        // Act
        $exception = null;

        try {
            app(EnemyForcesDb2ServiceInterface::class)->writeEnemyForces($dungeonDiff);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $exception = $invalidArgumentException;
        } finally {
            $this->restoreEnemyForces($mappingVersion, $ourEnemyForcesByNpcId);
        }

        // Assert
        $this->assertNotNull($exception);
        $this->assertStringContainsString('2 enemy forces nodes', $exception->getMessage());
        $this->assertSame($mappingVersion->enemy_forces_required, MappingVersion::findOrFail($mappingVersion->id)->enemy_forces_required);
    }

    protected function getDb2Build(): string
    {
        return self::BUILD;
    }

    private function writeEnemyForces(Dungeon $dungeon): DungeonEnemyForcesDiff
    {
        $dungeonDiff = $this->diffEnemyForces($dungeon)->dungeonDiffs[$dungeon->id];
        $this->assertTrue($dungeonDiff->isResolved(), (string)$dungeonDiff->unresolvedReason);

        app(EnemyForcesDb2ServiceInterface::class)->writeEnemyForces($dungeonDiff);

        return $dungeonDiff;
    }

    private function getNpcEnemyForcesRow(MappingVersion $mappingVersion, int $npcId): ?NpcEnemyForces
    {
        return NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->where('npc_id', $npcId)
            ->first();
    }

    /**
     * Puts the seeded total and per NPC amounts back.
     *
     * @param array<int, int> $ourEnemyForcesByNpcId
     */
    private function restoreEnemyForces(MappingVersion $mappingVersion, array $ourEnemyForcesByNpcId): void
    {
        MappingVersion::query()
            ->whereKey($mappingVersion->id)
            ->update([
                'enemy_forces_required' => $mappingVersion->enemy_forces_required,
                'updated_at'            => $mappingVersion->updated_at,
            ]);

        foreach ($ourEnemyForcesByNpcId as $npcId => $enemyForces) {
            NpcEnemyForces::query()
                ->where('mapping_version_id', $mappingVersion->id)
                ->where('npc_id', $npcId)
                ->update(['enemy_forces' => $enemyForces]);
        }
    }

    private function diffEnemyForces(Dungeon $dungeon): EnemyForcesDb2Report
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
}
