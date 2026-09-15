<?php

namespace App\Service\EnemyForces\Dtos;

/** The outcome of diffing one game build's enemy forces against ours. */
class EnemyForcesDb2Report
{
    /** @param array<int, DungeonEnemyForcesDiff> $dungeonDiffs keyed by dungeon id */
    public function __construct(
        public readonly string $product,
        public readonly string $build,
        public readonly array  $dungeonDiffs,
    ) {
    }

    /** @return array<int, DungeonEnemyForcesDiff> */
    public function getResolvedDungeonDiffs(): array
    {
        return array_filter($this->dungeonDiffs, static fn(DungeonEnemyForcesDiff $diff): bool => $diff->isResolved());
    }

    /** @return array<int, DungeonEnemyForcesDiff> */
    public function getDivergingDungeonDiffs(): array
    {
        return array_filter($this->getResolvedDungeonDiffs(), static fn(DungeonEnemyForcesDiff $diff): bool => !$diff->matches());
    }

    /** @return array<int, DungeonEnemyForcesDiff> */
    public function getDungeonDiffsWithDivergingEnemyForcesRequired(): array
    {
        return array_filter($this->getResolvedDungeonDiffs(), static fn(DungeonEnemyForcesDiff $diff): bool => !$diff->hasMatchingEnemyForcesRequired());
    }
}
