<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeCombatLogEvent;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPatrol;
use App\Models\Floor;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;
use App\Service\CombatLog\Logging\DungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\ResultEvents\EnemyKilled;
use App\Service\CombatLog\ResultEvents\MapChange as MapChangeResultEvent;
use Exception;
use Illuminate\Support\Collection;

class DungeonRouteBuilder
{
    /** @var int How much yards the closest enemy must be away from in order to consider if we maybe aggroed a patrol */
    private const MAX_AGGRO_DISTANCE_FOR_PATROLS = 50;

    /** @var int The distance in yards that an enemy must be away from before we completely ignore him - it must be an error. */
    private const MAX_DISTANCE_IGNORE = 100;

    /** @var array Dungeons for which the floor check for enemies is disabled due to issues on Blizzard's side */
    private const DUNGEON_ENEMY_FLOOR_CHECK_DISABLED = [
        // With this check for example, the Gulping Goliath in Halls of Infusion will not be killed as the floor switch only happens til
        // after the boss and as a result we can't find it.
        Dungeon::DUNGEON_HALLS_OF_INFUSION,
        // Spawned in enemies right before final boss will not get picked up otherwise
        Dungeon::DUNGEON_NELTHARUS,
    ];

    private const NPC_ID_MAPPING = [
        // Brackenhide Gnolls transform into Witherlings after engaging them
        194373 => 187238,
    ];

    private DungeonRoute $dungeonRoute;

    /** @var Collection|BaseResultEvent[] */
    private Collection $resultEvents;

    /** @var Collection|EnemyEngaged[] */
    private Collection $enemiesKilledInCurrentPull;

    /** @var Collection|EnemyEngaged[] */
    private Collection $currentEnemiesInCombat;

    private ?Floor $currentFloor;

    private int $killZoneIndex = 1;

    /** @var Collection|Enemy[] */
    private Collection $availableEnemies;

    private DungeonRouteBuilderLoggingInterface $log;

    /**
     * @param DungeonRoute                 $dungeonRoute
     * @param Collection|BaseResultEvent[] $resultEvents
     */
    public function __construct(DungeonRoute $dungeonRoute, Collection $resultEvents)
    {
        $this->dungeonRoute = $dungeonRoute;
        $this->resultEvents = $resultEvents;
        /** @var DungeonRouteBuilderLoggingInterface $log */
        $log                              = App::make(DungeonRouteBuilderLoggingInterface::class);
        $this->log                        = $log;
        $this->enemiesKilledInCurrentPull = collect();
        $this->currentEnemiesInCombat     = collect();
        $this->currentFloor               = null;
        $this->availableEnemies           = $this->dungeonRoute->mappingVersion->enemies()->with([
            'floor',
            'enemyPack',
            'enemyPatrol',
        ])->get()->sort(function (
            Enemy $enemy
        ) {
            return $enemy->enemy_patrol_id === null ? 0 : $enemy->enemy_patrol_id;
        })->keyBy('id');
    }

    /**
     * @return DungeonRoute
     * @throws Exception
     */
    public function build(): DungeonRoute
    {
        foreach ($this->resultEvents as $resultEvent) {
            try {
                $baseEvent = $resultEvent->getBaseEvent();
                $this->log->buildStart(
                    $baseEvent->getTimestamp()->toDateTimeString(),
                    $baseEvent->getEventName()
                );

                if ($resultEvent instanceof MapChangeResultEvent) {
                    /** @var $baseEvent MapChangeCombatLogEvent */
                    $this->currentFloor = $resultEvent->getFloor();
                } elseif ($this->currentFloor === null) {
                    $this->log->buildNoFloorFoundYet();
                    continue;
                }

                if ($resultEvent instanceof EnemyEngaged) {
                    // We are in combat with this enemy now
                    $this->currentEnemiesInCombat->put($resultEvent->getGuid()->getGuid(), $resultEvent);

                    $this->log->buildInCombatWithEnemy($resultEvent->getGuid()->getGuid());
                } elseif ($resultEvent instanceof EnemyKilled) {
                    /** @var $baseEvent UnitDied */
                    // Check if we had this enemy in combat, if so, we just killed it in our current pull
                    // UnitDied only has DestGuid
                    $guid = $baseEvent->getGenericData()->getDestGuid()->getGuid();
                    if ($this->currentEnemiesInCombat->has($guid)) {
                        $this->enemiesKilledInCurrentPull->put($guid, $this->currentEnemiesInCombat->get($guid));

                        $this->currentEnemiesInCombat->forget($guid);
                        $this->log->buildUnitDiedNoLongerInCombat($guid);
                    } else {
                        $this->log->buildUnitDiedNotInCombat($guid);
                    }

                    // If we just killed the last enemy that we were in combat with, we just completed a pull
                    if ($this->currentEnemiesInCombat->isEmpty()) {
                        $this->log->buildCreateNewPull($this->enemiesKilledInCurrentPull->keys()->toArray());

                        $this->createPull();
                    }
                }
            }
            finally {
                $this->log->buildEnd();
            }
        }

        // Ensure that we create a final pull if need be
        if ($this->enemiesKilledInCurrentPull->isNotEmpty()) {
            $this->log->buildCreateNewFinalPull($this->enemiesKilledInCurrentPull->keys()->toArray());
            $this->createPull();
        }

        return $this->dungeonRoute;
    }

