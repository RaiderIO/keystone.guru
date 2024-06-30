<?php

namespace App\Service\CombatLog\Builders;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPatrol;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\NpcClassification;
use App\Models\Spell;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Service\CombatLog\Builders\Logging\DungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Models\ActivePull\ActivePull;
use App\Service\CombatLog\Models\ActivePull\ActivePullCollection;
use App\Service\CombatLog\Models\ActivePull\ActivePullEnemy;
use App\Service\CombatLog\Models\ClosestEnemy;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Support\Collection;

abstract class DungeonRouteBuilder
{
    private const DUNGEON_ENEMY_FLOOR_CHECK_ENABLED = [
        //        Dungeon::DUNGEON_WAYCREST_MANOR
    ];

    protected const NPC_ID_MAPPING = [
        // Brackenhide Gnolls transform into Witherlings after engaging them
        194373 => 187238,
    ];

    /** @var int The average HP of the current pull before we consider new enemies as part of a new pull */
    protected const CHAIN_PULL_DETECTION_HP_PERCENT = 30;

    /** @var float Value between 0..1 for how much the distance between enemies matters vrs distance of previous pull */
    private const ENEMY_DISTANCE_WEIGHT_RATIO = 0.75;

    /**
     * @var int If the last pull was this many yards away, cap the distance since you've just skipped a lot of enemies.
     *          Leaving this uncapped may cause more problems, the enemy distance should be more leading. This will just
     *          be a nudge to help with the correct direction instead of a massively stretchy elastic band pulling you
     *          wayyyyyy back to the packs you have just skipped.
     */
    private const ENEMY_LAST_PULL_DISTANCE_CAP_YARDS = 100;

    protected ?Floor $currentFloor;

    /** @var Collection<Enemy> */
    protected Collection $availableEnemies;

    protected ActivePullCollection $activePullCollection;

    /** @var Collection<int> */
    protected Collection $validNpcIds;

    /** @var Collection<int> */
    protected Collection $validSpellIds;

    private int $killZoneIndex = 1;

    /** @var Collection<KillZone> */
    protected Collection $killZones;

    public function __construct(
        protected CoordinatesServiceInterface         $coordinatesService,
        protected DungeonRouteRepositoryInterface     $dungeonRouteRepository,
        protected KillZoneRepositoryInterface         $killZoneRepository,
        protected KillZoneEnemyRepositoryInterface    $killZoneEnemyRepository,
        protected KillZoneSpellRepositoryInterface    $killZoneSpellRepository,
        protected DungeonRoute                        $dungeonRoute,
        private readonly DungeonRouteBuilderLoggingInterface $log
    ) {
        $this->currentFloor     = null;
        $this->availableEnemies = $this->dungeonRoute->mappingVersion->enemies()->with([
            'floor',
            'floor.dungeon',
            'enemyPack',
            'enemyPatrol',
        ])->get()
            ->each(static function (Enemy $enemy) {
                // Ensure that the kill priority is 0 if it wasn't set
                $enemy->kill_priority ??= 0;
            })
            ->sort(static fn(Enemy $enemy) => $enemy->enemy_patrol_id ?? 0)
            ->keyBy('id');

        // #1818 Filter out any NPC ids that are invalid
        $this->validNpcIds          = $this->dungeonRoute->dungeon->getInUseNpcIds();

        $this->validSpellIds        = Spell::all('id')->pluck(['id']);
        $this->activePullCollection = new ActivePullCollection();

        // This allows me to set the killZones in buildFinished, so that existing relations are still preserved
        // If you don't Laravel probably starts resolving relations, and it will lose relations that were set
        // manually, which sucks when the repositories in these classes are actually Stubs
        $this->killZones = collect();
    }

    /**
     * @throws Exception
     */
    abstract public function build(): DungeonRoute;

    /**
     * @return void
     */
    protected function buildFinished(): void
    {
        $this->dungeonRoute->setRelation('killZones', $this->killZones);

        // Direct update doesn't work.. no clue why
        $enemyForces = $this->dungeonRoute->getEnemyForces();
        $this->dungeonRouteRepository->find($this->dungeonRoute->id)->update(['enemy_forces' => $enemyForces]);
        $this->dungeonRoute->enemy_forces = $enemyForces;
    }

