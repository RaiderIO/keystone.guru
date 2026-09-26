<?php

namespace App\Service\DungeonRoute\Dtos;

use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

/**
 * What upgrading a dungeon route from one mapping version to another did to it.
 *
 * The route scoped parts come first because they are the ones the author has to act on; the dungeon wide
 * counts at the bottom are context for how big a mapping change this was.
 */
readonly class MappingVersionUpgradeDiff
{
    /**
     * @param Collection<int, MappingVersionUpgradeDiffEnemy>          $removedPullEnemies
     * @param Collection<int, MappingVersionUpgradeDiffPull>           $emptiedPulls
     * @param Collection<int, MappingVersionUpgradeDiffMovedEnemy>     $movedPullEnemies
     * @param Collection<int, MappingVersionUpgradeDiffEnemy>          $unkilledRequiredEnemies
     * @param Collection<int, MappingVersionUpgradeDiffNpcEnemyForces> $npcEnemyForcesChanges
     */
    public function __construct(
        public MappingVersion $oldMappingVersion,
        public MappingVersion $newMappingVersion,
        public Collection     $removedPullEnemies,
        public Collection     $emptiedPulls,
        public Collection     $movedPullEnemies,
        public Collection     $unkilledRequiredEnemies,
        public Collection     $npcEnemyForcesChanges,
        public ?int           $oldEnemyForces,
        /** Null when the diff was computed without an upgraded route to read the new total off. */
        public ?int           $newEnemyForces,
        public int            $oldEnemyForcesRequired,
        public int            $newEnemyForcesRequired,
        public int            $addedEnemyCount,
        public int            $removedEnemyCount,
        public int            $oldEnemyPatrolCount,
        public int            $newEnemyPatrolCount,
    ) {
    }

    /**
     * Whether anything in this diff asks something of the author.
     */
    public function hasRouteImpact(): bool
    {
        return $this->removedPullEnemies->isNotEmpty()
            || $this->emptiedPulls->isNotEmpty()
            || $this->movedPullEnemies->isNotEmpty()
            || $this->unkilledRequiredEnemies->isNotEmpty()
            || $this->hasEnemyForcesImpact();
    }

    public function hasEnemyForcesImpact(): bool
    {
        return ($this->newEnemyForces !== null && $this->newEnemyForces !== $this->oldEnemyForces)
            || $this->newEnemyForcesRequired !== $this->oldEnemyForcesRequired
            || $this->npcEnemyForcesChanges->isNotEmpty();
    }

    /**
     * Whether the mapping changed at all outside of what the route selects.
     */
    public function hasMappingChanges(): bool
    {
        return $this->addedEnemyCount > 0
            || $this->removedEnemyCount > 0
            || $this->oldEnemyPatrolCount !== $this->newEnemyPatrolCount;
    }
}