    /**
     * @return KillZone
     */
    private function createPull(): KillZone
    {
        $killZone = KillZone::create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'color'            => randomHexColor(),
            'index'            => $this->killZoneIndex,
        ]);

        // Keep track of which groups we're in combat with
        $groupsPulled = collect();
        foreach ($this->enemiesKilledInCurrentPull as $guid => $enemyEngagedEvent) {
            try {
                $this->log->createPullFindEnemyForGuidStart($guid);
                /** @var \App\Service\CombatLog\ResultEvents\EnemyEngaged $enemyEngagedEvent */
                $advancedData = $enemyEngagedEvent->getEngagedEvent()->getAdvancedData();
                $npcId        = $enemyEngagedEvent->getGuid()->getId();

                // See if we actually need to go look for another NPC
                if (isset(self::NPC_ID_MAPPING[$npcId])) {
                    $this->log->createPullFindEnemyForGuidStartMappingToDifferentNpcId(
                        $npcId, self::NPC_ID_MAPPING[$npcId]
                    );
                    $npcId = self::NPC_ID_MAPPING[$npcId];
                }

                $enemy = $this->findUnkilledEnemyForNpcAtIngameLocation(
                    $npcId,
                    $advancedData->getPositionX(),
                    $advancedData->getPositionY(),
                    $groupsPulled
                );

                if ($enemy === null) {
                    $this->log->createPullEnemyNotFound(
                        $npcId,
                        $advancedData->getPositionX(),
                        $advancedData->getPositionY()
                    );
                } else {
                    KillZoneEnemy::create([
                        'kill_zone_id' => $killZone->id,
                        'npc_id'       => $enemy->npc_id,
                        'mdt_id'       => $enemy->mdt_id,
                    ]);

                    $killZone->enemies->push($enemy);
                    // If this enemy was part of a pack, ensure that we know that this group has been pulled
                    if ($enemy->enemy_pack_id !== null) {
                        $groupsPulled->put($enemy->enemyPack->group, true);
                    }
                    $this->log->createPullEnemyAttachedToKillZone(
                        $npcId,
                        $advancedData->getPositionX(),
                        $advancedData->getPositionY()
                    );
                }
            }
            finally {
                $this->log->createPullFindEnemyForGuidEnd();
            }
        }

        // Clear the collection - we just created a pull for all enemies
        $this->enemiesKilledInCurrentPull = collect();

        if ($killZone->enemies->isNotEmpty()) {
            $this->killZoneIndex++;
        } else {
            $killZone->delete();
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
    private function findUnkilledEnemyForNpcAtIngameLocation(
        int $npcId,
        float $ingameX,
        float $ingameY,
        Collection $preferredGroups
    ): ?Enemy {
        try {
            $this->log->findUnkilledEnemyForNpcAtIngameLocationStart($npcId, $ingameX, $ingameY);

            // Find the closest Enemy with the same NPC ID that is not killed yet
            $closestEnemyDistance = 99999999999;
            /** @var Enemy|null $closestEnemy */
            $closestEnemy = null;

            /** @var Collection|Enemy[] $filteredEnemies */
            $filteredEnemies = $this->availableEnemies->filter(function (Enemy $availableEnemy) use ($npcId)
            {
                if ($availableEnemy->npc_id !== $npcId) {
                    return false;
                }

                if ($availableEnemy->teeming !== null) {
                    return false;
                }

                // I'd like to have the check for floor_ids here but in-game a new floor is not always navigated when you expect it to.
                if (!in_array($availableEnemy->floor->dungeon->key, self::DUNGEON_ENEMY_FLOOR_CHECK_DISABLED) &&
                    $availableEnemy->floor_id !== $this->currentFloor->id) {
                    return false;
                }

                return true;
            });

            // Build a list of potential enemies which will always take precedence since they're in a group that we have aggroed.
            // Therefore these enemies should be in combat with us regardless
            /** @var Collection|Enemy[] $preferredEnemiesInEngagedGroups */
            $preferredEnemiesInEngagedGroups = $filteredEnemies->filter(function (Enemy $availableEnemy) use ($preferredGroups)
            {
                if ($availableEnemy->enemy_pack_id === null) {
                    return false;
                }

                return $preferredGroups->has($availableEnemy->enemyPack->group);
            });

            foreach ($preferredEnemiesInEngagedGroups as $availableEnemy) {
                $this->findClosestEnemyAndDistance($availableEnemy, $availableEnemy->lat, $availableEnemy->lng,
                    $ingameX, $ingameY, $closestEnemyDistance, $closestEnemy);
            }

            // If we found an enemy in one of our preferred packs, we must not continue searching
            if ($closestEnemy !== null) {
                $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyFoundInPreferredGroup(
                    $closestEnemy->id, $closestEnemyDistance, $closestEnemy->enemyPack->group
                );
            } else {
                foreach ($filteredEnemies as $availableEnemy) {
                    $this->findClosestEnemyAndDistance($availableEnemy, $availableEnemy->lat, $availableEnemy->lng,
                        $ingameX, $ingameY, $closestEnemyDistance, $closestEnemy);
                }

                $this->log->findUnkilledEnemyForNpcAtIngameLocationClosestEnemy(
                    optional($closestEnemy)->id, $closestEnemyDistance
                );

                // If the closest enemy was still pretty far away - check if there was a patrol that may have been closer
                if ($closestEnemyDistance > self::MAX_AGGRO_DISTANCE_FOR_PATROLS) {
                    $this->log->findUnkilledEnemyForNpcAtIngameLocationConsideringPatrols();

                    foreach ($filteredEnemies as $availableEnemy) {
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
                            $this->findClosestEnemyAndDistance($availableEnemy, $pointLatLng['lat'], $pointLatLng['lng'],
                                $ingameX, $ingameY, $closestEnemyDistance, $closestEnemy);
                        }
                    }
                }

                if ($closestEnemyDistance > self::MAX_DISTANCE_IGNORE) {
                    if ($closestEnemy !== null && $closestEnemy->npc->classification_id >= App\Models\NpcClassification::ALL[App\Models\NpcClassification::NPC_CLASSIFICATION_BOSS]) {
                        $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyIsBossIgnoringTooFarAwayCheck();
                    } else {
                        $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyTooFarAway(
                            optional($closestEnemy)->id, $closestEnemyDistance, self::MAX_DISTANCE_IGNORE
                        );
                        $closestEnemy = null;
                    }
                }
            }

            if ($closestEnemy !== null) {
                $enemyXY = $this->currentFloor->calculateIngameLocationForMapLocation($closestEnemy->lat, $closestEnemy->lng);
                $this->log->findUnkilledEnemyForNpcAtIngameLocationEnemyFound(
                    $closestEnemy->id, $enemyXY['x'], $enemyXY['y'], $closestEnemyDistance
                );

                $this->availableEnemies->forget($closestEnemy->id);
            }
        }
        finally {
            $this->log->findUnkilledEnemyForNpcAtIngameLocationEnd();
        }

        return $closestEnemy;
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
     * @return void
     */
    private function findClosestEnemyAndDistance(
        Enemy $availableEnemy,
        float $enemyLat,
        float $enemyLng,
        float $ingameX,
        float $ingameY,
        float &$closestEnemyDistance,
        ?Enemy &$closestEnemy
    ): void {
        // Always use the floor that the enemy itself is on, not $this->currentFloor
        $enemyXY = $availableEnemy->floor->calculateIngameLocationForMapLocation($enemyLat, $enemyLng);

        $distance = App\Logic\Utils\MathUtils::distanceBetweenPoints(
            $enemyXY['x'],
            $ingameX,
            $enemyXY['y'],
            $ingameY
        );

        if ($closestEnemyDistance > $distance) {
            $closestEnemyDistance = $distance;
            $closestEnemy         = $availableEnemy;
        }
    }
}
