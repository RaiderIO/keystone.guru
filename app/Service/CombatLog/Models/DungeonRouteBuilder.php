<?php

namespace App\Service\CombatLog\Models;

use App;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeCombatLogEvent;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\KillZone;
use App\Models\KillZoneEnemy;
use App\Service\CombatLog\Logging\DungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Models\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\Models\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\Models\ResultEvents\EnemyKilled;
use App\Service\CombatLog\Models\ResultEvents\MapChange as MapChangeResultEvent;
use Exception;
use Illuminate\Support\Collection;

class DungeonRouteBuilder
{
    private DungeonRoute $dungeonRoute;

    /** @var Collection|BaseResultEvent[] */
    private Collection $resultEvents;

    private DungeonRouteBuilderLoggingInterface $log;

    /** @var Collection|EnemyEngaged[] */
    private Collection $enemiesKilledInCurrentPull;

    /** @var Collection|EnemyEngaged[] */
    private Collection $currentEnemiesInCombat;

    private ?Floor $currentFloor;

    private int $killZoneIndex = 1;

    /** @var Collection|Enemy[] */
    private Collection $availableEnemies;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param Collection|BaseResultEvent[] $resultEvents
     */
    public function __construct(DungeonRoute $dungeonRoute, Collection $resultEvents)
    {
        $this->dungeonRoute               = $dungeonRoute;
        $this->resultEvents               = $resultEvents;
        $this->log                        = App::make(DungeonRouteBuilderLoggingInterface::class);
        $this->enemiesKilledInCurrentPull = collect();
        $this->currentEnemiesInCombat     = collect();
        $this->currentFloor               = null;
        $this->availableEnemies           = $this->dungeonRoute->mappingVersion->enemies->keyBy('id');
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
                } else if ($this->currentFloor === null) {
                    $this->log->buildNoFloorFoundYet();
                    continue;
                }

                if ($resultEvent instanceof EnemyEngaged) {
                    // We are in combat with this enemy now
                    $this->currentEnemiesInCombat->put($resultEvent->getGuid()->getGuid(), $resultEvent);

                    $this->log->buildInCombatWithEnemy($resultEvent->getGuid()->getGuid());
                } else if ($resultEvent instanceof EnemyKilled) {
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
            } finally {
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

        foreach ($this->enemiesKilledInCurrentPull as $guid => $enemyEngagedEvent) {
            /** @var EnemyEngaged $enemyEngagedEvent */
            $advancedData = $enemyEngagedEvent->getEngagedEvent()->getAdvancedData();
            $enemy        = $this->findUnkilledEnemyForNpcAtIngameLocation(
                $enemyEngagedEvent->getGuid()->getId(),
                $advancedData->getPositionX(),
                $advancedData->getPositionY()
            );

            if ($enemy === null) {
                $this->log->buildEnemyNotFound(
                    $enemyEngagedEvent->getGuid()->getId(),
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
                $this->log->buildEnemyAttachedToKillZone(
                    $enemyEngagedEvent->getGuid()->getId(),
                    $advancedData->getPositionX(),
                    $advancedData->getPositionY()
                );
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
     * @param int $npcId
     * @param float $ingameX
     * @param float $ingameY
     * @return Enemy|null
     */
    private function findUnkilledEnemyForNpcAtIngameLocation(
        int   $npcId,
        float $ingameX,
        float $ingameY
    ): ?Enemy
    {
        $latLng = $this->currentFloor->calculateMapLocationForIngameLocation($ingameX, $ingameY);

        // Find the closest Enemy with the same NPC ID that is not killed yet
        $closestEnemyDistance = 99999999999;
        $closestEnemy         = null;
        foreach ($this->availableEnemies as $availableEnemy) {
            if ($availableEnemy->npc_id !== $npcId) {
                continue;
            }

            // Ignore teeming enemies..
            if ($availableEnemy->teeming !== null) {
                continue;
            }

            $distance = App\Logic\Utils\MathUtils::distanceBetweenPoints(
                $availableEnemy->lng,
                $latLng['lng'],
                $availableEnemy->lat,
                $latLng['lat']
            );

            if ($closestEnemyDistance > $distance) {
                $closestEnemyDistance = $distance;
                $closestEnemy         = $availableEnemy;
            }
        }

        if ($closestEnemy !== null) {
            $this->availableEnemies->forget($closestEnemy->id);
        }


        return $closestEnemy;
    }
}