    protected function createPull(ActivePull $activePull): KillZone
    {
        try {
            $this->log->createPullStart($this->killZoneIndex);

            /** @var Collection|ActivePullEnemy[] $killedEnemies */
            $killedEnemies = $activePull->getEnemiesKilled();

            $killZone = $this->killZoneRepository->create([
                'dungeon_route_id' => $this->dungeonRoute->id,
                'color'            => randomHexColorNoMapColors(),
                'index'            => $this->killZoneIndex,
            ]);

            // Keep track of which groups we're in combat with
            $killZoneEnemiesAttributes = collect();
            foreach ($killedEnemies as $guid => $killedEnemy) {
                /** @var string $guid */
                try {
                    $this->log->createPullFindEnemyForGuidStart($guid);

                    $enemy = $killedEnemy->getResolvedEnemy();

                    if ($enemy === null) {
                        $this->log->createPullEnemyNotFound(
                            $killedEnemy->getNpcId(),
                            $killedEnemy->getX(),
                            $killedEnemy->getY()
                        );
                    } else {
                        // Schedule for creation later
                        $killZoneEnemiesAttributes->push([
                            'kill_zone_id' => $killZone->id,
                            'npc_id'       => $enemy->npc_id,
                            'mdt_id'       => $enemy->mdt_id,
                        ]);

                        $this->log->createPullEnemyAttachedToKillZone(
                            $killedEnemy->getNpcId(),
                            $killedEnemy->getX(),
                            $killedEnemy->getY()
                        );
                    }
                } finally {
                    $this->log->createPullFindEnemyForGuidEnd();
                }
            }

            $killZone->setRelation('killZoneEnemies', $killZoneEnemiesAttributes->map(function (array $attributes) {
                return new KillZoneEnemy($attributes);
            }));

            if ($killZoneEnemiesAttributes->isNotEmpty()) {
                $this->killZoneEnemyRepository->insert($killZoneEnemiesAttributes->toArray());
                $this->killZoneIndex++;
                $enemyCount = $killZoneEnemiesAttributes->count();
                $this->log->createPullInsertedEnemies($enemyCount);

                // Assign spells to the pull
                $killZoneSpellsAttributes = collect();
                foreach ($activePull->getSpellsCast() as $spellId) {
                    $killZoneSpellsAttributes->push([
                        'kill_zone_id' => $killZone->id,
                        'spell_id'     => $spellId,
                    ]);
                }

                if ($killZoneSpellsAttributes->isNotEmpty()) {
                    $this->killZoneSpellRepository->insert($killZoneSpellsAttributes->toArray());
                    $spellCount = $killZoneSpellsAttributes->count();
                    $this->log->createPullSpellsAttachedToKillZone($spellCount);
                }

                $this->killZones->push($killZone);
            } else {
                // No enemies were inserted for this pull, so it's worthless. Delete it
                $this->killZoneRepository->delete($killZone);
                $this->log->createPullNoEnemiesPullDeleted();
            }
        } finally {
            $this->log->createPullEnd();
        }

        return $killZone;
    }

