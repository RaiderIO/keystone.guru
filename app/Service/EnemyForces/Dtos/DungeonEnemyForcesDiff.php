<?php

namespace App\Service\EnemyForces\Dtos;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;

/** What the game client and we each hold for one dungeon's enemy forces. */
class DungeonEnemyForcesDiff
{
    /**
     * @param array<int, NpcEnemyForcesDiff> $npcDiffs               one entry per NPC either side knows, keyed by NPC id
     * @param array<int, int>                $unmappedNpcEnemyForces NPC id => forces the client awards for NPCs this
     *                                                               mapping version has no enemy of; retired seasonal
     *                                                               affix creatures live on in the client's tree
     * @param array<int, Db2CriteriaRow>     $nonCreatureCriteria
     */
    public function __construct(
        public readonly Dungeon         $dungeon,
        public readonly ?MappingVersion $mappingVersion = null,
        public readonly ?string         $unresolvedReason = null,
        public readonly ?int            $scenarioId = null,
        public readonly ?int            $criteriaTreeId = null,
        public readonly ?int            $db2EnemyForcesRequired = null,
        public readonly array           $npcDiffs = [],
        public readonly array           $unmappedNpcEnemyForces = [],
        public readonly array           $nonCreatureCriteria = [],
    ) {
    }

    public function isResolved(): bool
    {
        return $this->unresolvedReason === null;
    }

    /** @return array<int, NpcEnemyForcesDiff> */
    public function getMismatchedNpcDiffs(): array
    {
        return array_filter($this->npcDiffs, static fn(NpcEnemyForcesDiff $npcDiff): bool => !$npcDiff->matches());
    }

    /** The total the bar requires - the one number a server side hotfix moves. */
    public function hasMatchingEnemyForcesRequired(): bool
    {
        return $this->isResolved() && $this->db2EnemyForcesRequired === $this->mappingVersion?->enemy_forces_required;
    }

    public function matches(): bool
    {
        return $this->hasMatchingEnemyForcesRequired() && $this->getMismatchedNpcDiffs() === [];
    }
}
