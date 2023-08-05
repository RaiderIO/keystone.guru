<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Logic\Utils\MathUtils;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPatrol;
use App\Models\Floor;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\KillZone\KillZoneSpell;
use App\Service\CombatLog\Logging\DungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Models\ActivePull\ActivePull;
use App\Service\CombatLog\Models\ActivePull\ActivePullEnemy;
use Exception;
use Illuminate\Support\Collection;

abstract class DungeonRouteBuilder
{
    protected const NPC_ID_MAPPING = [
        // Brackenhide Gnolls transform into Witherlings after engaging them
        194373 => 187238,
    ];

    /** @var int The average HP of the current pull before we consider new enemies as part of a new pull */
    protected const CHAIN_PULL_DETECTION_HP_PERCENT = 30;

    protected DungeonRoute $dungeonRoute;

    protected ?Floor $currentFloor;

    /** @var Collection|Enemy[] */
    protected Collection $availableEnemies;

    /** @var Collection|ActivePull[] */
    protected Collection $activePulls;

    /** @var Collection|int */
    protected Collection $validNpcIds;

    private int $killZoneIndex = 1;

    private DungeonRouteBuilderLoggingInterface $log;

    /**
     * @param DungeonRoute $dungeonRoute
     */
    public function __construct(DungeonRoute $dungeonRoute)
    {
        $this->dungeonRoute = $dungeonRoute;
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
        $this->validNpcIds = $dungeonRoute->dungeon->getInUseNpcIds();
        $this->activePulls = collect();
    }

    /**
     * @return DungeonRoute
     * @throws Exception
     */
    public abstract function build(): DungeonRoute;

    /**
     * @return void
     */
    protected function recalculateEnemyForcesOnDungeonRoute()
    {
        // Direct update doesn't work.. no clue why
        $enemyForces = $this->dungeonRoute->getEnemyForces();
        DungeonRoute::find($this->dungeonRoute->id)->update(['enemy_forces' => $enemyForces]);
        $this->dungeonRoute->enemy_forces = $enemyForces;
    }