    /**
     * @param Collection $preferredGroups The groups that are pulled and should always be preferred when choosing enemies
     */
    protected function findUnkilledEnemyForNpcAtIngameLocation(
        ActivePullEnemy $activePullEnemy,
        Collection      $preferredGroups
    ): ?Enemy {
        // See if we actually need to go look for another NPC
        if (isset(self::NPC_ID_MAPPING[$activePullEnemy->getNpcId()])) {
            $this->log->findUnkilledEnemyForNpcAtIngameLocationMappingToDifferentNpcId(
                $activePullEnemy->getNpcId(), self::NPC_ID_MAPPING[$activePullEnemy->getNpcId()]
            );
            $npcId = self::NPC_ID_MAPPING[$activePullEnemy->getNpcId()];
        } else {
            $npcId = $activePullEnemy->getNpcId();
        }

        /** @var LatLng|null $previousPullLatLng */
        $previousPullLatLng = null;
        /** @var KillZone $previousPull */
        $previousPull = $this->dungeonRoute->killZones->last();
        if ($previousPull !== null) {
            $previousPullLatLng = $previousPull->getKillLocation(true);
        }

        try {
            $this->log->findUnkilledEnemyForNpcAtIngameLocationStart(
                $npcId, $activePullEnemy->getX(), $activePullEnemy->getY(),
                $previousPullLatLng?->getLat(),
                $previousPullLatLng?->getLng(),
                $preferredGroups->toArray()
            );

            // Find the closest Enemy with the same NPC ID that is not killed yet
            $closestEnemy = new ClosestEnemy();

            /** @var Collection|Enemy[] $filteredEnemies */
            $filteredEnemies = $this->availableEnemies->filter(function (Enemy $availableEnemy) use ($npcId) {
                if ($availableEnemy->npc_id !== $npcId) {
                    return false;
                }

                if ($availableEnemy->teeming !== null) {
                    return false;
                }

                // Floor checks are a nice idea but in practice they don't work because Blizzard does not take floors
                // as seriously as we do. For just about every dungeon there are enemies on the wrong floors after which
                // I have to exclude them in the below check, but every dungeon has these issues, so we simply cannot do this.
                // Annoying, but that's what it is.
                if (in_array($availableEnemy->floor->dungeon->key, self::DUNGEON_ENEMY_FLOOR_CHECK_ENABLED) &&
                    $availableEnemy->floor_id !== $this->currentFloor->id) {
                    return false;
                }

                return true;
            });

            $this->findClosestEnemyInPreferredGroups(
                $preferredGroups,
                $filteredEnemies,
                $activePullEnemy,
                $previousPullLatLng,
                $closestEnemy
            );

            if ($closestEnemy->getEnemy() !== null) {
                // If we found an enemy in one of our preferred packs, we must not continue searching
                $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredGroup(
                    $closestEnemy->getEnemy()->id,
                    $closestEnemy->getDistanceBetweenEnemies(),
                    $closestEnemy->getDistanceBetweenLastPullAndEnemy(),
                    $closestEnemy->getEnemy()->enemyPack->group
                );
            } else {
                $this->findClosestEnemyInPreferredFloor(
                    $filteredEnemies,
                    $activePullEnemy,
                    $previousPullLatLng,
                    $closestEnemy
                );
            }

            if ($closestEnemy->getEnemy() !== null) {
                // If we found an enemy on our preferred floor, we must not continue searching
                $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredFloor(
                    $closestEnemy->getEnemy()->id,
                    $closestEnemy->getDistanceBetweenEnemies(),
                    $closestEnemy->getDistanceBetweenLastPullAndEnemy(),
                    $closestEnemy->getEnemy()->floor_id
                );
            } else {
                $this->findClosestEnemyInAllFilteredEnemies(
                    $filteredEnemies,
                    $activePullEnemy,
                    $previousPullLatLng,
                    $closestEnemy
                );
            }

            if ($closestEnemy->getEnemy() !== null) {
                $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyFound(
                    $closestEnemy->getEnemy()->id,
                    $closestEnemy->getDistanceBetweenEnemies(),
                    $closestEnemy->getDistanceBetweenLastPullAndEnemy()
                );

                $this->availableEnemies->forget($closestEnemy->getEnemy()->id);
            }
        } finally {
            $this->log->findUnkilledEnemyForNpcAtIngameLocationEnd();
        }

        return $closestEnemy->getEnemy();
    }

    /**
     * If we're looking for the closest enemy for an active pull, check if we can find a matching enemy in an already
     * engaged pack.
     */
    private function findClosestEnemyInPreferredGroups(
        Collection      $preferredGroups,
        Collection      $filteredEnemies,
        ActivePullEnemy $activePullEnemy,
        ?LatLng         $previousPullLatLng,
        ClosestEnemy    $closestEnemy
    ): void {
        // Build a list of potential enemies which will always take precedence since they're in a group that we have aggroed.
        // Therefore, these enemies should be in combat with us regardless
        /** @var Collection|Enemy[] $preferredEnemiesInEngagedGroups */
        $preferredEnemiesInEngagedGroups = $filteredEnemies->filter(static function (Enemy $availableEnemy) use ($preferredGroups) {
            return $availableEnemy->enemy_pack_id !== null && $preferredGroups->has($availableEnemy->enemyPack->group);
        });

        if ($preferredEnemiesInEngagedGroups->isNotEmpty()) {
            $this->findClosestEnemyAndDistanceFromList(
                $preferredEnemiesInEngagedGroups,
                $activePullEnemy,
                $previousPullLatLng,
                $closestEnemy
            );
        }
    }

