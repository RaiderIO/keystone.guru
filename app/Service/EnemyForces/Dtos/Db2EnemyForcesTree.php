<?php

namespace App\Service\EnemyForces\Dtos;

/** The enemy forces a single challenge mode scenario's criteria tree awards. */
class Db2EnemyForcesTree
{
    /**
     * @param array<int, int>            $forcesNodesByCriteriaTreeId every node of the scenario that sums its children
     *                                                                into an enemy forces bar, and the total it
     *                                                                requires. More than one means the scenario has
     *                                                                several steps - a normal and a teeming variant
     *                                                                (Siege of Boralus 319/383), or two halves of a
     *                                                                dungeon (Operation Mechagon 374/192) - and
     *                                                                nothing in these tables says which one M+ runs.
     * @param array<int, int>            $enemyForcesByNpcId          creature id => forces awarded per kill
     * @param array<int, Db2CriteriaRow> $nonCreatureCriteria         rows the node carries that are not creature kills
     */
    public function __construct(
        public readonly int    $scenarioId,
        public readonly string $scenarioName,
        public readonly array  $forcesNodesByCriteriaTreeId,
        public readonly array  $enemyForcesByNpcId = [],
        public readonly array  $nonCreatureCriteria = [],
    ) {
    }

    public function isAmbiguous(): bool
    {
        return count($this->forcesNodesByCriteriaTreeId) !== 1;
    }

    public function getCriteriaTreeId(): int
    {
        return (int)array_key_first($this->forcesNodesByCriteriaTreeId);
    }

    public function getEnemyForcesRequired(): int
    {
        return $this->forcesNodesByCriteriaTreeId[$this->getCriteriaTreeId()] ?? 0;
    }
}
