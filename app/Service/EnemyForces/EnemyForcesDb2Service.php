<?php

namespace App\Service\EnemyForces;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcEnemyForces;
use App\Service\EnemyForces\Dtos\Db2CriteriaRow;
use App\Service\EnemyForces\Dtos\Db2EnemyForcesTree;
use App\Service\EnemyForces\Dtos\DungeonEnemyForcesDiff;
use App\Service\EnemyForces\Dtos\EnemyForcesDb2Report;
use App\Service\EnemyForces\Dtos\NpcEnemyForcesDiff;
use App\Service\EnemyForces\Logging\EnemyForcesDb2ServiceLoggingInterface;
use App\Service\WagoTools\WagoToolsServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class EnemyForcesDb2Service implements EnemyForcesDb2ServiceInterface
{
    /** `Scenario.Type` of the challenge mode (M+) run of a dungeon; type 3 is the same dungeon's normal/heroic run. */
    private const int SCENARIO_TYPE_CHALLENGE_MODE = 1;

    /** `CriteriaTree.Operator` of the node that sums its children into a progress bar - the enemy forces node. */
    private const int CRITERIA_TREE_OPERATOR_SUM_CHILDREN = 9;

    /** `Criteria.Type` of "kill creature", whose `Asset` is the creature id. */
    private const int CRITERIA_TYPE_KILL_CREATURE = 0;

    /** `Criteria.Type` of "defeat dungeon encounter", whose `Asset` is a `DungeonEncounter` id. */
    private const int CRITERIA_TYPE_DUNGEON_ENCOUNTER = 165;

    public function __construct(
        private readonly WagoToolsServiceInterface             $wagoToolsService,
        private readonly EnemyForcesDb2ServiceLoggingInterface $log,
    ) {
    }

    public function diffEnemyForces(
        string      $product,
        GameVersion $gameVersion,
        ?string     $build = null,
        ?Dungeon    $dungeon = null,
    ): ?EnemyForcesDb2Report {
        $build ??= $this->wagoToolsService->getLatestBuild($product);

        if ($build === null) {
            $this->log->diffEnemyForcesUnknownBuild($product);

            return null;
        }

        try {
            $this->log->diffEnemyForcesStart($product, $build, $gameVersion->id);

            $dungeons = $this->getDungeons($dungeon);

            if ($dungeons->isEmpty()) {
                $this->log->diffEnemyForcesNoDungeons($gameVersion->id);

                return null;
            }

            $challengeModes = $this->readChallengeModes($build, $dungeons);
            $treesByMapId   = $this->readEnemyForcesTrees($build);

            $dungeonDiffs = [];
            foreach ($dungeons as $currentDungeon) {
                $dungeonDiffs[$currentDungeon->id] = $this->diffDungeon(
                    $currentDungeon,
                    $gameVersion,
                    $challengeModes[$currentDungeon->challenge_mode_id] ?? null,
                    $treesByMapId,
                );
            }

            return new EnemyForcesDb2Report($product, $build, $dungeonDiffs);
        } finally {
            $this->log->diffEnemyForcesEnd();
        }
    }

    /** @return Collection<int, Dungeon> */
    private function getDungeons(?Dungeon $dungeon): Collection
    {
        /** @var Collection<int, Dungeon> $dungeons */
        $dungeons = Dungeon::query()
            ->whereNotNull('challenge_mode_id')
            ->when($dungeon !== null, static fn($query) => $query->whereKey($dungeon?->id))
            ->when($dungeon === null, static fn($query) => $query->where('active', true))
            ->orderBy('id')
            ->get();

        return $dungeons;
    }

    /**
     * @param  Collection<int, Dungeon>                    $dungeons
     * @return array<int, array{mapId: int, name: string}> challenge mode id => the map it is played on, and the
     *                                                     name the client gives it
     */
    private function readChallengeModes(string $build, Collection $dungeons): array
    {
        $wantedChallengeModeIds = array_fill_keys($dungeons->pluck('challenge_mode_id')->all(), true);

        $challengeModes = [];
        foreach ($this->wagoToolsService->readTable('MapChallengeMode', $build) as $row) {
            $challengeModeId = (int)($row['ID'] ?? 0);

            if (isset($wantedChallengeModeIds[$challengeModeId])) {
                $challengeModes[$challengeModeId] = [
                    'mapId' => (int)($row['MapID'] ?? 0),
                    'name'  => (string)($row['Name_lang'] ?? ''),
                ];
            }
        }

        return $challengeModes;
    }

    /**
     * Every challenge mode scenario's enemy forces tree, keyed by the map its bosses live on.
     *
     * The client offers no id linking a `MapChallengeMode` to its `Scenario` - only their names match, and
     * not always (`Shado-Pan Monastery` is `Shadow-Pan Monastery` as a scenario). The scenario's boss
     * criteria do resolve to a `DungeonEncounter` that names a map, so that is what the scenario is keyed
     * by here instead of its name.
     *
     * @return array<int, array<int, Db2EnemyForcesTree>> map id => scenario id => its forces tree
     */
    private function readEnemyForcesTrees(string $build): array
    {
        $challengeModeScenarioNames = [];
        foreach ($this->wagoToolsService->readTable('Scenario', $build) as $row) {
            if ((int)($row['Type'] ?? -1) === self::SCENARIO_TYPE_CHALLENGE_MODE) {
                $challengeModeScenarioNames[(int)($row['ID'] ?? 0)] = (string)($row['Name_lang'] ?? '');
            }
        }

        $scenarioIdByRootTreeId = [];
        foreach ($this->wagoToolsService->readTable('ScenarioStep', $build) as $row) {
            $scenarioId = (int)($row['ScenarioID'] ?? 0);
            $rootTreeId = (int)($row['CriteriatreeID'] ?? 0);

            if ($rootTreeId !== 0 && isset($challengeModeScenarioNames[$scenarioId])) {
                $scenarioIdByRootTreeId[$rootTreeId] = $scenarioId;
            }
        }

        [$forcesNodesByScenarioId, $bossCriteriaIdsByScenarioId] = $this->readScenarioRootChildren($build, $scenarioIdByRootTreeId);

        $forcesRowsByScenarioId = $this->readForcesNodeChildren($build, $forcesNodesByScenarioId);
        $criteria               = $this->readCriteria($build, $bossCriteriaIdsByScenarioId, $forcesRowsByScenarioId);
        $mapIdByEncounterId     = $this->readEncounterMapIds($build, $criteria);

        $treesByMapId = [];
        foreach ($forcesNodesByScenarioId as $scenarioId => $forcesNodes) {
            $tree = $this->buildTree(
                $scenarioId,
                $challengeModeScenarioNames[$scenarioId] ?? '',
                $forcesNodes,
                $forcesRowsByScenarioId[$scenarioId] ?? [],
                $criteria,
            );

            foreach ($bossCriteriaIdsByScenarioId[$scenarioId] ?? [] as $bossCriteriaId) {
                $criteriaRow = $criteria[$bossCriteriaId] ?? null;

                if ($criteriaRow === null || $criteriaRow['type'] !== self::CRITERIA_TYPE_DUNGEON_ENCOUNTER) {
                    continue;
                }

                $mapId = $mapIdByEncounterId[$criteriaRow['asset']] ?? null;

                if ($mapId === null) {
                    continue;
                }

                // A map is not one dungeon: the wings of a split dungeon share it (both Dawn of the
                // Infinite wings are map 2579), and a reworked dungeon keeps its retired scenario around
                // (Siege of Boralus is both 1685 and 2486). Every candidate is kept and picked between
                // per dungeon.
                $treesByMapId[$mapId][$scenarioId] = $tree;
            }
        }

        return $treesByMapId;
    }

    /**
     * @param  array<int, int>                                                       $scenarioIdByRootTreeId
     * @return array{0: array<int, array<int, int>>, 1: array<int, array<int, int>>}
     */
    private function readScenarioRootChildren(string $build, array $scenarioIdByRootTreeId): array
    {
        $forcesNodesByScenarioId     = [];
        $bossCriteriaIdsByScenarioId = [];

        foreach ($this->wagoToolsService->readTable('CriteriaTree', $build) as $row) {
            $scenarioId = $scenarioIdByRootTreeId[(int)($row['Parent'] ?? 0)] ?? null;

            if ($scenarioId === null) {
                continue;
            }

            if ((int)($row['Operator'] ?? -1) === self::CRITERIA_TREE_OPERATOR_SUM_CHILDREN) {
                $forcesNodesByScenarioId[$scenarioId][(int)($row['ID'] ?? 0)] = (int)($row['Amount'] ?? 0);
            } else {
                $bossCriteriaIdsByScenarioId[$scenarioId][] = (int)($row['CriteriaID'] ?? 0);
            }
        }

        return [$forcesNodesByScenarioId, $bossCriteriaIdsByScenarioId];
    }

    /**
     * The children of the forces node of every scenario that has exactly one - an ambiguous scenario is
     * never read further, so nothing downstream has to pick between its nodes.
     *
     * @param  array<int, array<int, int>>                                 $forcesNodesByScenarioId
     * @return array<int, array<int, array{criteriaId: int, amount: int}>>
     */
    private function readForcesNodeChildren(string $build, array $forcesNodesByScenarioId): array
    {
        $scenarioIdByForcesTreeId = [];
        foreach ($forcesNodesByScenarioId as $scenarioId => $forcesNodes) {
            if (count($forcesNodes) === 1) {
                $scenarioIdByForcesTreeId[(int)array_key_first($forcesNodes)] = $scenarioId;
            }
        }

        $forcesRowsByScenarioId = [];
        foreach ($this->wagoToolsService->readTable('CriteriaTree', $build) as $row) {
            $scenarioId = $scenarioIdByForcesTreeId[(int)($row['Parent'] ?? 0)] ?? null;

            if ($scenarioId === null) {
                continue;
            }

            $forcesRowsByScenarioId[$scenarioId][] = [
                'criteriaId' => (int)($row['CriteriaID'] ?? 0),
                'amount'     => (int)($row['Amount'] ?? 0),
            ];
        }

        return $forcesRowsByScenarioId;
    }

    /**
     * @param  array<int, array<int, int>>                                 $bossCriteriaIdsByScenarioId
     * @param  array<int, array<int, array{criteriaId: int, amount: int}>> $forcesRowsByScenarioId
     * @return array<int, array{type: int, asset: int}>
     */
    private function readCriteria(string $build, array $bossCriteriaIdsByScenarioId, array $forcesRowsByScenarioId): array
    {
        $wantedCriteriaIds = [];

        foreach ($bossCriteriaIdsByScenarioId as $bossCriteriaIds) {
            foreach ($bossCriteriaIds as $bossCriteriaId) {
                $wantedCriteriaIds[$bossCriteriaId] = true;
            }
        }

        foreach ($forcesRowsByScenarioId as $forcesRows) {
            foreach ($forcesRows as $forcesRow) {
                $wantedCriteriaIds[$forcesRow['criteriaId']] = true;
            }
        }

        $criteria = [];
        foreach ($this->wagoToolsService->readTable('Criteria', $build) as $row) {
            $criteriaId = (int)($row['ID'] ?? 0);

            if (isset($wantedCriteriaIds[$criteriaId])) {
                $criteria[$criteriaId] = [
                    'type'  => (int)($row['Type'] ?? -1),
                    'asset' => (int)($row['Asset'] ?? 0),
                ];
            }
        }

        return $criteria;
    }

    /**
     * @param  array<int, array{type: int, asset: int}> $criteria
     * @return array<int, int>                          dungeon encounter id => map id
     */
    private function readEncounterMapIds(string $build, array $criteria): array
    {
        $wantedEncounterIds = [];
        foreach ($criteria as $criteriaRow) {
            if ($criteriaRow['type'] === self::CRITERIA_TYPE_DUNGEON_ENCOUNTER) {
                $wantedEncounterIds[$criteriaRow['asset']] = true;
            }
        }

        $mapIdByEncounterId = [];
        foreach ($this->wagoToolsService->readTable('DungeonEncounter', $build) as $row) {
            $encounterId = (int)($row['ID'] ?? 0);

            if (isset($wantedEncounterIds[$encounterId])) {
                $mapIdByEncounterId[$encounterId] = (int)($row['MapID'] ?? 0);
            }
        }

        return $mapIdByEncounterId;
    }

    /**
     * @param array<int, int>                                 $forcesNodes
     * @param array<int, array{criteriaId: int, amount: int}> $forcesRows
     * @param array<int, array{type: int, asset: int}>        $criteria
     */
    private function buildTree(int $scenarioId, string $scenarioName, array $forcesNodes, array $forcesRows, array $criteria): Db2EnemyForcesTree
    {
        $enemyForcesByNpcId  = [];
        $nonCreatureCriteria = [];

        foreach ($forcesRows as $forcesRow) {
            $criteriaRow = $criteria[$forcesRow['criteriaId']] ?? null;

            if ($criteriaRow === null) {
                continue;
            }

            if ($criteriaRow['type'] === self::CRITERIA_TYPE_KILL_CREATURE) {
                $enemyForcesByNpcId[$criteriaRow['asset']] = $forcesRow['amount'];
            } else {
                $nonCreatureCriteria[] = new Db2CriteriaRow(
                    $forcesRow['criteriaId'],
                    $criteriaRow['type'],
                    $criteriaRow['asset'],
                    $forcesRow['amount'],
                );
            }
        }

        ksort($enemyForcesByNpcId);

        return new Db2EnemyForcesTree($scenarioId, $scenarioName, $forcesNodes, $enemyForcesByNpcId, $nonCreatureCriteria);
    }

    /**
     * @param array{mapId: int, name: string}|null       $challengeMode
     * @param array<int, array<int, Db2EnemyForcesTree>> $treesByMapId
     */
    private function diffDungeon(Dungeon $dungeon, GameVersion $gameVersion, ?array $challengeMode, array $treesByMapId): DungeonEnemyForcesDiff
    {
        $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        if ($mappingVersion === null) {
            return $this->unresolved($dungeon, sprintf('No %s mapping version', $gameVersion->key));
        }

        if ($challengeMode === null) {
            return $this->unresolved($dungeon, sprintf('Build has no MapChallengeMode %d', $dungeon->challenge_mode_id));
        }

        $mapId      = $challengeMode['mapId'];
        $candidates = $treesByMapId[$mapId] ?? [];

        if ($candidates === []) {
            return $this->unresolved($dungeon, sprintf('Build has no challenge mode enemy forces tree for map %d', $mapId), $mappingVersion);
        }

        $tree = $this->pickTree($candidates, $challengeMode['name']);

        if ($tree === null) {
            return $this->unresolved($dungeon, sprintf(
                'Map %d carries %d challenge mode scenarios (%s) and none of them is named %s',
                $mapId,
                count($candidates),
                implode(', ', array_map(
                    static fn(Db2EnemyForcesTree $candidate): string => sprintf('%d "%s"', $candidate->scenarioId, $candidate->scenarioName),
                    $candidates,
                )),
                $challengeMode['name'],
            ), $mappingVersion);
        }

        if ($tree->isAmbiguous()) {
            return $this->unresolved($dungeon, sprintf(
                'Scenario %d has %d enemy forces nodes (%s) - nothing says which one M+ runs',
                $tree->scenarioId,
                count($tree->forcesNodesByCriteriaTreeId),
                implode(', ', array_map(
                    static fn(int $criteriaTreeId, int $enemyForcesRequired): string => sprintf('%d requires %d', $criteriaTreeId, $enemyForcesRequired),
                    array_keys($tree->forcesNodesByCriteriaTreeId),
                    $tree->forcesNodesByCriteriaTreeId,
                )),
            ), $mappingVersion);
        }

        $ourEnemyForcesByNpcId = NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->pluck('enemy_forces', 'npc_id')
            ->all();

        // An NPC the client awards forces for that this mapping version places no enemy of is not a
        // difference in our data: the client's tree keeps the seasonal affix creatures of past expansions.
        $mappedNpcIds = array_fill_keys(
            Enemy::query()
                ->where('mapping_version_id', $mappingVersion->id)
                ->whereNotNull('npc_id')
                ->distinct()
                ->pluck('npc_id')
                ->all(),
            true,
        );

        $unmappedNpcEnemyForces = [];
        $comparedNpcIds         = [];

        foreach ($tree->enemyForcesByNpcId as $npcId => $enemyForces) {
            if (isset($mappedNpcIds[$npcId]) || isset($ourEnemyForcesByNpcId[$npcId])) {
                $comparedNpcIds[$npcId] = true;
            } else {
                $unmappedNpcEnemyForces[$npcId] = $enemyForces;
            }
        }

        foreach ($ourEnemyForcesByNpcId as $npcId => $enemyForces) {
            $comparedNpcIds[(int)$npcId] = true;
        }

        ksort($comparedNpcIds);
        ksort($unmappedNpcEnemyForces);

        $npcNames = Npc::query()
            ->whereIn('id', array_keys($comparedNpcIds))
            ->pluck('name', 'id')
            ->all();

        $npcDiffs = [];
        foreach (array_keys($comparedNpcIds) as $npcId) {
            $npcDiffs[$npcId] = new NpcEnemyForcesDiff(
                $npcId,
                $npcNames[$npcId] ?? null,
                $tree->enemyForcesByNpcId[$npcId] ?? null,
                isset($ourEnemyForcesByNpcId[$npcId]) ? (int)$ourEnemyForcesByNpcId[$npcId] : null,
            );
        }

        return new DungeonEnemyForcesDiff(
            dungeon: $dungeon,
            mappingVersion: $mappingVersion,
            scenarioId: $tree->scenarioId,
            criteriaTreeId: $tree->getCriteriaTreeId(),
            db2EnemyForcesRequired: $tree->getEnemyForcesRequired(),
            npcDiffs: $npcDiffs,
            unmappedNpcEnemyForces: $unmappedNpcEnemyForces,
            nonCreatureCriteria: $tree->nonCreatureCriteria,
        );
    }

    /**
     * The scenario of the dungeon a challenge mode is played in. Several can share a map - the wings of a
     * split dungeon (Dawn of the Infinite), and the retired scenario of a reworked one (Siege of Boralus
     * keeps 1685 alongside 2486) - so a map alone does not identify one.
     *
     * @param array<int, Db2EnemyForcesTree> $candidates
     */
    private function pickTree(array $candidates, string $challengeModeName): ?Db2EnemyForcesTree
    {
        // A retired scenario is left with the several forces nodes of the steps it used to have, so having
        // exactly one is what tells the current scenario apart from it
        $unambiguousCandidates = array_filter($candidates, static fn(Db2EnemyForcesTree $candidate): bool => !$candidate->isAmbiguous());
        $pool                  = $unambiguousCandidates === [] ? $candidates : $unambiguousCandidates;

        if (count($pool) === 1) {
            return array_values($pool)[0];
        }

        // Only the name tells two wings of one map apart, and the client does not always spell it the way
        // its own MapChallengeMode does ("Mechagon Junkyard" against "Operation Mechagon: Junkyard") - a
        // name that does not resolve is reported rather than guessed at.
        $namedCandidates = array_filter(
            $pool,
            static fn(Db2EnemyForcesTree $candidate): bool => strcasecmp(trim($candidate->scenarioName), trim($challengeModeName)) === 0,
        );

        return count($namedCandidates) === 1 ? array_values($namedCandidates)[0] : null;
    }

    private function unresolved(Dungeon $dungeon, string $reason, ?MappingVersion $mappingVersion = null): DungeonEnemyForcesDiff
    {
        $this->log->diffEnemyForcesUnresolvedDungeon($dungeon->id, $reason);

        return new DungeonEnemyForcesDiff(dungeon: $dungeon, mappingVersion: $mappingVersion, unresolvedReason: $reason);
    }
}