    /**
     * Check if we can find an enemy on our preferred floor first. If we cannot find it, only then consider enemies
     * on other floors.
     */
    private function findClosestEnemyInPreferredFloor(
        Collection      $filteredEnemies,
        ActivePullEnemy $activePullEnemy,
        ?LatLng         $previousPullLatLng,
        ClosestEnemy    $closestEnemy
    ): void {
        /** @var Collection|Enemy[] $preferredEnemiesOnCurrentFloor */
        $preferredEnemiesOnCurrentFloor = $filteredEnemies->filter(fn(Enemy $availableEnemy) => $availableEnemy->floor_id == $this->currentFloor->id);

        if ($preferredEnemiesOnCurrentFloor->isNotEmpty()) {
            $this->findClosestEnemyAndDistanceFromList(
                $preferredEnemiesOnCurrentFloor,
                $activePullEnemy,
                $previousPullLatLng,
                $closestEnemy
            );
        }
    }

    /**
     * If we cannot find an enemy with any other criteria, just consider them all instead.
     *
     * @return void
     */
    private function findClosestEnemyInAllFilteredEnemies(
        Collection      $filteredEnemies,
        ActivePullEnemy $activePullEnemy,
        ?LatLng         $previousPullLatLng,
        ClosestEnemy    $closestEnemy): void
    {
        $this->findClosestEnemyAndDistanceFromList(
            $filteredEnemies,
            $activePullEnemy,
            $previousPullLatLng,
            $closestEnemy
        );

        // If the closest enemy was still pretty far away - check if there was a patrol that may have been closer
        if ($closestEnemy->getDistanceBetweenEnemies() >
            ($this->currentFloor->enemy_engagement_max_range_patrols ?? config('keystoneguru.enemy_engagement_max_range_patrols_default'))) {
            $this->findClosestEnemyAndDistanceFromList(
                $filteredEnemies,
                $activePullEnemy,
                $previousPullLatLng,
                $closestEnemy,
                true
            );
        }

        if ($closestEnemy->getEnemy() === null) {
            $this->log->findClosestEnemyInAllFilteredEnemiesEnemyIsNull(
                $closestEnemy->getDistanceBetweenEnemies(),
                $closestEnemy->getDistanceBetweenLastPullAndEnemy()
            );
        } else if ($closestEnemy->getDistanceBetweenEnemies() >
            ($this->currentFloor->enemy_engagement_max_range ?? config('keystoneguru.enemy_engagement_max_range_default'))) {
            if ($closestEnemy->getEnemy()->npc->classification_id >= NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]) {
                $this->log->findClosestEnemyInAllFilteredEnemiesEnemyIsBossIgnoringTooFarAwayCheck();
            } else {
                $this->log->findClosestEnemyInAllFilteredEnemiesEnemyTooFarAway(
                    $closestEnemy->getEnemy()->id,
                    $closestEnemy->getDistanceBetweenEnemies(),
                    $closestEnemy->getDistanceBetweenLastPullAndEnemy(),
                    $this->currentFloor->enemy_engagement_max_range ?? config('keystoneguru.enemy_engagement_max_range_default')
                );

                $closestEnemy->setEnemy(null);
            }
        }
    }

    private function findClosestEnemyAndDistanceFromList(
        Collection      $enemies,
        ActivePullEnemy $enemy,
        ?LatLng         $previousPullLatLng,
        ClosestEnemy    $closestEnemy,
        bool            $considerPatrols = false
    ): bool {
        $result = false;

        $this->log->findClosestEnemyAndDistanceFromList($enemies->count(), $considerPatrols);

        // Sort descending - higher priorities go first
        $enemiesByKillPriority = $enemies->groupBy(static fn(Enemy $enemy) => $enemy->kill_priority ?? 0)->sortKeysDesc();

        foreach ($enemiesByKillPriority as $killPriority => $availableEnemies) {
            /** @var Collection|Enemy[] $availableEnemies */
            $this->log->findClosestEnemyAndDistanceFromListPriority($killPriority, $availableEnemies->count());

            // For each group of enemies
            foreach ($availableEnemies as $availableEnemy) {
                if ($considerPatrols) {
                    if (!($availableEnemy->enemyPatrol instanceof EnemyPatrol)) {
                        continue;
                    }

                    // If this enemy is part of a patrol, consider all patrol vertices as a location of this enemy as well.
                    $vertices = $availableEnemy->enemyPatrol->polyline->getDecodedLatLngs($availableEnemy->floor);

                    foreach ($vertices as $latLng) {
                        $foundNewClosestEnemy = $this->findClosestEnemyAndDistance(
                            $availableEnemy,
                            $latLng,
                            $previousPullLatLng,
                            $enemy->getIngameXY(),
                            $closestEnemy
                        );
                        $result               = $result || $foundNewClosestEnemy;
                    }
                } else {
                    $foundNewClosestEnemy = $this->findClosestEnemyAndDistance(
                        $availableEnemy,
                        $availableEnemy->getLatLng(),
                        $previousPullLatLng,
                        $enemy->getIngameXY(),
                        $closestEnemy
                    );
                    $result               = $result || $foundNewClosestEnemy;
                }
            }

            // If we found a matching enemy in the above list, stop completely
            if ($result) {
                $this->log->findClosestEnemyAndDistanceFromListFoundEnemy();
                break;
            }
        }

        $this->log->findClosestEnemyAndDistanceFromListResult(
            $closestEnemy->getEnemy()?->id,
            $closestEnemy->getDistanceBetweenEnemies(),
            $closestEnemy->getDistanceBetweenLastPullAndEnemy()
        );

        return $result;
    }

    /**
     * @return bool True if an enemy close enough was found
     */
    private function findClosestEnemyAndDistance(
        Enemy        $availableEnemy,
        LatLng       $enemyLatLng,
        ?LatLng      $previousPullLatLng,
        IngameXY     $targetIngameXY,
        ClosestEnemy $closestEnemy
    ): bool {
        $result = false;

        // Always use the floor that the enemy itself is on, not $this->currentFloor
        $enemyXY                = $this->coordinatesService->calculateIngameLocationForMapLocation($enemyLatLng);
        $distanceBetweenEnemies = $this->coordinatesService->distanceBetweenPoints(
            $enemyXY->getX(),
            $targetIngameXY->getX(),
            $enemyXY->getY(),
            $targetIngameXY->getY(),
        );

        // $this->log->findClosestEnemyAndDistanceDistanceBetweenEnemies($enemyXY->toArray(), $targetIngameXY->toArray(), $distanceBetweenEnemies, $closestEnemy->getDistanceBetweenEnemies());

        if ($distanceBetweenEnemies < ($this->currentFloor->enemy_engagement_max_range ?? config('keystoneguru.enemy_engagement_max_range_default'))) {
            // Calculate the location of the latLng
            /** @var IngameXY|null $previousPullIngameXY */
            $previousPullIngameXY = $previousPullLatLng === null || $previousPullLatLng->getFloor() === null ?
                null : $this->coordinatesService->calculateIngameLocationForMapLocation($previousPullLatLng);

            $distanceBetweenPreviousPullAndEnemy = min($previousPullIngameXY === null ? 0 : $this->coordinatesService->distanceBetweenPoints(
                $enemyXY->getX(),
                $previousPullIngameXY->getX(),
                $enemyXY->getY(),
                $previousPullIngameXY->getY(),
            ), self::ENEMY_LAST_PULL_DISTANCE_CAP_YARDS);

            // Calculate the weighted total distance which is a combination of the distance between our event enemy
            // and the candidate enemy, AND the candidate enemy with the kill location of the previous pull
            $weightedTotalDistance = (
                ($distanceBetweenEnemies * self::ENEMY_DISTANCE_WEIGHT_RATIO) +
                ($distanceBetweenPreviousPullAndEnemy * (1 - self::ENEMY_DISTANCE_WEIGHT_RATIO))
            );

            // If a combination of these factors yields a "distance" closer than the enemy we had before, we found a "closer" enemy.
            if ($closestEnemy->getWeightedTotalDistance() > $weightedTotalDistance) {
                $closestEnemy->setEnemy($availableEnemy);
                $closestEnemy->setDistanceBetweenEnemies($distanceBetweenEnemies);
                $closestEnemy->setDistanceBetweenLastPullAndEnemy($distanceBetweenPreviousPullAndEnemy);
                $closestEnemy->setWeightedTotalDistance($weightedTotalDistance);

                $result = true;
            }
        }

        return $result;
    }
}
