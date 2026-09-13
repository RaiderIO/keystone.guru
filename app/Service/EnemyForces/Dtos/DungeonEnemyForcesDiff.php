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

    /**
     * The NPCs a write takes the client's amount for: the ones it awards a different amount for, and the
     * mapped ones it awards forces for where we hold none.
     *
     * @return array<int, NpcEnemyForcesDiff>
     */
    public function getNpcDiffsToWrite(): array
    {
        return array_filter(
            $this->npcDiffs,
            static fn(NpcEnemyForcesDiff $npcDiff): bool => $npcDiff->db2EnemyForces !== null && !$npcDiff->matches(),
        );
    }

    /**
     * The NPCs we hold forces for that the client awards none for. Mostly Shrouded affix creatures, whose
     * forces come from the mapping version instead - a write keeps them.
     *
     * @return array<int, NpcEnemyForcesDiff>
     */
    public function getNpcDiffsOnlyWeHold(): array
    {
        return array_filter($this->npcDiffs, static fn(NpcEnemyForcesDiff $npcDiff): bool => $npcDiff->db2EnemyForces === null);
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
