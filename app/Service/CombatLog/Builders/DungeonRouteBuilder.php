<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPatrol;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\KillZone\KillZoneSpell;
use App\Service\CombatLog\Logging\DungeonRouteBuilderLoggingInterface;
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

    protected CoordinatesServiceInterface $coordinatesService;

    protected DungeonRoute $dungeonRoute;

    protected ?Floor $currentFloor;

    /** @var Collection|Enemy[] */
    protected Collection $availableEnemies;

    protected ActivePullCollection $activePullCollection;

    /** @var Collection|int */
    protected Collection $validNpcIds;

    private int $killZoneIndex = 1;

    private DungeonRouteBuilderLoggingInterface $log;

    /**
     * @param CoordinatesServiceInterface $coordinatesService
     * @param DungeonRoute                $dungeonRoute
     */
    public function __construct(
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute                $dungeonRoute
    ) {
        $this->coordinatesService = $coordinatesService;
        $this->dungeonRoute       = $dungeonRoute;
        /** @var DungeonRouteBuilderLoggingInterface $log */
        $log                    = App::make(DungeonRouteBuilderLoggingInterface::class);
        $this->log              = $log;
        $this->currentFloor     = null;
        $this->availableEnemies = $this->dungeonRoute->mappingVersion->enemies()->with([
            'floor',
            'floor.dungeon',
            'enemyPack',
            'enemyPatrol',
        ])
            ->get()
            ->each(function (Enemy $enemy) {
                // Ensure that the kill priority is 0 if it wasn't set
                $enemy->kill_priority = $enemy->kill_priority ?? 0;
            })
            ->sort(function (Enemy $enemy) {
                return $enemy->enemy_patrol_id === null ? 0 : $enemy->enemy_patrol_id;
            })
            ->keyBy('id');

        // #1818 Filter out any NPC ids that are invalid
        $this->validNpcIds          = $dungeonRoute->dungeon->getInUseNpcIds();
        $this->activePullCollection = new ActivePullCollection();
    }

    /**
     * @return DungeonRoute
     * @throws Exception
     */
    public abstract function build(): DungeonRoute;

    /**
     * @return void
     */
    protected function buildFinished()
    {
        // Direct update doesn't work.. no clue why
        $enemyForces = $this->dungeonRoute->getEnemyForces();
        DungeonRoute::find($this->dungeonRoute->id)->update(['enemy_forces' => $enemyForces]);
        $this->dungeonRoute->enemy_forces = $enemyForces;
    }

    /**
     * @param ActivePull $activePull
     *
     * @return KillZone
     */
    protected function createPull(ActivePull $activePull): KillZone
    {
        try {
            $this->log->createPullStart($this->killZoneIndex);

            /** @var Collection|ActivePullEnemy[] $killedEnemies */
            $killedEnemies = $activePull->getEnemiesKilled();

            $killZone = KillZone::create([
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

                        $killZone->killZoneEnemies->push($enemy);

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

            if ($killZoneEnemiesAttributes->isNotEmpty()) {
                KillZoneEnemy::insert($killZoneEnemiesAttributes->toArray());
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
                    KillZoneSpell::insert($killZoneSpellsAttributes->toArray());
                    $spellCount = $killZoneSpellsAttributes->count();
                    $this->log->createPullSpellsAttachedToKillZone($spellCount);
                }

                // Write the killzone back to the dungeon route
                $this->dungeonRoute->setRelation('killZones', $this->dungeonRoute->killZones->push($killZone));
            } else {
                // No enemies were inserted for this pull, so it's worthless. Delete it
                $killZone->delete();
                $this->log->createPullNoEnemiesPullDeleted();
            }
        } finally {
            $this->log->createPullEnd();
        }

        return $killZone;
    }

    /**
     * @param ActivePullEnemy $activePullEnemy
     * @param Collection      $preferredGroups The groups that are pulled and should always be preferred when choosing enemies
     *
     * @return Enemy|null
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
                optional($previousPullLatLng)->getLat(),
                optional($previousPullLatLng)->getLng(),
                $preferredGroups->toArray()
            );

            // Find the closest Enemy with the same NPC ID that is not killed yet
            $closestEnemy = new ClosestEnemy();

            /** @var Collection|Enemy[] $filteredEnemies */
            $filteredEnemies = $this->availableEnemies->filter(function (Enemy $availableEnemy) use ($activePullEnemy, $npcId) {
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
     *
     * @param Collection      $preferredGroups
     * @param Collection      $filteredEnemies
     * @param ActivePullEnemy $activePullEnemy
     * @param LatLng|null     $previousPullLatLng
     * @param ClosestEnemy    $closestEnemy
     * @return void
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
        $preferredEnemiesInEngagedGroups = $filteredEnemies->filter(function (Enemy $availableEnemy) use ($preferredGroups) {
            if ($availableEnemy->enemy_pack_id === null) {
                return false;
            }

            return $preferredGroups->has($availableEnemy->enemyPack->group);
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
     *
     * @param Collection      $filteredEnemies
     * @param ActivePullEnemy $activePullEnemy
     * @param LatLng|null     $previousPullLatLng
     * @param ClosestEnemy    $closestEnemy
     * @return void
     */
    private function findClosestEnemyInPreferredFloor(
        Collection      $filteredEnemies,
        ActivePullEnemy $activePullEnemy,
        ?LatLng         $previousPullLatLng,
        ClosestEnemy    $closestEnemy
    ): void {
        /** @var Collection|Enemy[] $preferredEnemiesOnCurrentFloor */
        $preferredEnemiesOnCurrentFloor = $filteredEnemies->filter(function (Enemy $availableEnemy) {
            return $availableEnemy->floor_id == $this->currentFloor->id;
        });

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
     * @param Collection      $filteredEnemies
     * @param ActivePullEnemy $activePullEnemy
     * @param LatLng|null     $previousPullLatLng
     * @param ClosestEnemy    $closestEnemy
     * @return void
     */
    private function findClosestEnemyInAllFilteredEnemies(
        Collection      $filteredEnemies,
        ActivePullEnemy $activePullEnemy,
        ?LatLng         $previousPullLatLng,
        ClosestEnemy    $closestEnemy)
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
            $this->log->findUnkilledEnemyForNpcAtIngameLocationClosestEnemy(
                optional($closestEnemy)->id,
                $closestEnemy->getDistanceBetweenEnemies(),
                $closestEnemy->getDistanceBetweenLastPullAndEnemy()
            );
        } else if ($closestEnemy->getDistanceBetweenEnemies() >
            ($this->currentFloor->enemy_engagement_max_range ?? config('keystoneguru.enemy_engagement_max_range_default'))) {
            if ($closestEnemy->getEnemy()->npc->classification_id >= App\Models\NpcClassification::ALL[App\Models\NpcClassification::NPC_CLASSIFICATION_BOSS]) {
                $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyIsBossIgnoringTooFarAwayCheck();
            } else {
                $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyTooFarAway(
                    $closestEnemy->getEnemy()->id,
                    $closestEnemy->getDistanceBetweenEnemies(),
                    $closestEnemy->getDistanceBetweenLastPullAndEnemy(),
                    $this->currentFloor->enemy_engagement_max_range
                );

                $closestEnemy->setEnemy(null);
            }
        }
    }

    /**
     * @param Collection      $enemies
     * @param ActivePullEnemy $enemy
     * @param LatLng|null     $previousPullLatLng
     * @param ClosestEnemy    $closestEnemy
     * @param bool            $considerPatrols
     *
     * @return bool
     */
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
        $enemiesByKillPriority = $enemies->groupBy(function (Enemy $enemy) {
            return $enemy->kill_priority ?? 0;
        })->sortKeysDesc();

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
            optional($closestEnemy->getEnemy())->id,
            $closestEnemy->getDistanceBetweenEnemies(),
            $closestEnemy->getDistanceBetweenLastPullAndEnemy()
        );

        return $result;
    }

    /**
     * @param Enemy        $availableEnemy
     * @param LatLng       $enemyLatLng
     * @param LatLng|null  $previousPullLatLng
     * @param IngameXY     $targetIngameXY
     * @param ClosestEnemy $closestEnemy
     *
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


        if ($distanceBetweenEnemies < $this->currentFloor->enemy_engagement_max_range) {
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
