<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeCombatLogEvent;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Service\CombatLog\Logging\ResultEventDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Models\ActivePull\ActivePull;
use App\Service\CombatLog\Models\ActivePull\ActivePullEnemy;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\ResultEvents\EnemyKilled;
use App\Service\CombatLog\ResultEvents\MapChange as MapChangeResultEvent;
use App\Service\CombatLog\ResultEvents\SpellCast;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

/**
 * @author Wouter
 *
 * @since 24/06/2023
 */
class ResultEventDungeonRouteBuilder extends DungeonRouteBuilder
{
    private readonly ResultEventDungeonRouteBuilderLoggingInterface $log;

    public function __construct(
        CoordinatesServiceInterface      $coordinatesService,
        DungeonRouteRepositoryInterface  $dungeonRouteRepository,
        KillZoneRepositoryInterface      $killZoneRepository,
        KillZoneEnemyRepositoryInterface $killZoneEnemyRepository,
        KillZoneSpellRepositoryInterface $killZoneSpellRepository,
        DungeonRoute                     $dungeonRoute,
        /** @var Collection|BaseResultEvent[] */
        private readonly Collection      $resultEvents
    ) {
        $this->log = App::make(ResultEventDungeonRouteBuilderLoggingInterface::class);

        parent::__construct(
            $coordinatesService,
            $dungeonRouteRepository,
            $killZoneRepository,
            $killZoneEnemyRepository,
            $killZoneSpellRepository,
            $dungeonRoute,
            $this->log
        );
    }

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
                    if ($this->validNpcIds->search($resultEvent->getGuid()->getId()) !== false) {

                        /** @var ActivePull|null $activePull */
                        $activePull = $this->activePullCollection->last();

                        if ($activePull === null) {
                            $activePull = $this->activePullCollection->addNewPull();

                            $this->log->buildCreateNewActivePull();
                        } else if ($activePull->isCompleted()) {
                            $activePull = $this->activePullCollection->addNewPull();

                            $this->log->buildCreateNewActivePullChainPullCompleted();
                        } // Check if we need to account for chain pulling
                        else if (($activePullAverageHPPercent = $activePull->getAverageHPPercentAt($resultEvent->getEngagedEvent()->getTimestamp()))
                            <= self::CHAIN_PULL_DETECTION_HP_PERCENT) {
                            $activePull = $this->activePullCollection->addNewPull();

                            $this->log->buildCreateNewActiveChainPull($activePullAverageHPPercent, self::CHAIN_PULL_DETECTION_HP_PERCENT);
                        }

                        $activePullEnemy = $this->createActivePullEnemy($resultEvent);
                        $resolvedEnemy   = $this->findUnkilledEnemyForNpcAtIngameLocation(
                            $activePullEnemy,
                            $this->activePullCollection->getInCombatGroups()
                        );

                        if ($resolvedEnemy === null) {
                            $this->log->buildUnableToFindEnemyForNpc($resultEvent->getGuid()->getGuid());

                            continue;
                        }

                        // Ensure we know about the enemy being resolved fully
                        $resultEvent->setResolvedEnemy($resolvedEnemy);
                        $activePullEnemy->setResolvedEnemy($resolvedEnemy);

                        // We are in combat with this enemy now
                        $activePull->enemyEngaged($activePullEnemy);

                        $this->log->buildInCombatWithEnemy($resultEvent->getGuid()->getGuid());
                    } else {
                        $this->log->buildEnemyNotInValidNpcIds($resultEvent->getGuid()->getGuid());
                    }
                } else if ($resultEvent instanceof EnemyKilled) {
                    if ($this->validNpcIds->search($resultEvent->getGuid()->getId()) === false) {
                        // No need to log really
                        continue;
                    }

                    /** @var $baseEvent UnitDied */
                    // Check if we had this enemy in combat, if so, we just killed it in our current pull
                    // UnitDied only has DestGuid
                    $guid = $resultEvent->getGuid()->getGuid();

                    // Find the pull that this enemy is part of
                    foreach ($this->activePullCollection as $activePull) {
                        /** @var $activePull ActivePull */
                        if ($activePull->isEnemyInCombat($guid)) {
                            $activePull->enemyKilled($guid);
                            $this->log->buildEnemyKilled($guid, $resultEvent->getBaseEvent()->getTimestamp()->toDateTimeString());
                        }
                    }

                    // Handle the actual creation of pulls
                    foreach ($this->activePullCollection as $pullIndex => $activePull) {
                        /** @var $activePull ActivePull */
                        if ($activePull->getEnemiesInCombat()->isEmpty()) {
                            $this->createPull($activePull);

                            $this->activePullCollection->forget($pullIndex);
                        }
                    }
                } else if ($resultEvent instanceof SpellCast) {
                    // Add BL to the newest pull
                    if ($this->activePullCollection->isEmpty()) {
                        $activePull = $this->activePullCollection->addNewPull();

                        $this->log->buildCreateNewActivePull();
                    } else {
                        /** @var ActivePull $activePull */
                        $activePull = $this->activePullCollection->last();
                    }

                    $activePull->addSpell($resultEvent->getSpellId());

                    $this->log->buildSpellCast(
                        $resultEvent->getAdvancedCombatLogEvent()->getAdvancedData()->getInfoGuid()->getGuid(),
                        $resultEvent->getSpellId()
                    );
                }
            } finally {
                $this->log->buildEnd();
            }
        }

        // Handle spells and the actual creation of pulls for all remaining active pulls
        foreach ($this->activePullCollection as $activePull) {
            if ($activePull->getEnemiesInCombat()->isEmpty()) {
                $this->log->buildCreateNewFinalPull($activePull->getEnemiesKilled()->keys()->toArray());

                $this->createPull($activePull);
            }
        }

        $this->buildFinished();

        return $this->dungeonRoute;
    }

    private function createActivePullEnemy(EnemyEngaged $enemyEngaged): ActivePullEnemy
    {
        return new ActivePullEnemy(
            $enemyEngaged->getGuid()->getGuid(),
            $enemyEngaged->getGuid()->getId(),
            $enemyEngaged->getEngagedEvent()->getAdvancedData()->getPositionX(),
            $enemyEngaged->getEngagedEvent()->getAdvancedData()->getPositionY(),
            $enemyEngaged->getEngagedEvent()->getTimestamp(),
            // @TODO We don't know this yet!
            null,
            $enemyEngaged->getResolvedEnemy()
        );
    }
}