    /**
     * @param ActivePull $activePull
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
                'color'            => randomHexColor(),
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
            } else {
                $killZone->delete();
                $this->log->createPullNoEnemiesPullDeleted();
            }

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
        } finally {
            $this->log->createPullEnd();
        }

        return $killZone;
    }

    /**
     * @param int        $npcId
     * @param float      $ingameX
     * @param float      $ingameY
     * @param Collection $preferredGroups The groups that are pulled and should always be preferred when choosing enemies
     *
     * @return Enemy|null
     */
    protected function findUnkilledEnemyForNpcAtIngameLocation(
        int        $npcId,
        float      $ingameX,
        float      $ingameY,
        Collection $preferredGroups
    ): ?Enemy {
        // See if we actually need to go look for another NPC
        if (isset(self::NPC_ID_MAPPING[$npcId])) {
            $this->log->findUnkilledEnemyForNpcAtIngameLocationMappingToDifferentNpcId(
                $npcId, self::NPC_ID_MAPPING[$npcId]
            );
            $npcId = self::NPC_ID_MAPPING[$npcId];
        }

        try {
            $this->log->findUnkilledEnemyForNpcAtIngameLocationStart($npcId, $ingameX, $ingameY, $preferredGroups->toArray());

            // Find the closest Enemy with the same NPC ID that is not killed yet
            $closestEnemyDistance = 99999999999;
            /** @var Enemy|null $closestEnemy */
            $closestEnemy = null;

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
                // I have to exclude them in the below check, but every dungeon has these issues so we simply cannot do this.
                // Annoying, but that's what it is.
//                if (!in_array($availableEnemy->floor->dungeon->key, self::DUNGEON_ENEMY_FLOOR_CHECK_DISABLED) &&
//                    $availableEnemy->floor_id !== $this->currentFloor->id) {
//                    return false;
//                }

                return true;
            });

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
                $this->findClosestEnemyAndDistanceFromList($preferredEnemiesInEngagedGroups, $ingameX, $ingameY, $closestEnemyDistance, $closestEnemy);
            }

            // If we found an enemy in one of our preferred packs, we must not continue searching
            if ($closestEnemy !== null) {
                $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredGroup(
                    $closestEnemy->id, $closestEnemyDistance, $closestEnemy->enemyPack->group
                );
            } else {
                $this->findClosestEnemyAndDistanceFromList($filteredEnemies, $ingameX, $ingameY, $closestEnemyDistance, $closestEnemy);

                // If the closest enemy was still pretty far away - check if there was a patrol that may have been closer
                if ($closestEnemyDistance > $this->currentFloor->enemy_engagement_max_range_patrols) {
                    $this->findClosestEnemyAndDistanceFromList($filteredEnemies, $ingameX, $ingameY, $closestEnemyDistance, $closestEnemy, true);
                }

                if ($closestEnemy === null) {
                    $this->log->findUnkilledEnemyForNpcAtIngameLocationClosestEnemy(
                        optional($closestEnemy)->id, $closestEnemyDistance
                    );
                } else if ($closestEnemyDistance > $this->currentFloor->enemy_engagement_max_range) {
                    if ($closestEnemy->npc->classification_id >= App\Models\NpcClassification::ALL[App\Models\NpcClassification::NPC_CLASSIFICATION_BOSS]) {
                        $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyIsBossIgnoringTooFarAwayCheck();
                    } else {
                        $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyTooFarAway(
                            $closestEnemy->id, $closestEnemyDistance, $this->currentFloor->enemy_engagement_max_range
                        );
                        $closestEnemy = null;
                    }
                }
            }

            if ($closestEnemy !== null) {
                $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyFound(
                    $closestEnemy->id, $closestEnemyDistance
                );

                $this->availableEnemies->forget($closestEnemy->id);
            }
        } finally {
            $this->log->findUnkilledEnemyForNpcAtIngameLocationEnd();
        }

        return $closestEnemy;
    }

    /**
     * @param Collection $enemies
     * @param float      $ingameX
     * @param float      $ingameY
     * @param float      $closestEnemyDistance
     * @param Enemy|null $closestEnemy
     * @param bool       $considerPatrols
     * @return bool
     */
    private function findClosestEnemyAndDistanceFromList(
        Collection $enemies,
        float      $ingameX,
        float      $ingameY,
        float      &$closestEnemyDistance,
        ?Enemy     &$closestEnemy,
        bool       $considerPatrols = false
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
                    $points   = [];
                    $vertices = json_decode($availableEnemy->enemyPatrol->polyline->vertices_json, true);
                    foreach ($vertices as $vertex) {
                        $points[] = $vertex;
                    }

                    foreach ($points as $pointLatLng) {
                        $foundNewClosestEnemy = $this->findClosestEnemyAndDistance(
                            $availableEnemy,
                            $pointLatLng['lat'],
                            $pointLatLng['lng'],
                            $ingameX,
                            $ingameY,
                            $closestEnemyDistance,
                            $closestEnemy
                        );
                        $result               = $result || $foundNewClosestEnemy;
                    }
                } else {
                    $foundNewClosestEnemy = $this->findClosestEnemyAndDistance(
                        $availableEnemy,
                        $availableEnemy->lat,
                        $availableEnemy->lng,
                        $ingameX,
                        $ingameY,
                        $closestEnemyDistance,
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

        $this->log->findClosestEnemyAndDistanceFromListResult(optional($closestEnemy)->id, $closestEnemyDistance);

        return $result;
    }

    /**
     * @param Enemy      $availableEnemy
     * @param float      $enemyLat
     * @param float      $enemyLng
     * @param float      $ingameX
     * @param float      $ingameY
     * @param float      $closestEnemyDistance
     * @param Enemy|null $closestEnemy
     *
     * @return bool True if an enemy close enough was found
     */
    private function findClosestEnemyAndDistance(
        Enemy  $availableEnemy,
        float  $enemyLat,
        float  $enemyLng,
        float  $ingameX,
        float  $ingameY,
        float  &$closestEnemyDistance,
        ?Enemy &$closestEnemy
    ): bool {
        $result = false;

        // Always use the floor that the enemy itself is on, not $this->currentFloor
        $enemyXY = $availableEnemy->floor->calculateIngameLocationForMapLocation($enemyLat, $enemyLng);

        $distance = MathUtils::distanceBetweenPoints(
            $enemyXY['x'],
            $ingameX,
            $enemyXY['y'],
            $ingameY
        );

        if ($closestEnemyDistance > $distance) {
            $closestEnemyDistance = $distance;
            $closestEnemy         = $availableEnemy;
            $result               = $closestEnemyDistance < $this->currentFloor->enemy_engagement_max_range;
        }

        return $result;
    }
}
