<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeCombatLogEvent;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Models\DungeonRoute;
use App\Service\CombatLog\Logging\ResultEventDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Models\ActivePull\ActivePull;
use App\Service\CombatLog\Models\ActivePull\ResultEventActivePull;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\ResultEvents\EnemyKilled;
use App\Service\CombatLog\ResultEvents\MapChange as MapChangeResultEvent;
use App\Service\CombatLog\ResultEvents\SpellCast;
use Illuminate\Support\Collection;

/**
 * @package App\Service\CombatLog\Builders
 * @author Wouter
 * @since 24/06/2023
 */
class ResultEventDungeonRouteBuilder extends DungeonRouteBuilder
{
    /** @var Collection|BaseResultEvent[] */
    private Collection $resultEvents;

    private ResultEventDungeonRouteBuilderLoggingInterface $log;

    public function __construct(DungeonRoute $dungeonRoute, Collection $resultEvents)
    {
        parent::__construct($dungeonRoute);

        $this->resultEvents = $resultEvents;

        /** @var ResultEventDungeonRouteBuilderLoggingInterface $log */
        $log       = App::make(ResultEventDungeonRouteBuilderLoggingInterface::class);
        $this->log = $log;
    }

    /**
     * @return DungeonRoute
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
                    if ($this->validNpcIds->search($resultEvent->getGuid()->getId()) !== false) {
                        if ($this->activePulls->isEmpty()) {
                            $activePull = new ResultEventActivePull();
                            $this->activePulls->push($activePull);

                            $this->log->buildCreateNewActivePull();
                        } else {
                            /** @var ActivePull $activePull */
                            $activePull = $this->activePulls->last();
                        }

                        // Check if we need to account for chain pulling
                        $activePullAverageHPPercent = $activePull->getAverageHPPercentAt($resultEvent->getEngagedEvent()->getTimestamp());
                        if ($activePullAverageHPPercent <= self::CHAIN_PULL_DETECTION_HP_PERCENT) {
                            $activePull = new ResultEventActivePull();
                            $this->activePulls->push($activePull);

                            $this->log->buildCreateNewActiveChainPull($activePullAverageHPPercent, self::CHAIN_PULL_DETECTION_HP_PERCENT);
                        }

                        // We are in combat with this enemy now
                        $activePull->enemyEngaged($resultEvent->getGuid()->getGuid(), $resultEvent);

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
                    foreach ($this->activePulls as $activePull) {
                        if ($activePull->isEnemyInCombat($guid)) {
                            $activePull->enemyKilled($guid, $activePull->getEnemiesInCombat()->get($guid));
                            $this->log->buildEnemyKilled($guid, $resultEvent->getBaseEvent()->getTimestamp()->toDateTimeString());
                        }
                    }

                    // Handle spells and the actual creation of pulls
                    foreach ($this->activePulls as $pullIndex => $activePull) {
                        if ($activePull->getEnemiesInCombat()->isEmpty()) {
                            $this->createPull($activePull);

                            $this->activePulls->forget($pullIndex);
                        }
                    }
                } else if ($resultEvent instanceof SpellCast) {
                    // Add BL to the newest pull
                    /** @var ActivePull $currentlyActivePull */
                    $currentlyActivePull = $this->activePulls->last();

                    $currentlyActivePull->addSpell($resultEvent->getSpellId());

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
        foreach ($this->activePulls as $activePull) {
            if ($activePull->getEnemiesInCombat()->isEmpty()) {
                $this->log->buildCreateNewFinalPull($activePull->getEnemiesKilled()->keys()->toArray());

                $this->createPull($activePull);
            }
        }

        $this->activePulls = collect();

        $this->recalculateEnemyForcesOnDungeonRoute();

        return $this->dungeonRoute;
    }

    /**
     * @param ActivePull $activePull
     * @return Collection
     */
    public function convertEnemiesKilledInActivePull(ActivePull $activePull): Collection
    {
        return $activePull->getEnemiesKilled()->mapWithKeys(function (EnemyEngaged $resultEvent, string $guid) {
            return [
                $guid => [
                    'npcId' => $resultEvent->getGuid()->getId(),
                    'x'     => $resultEvent->getEngagedEvent()->getAdvancedData()->getPositionX(),
                    'y'     => $resultEvent->getEngagedEvent()->getAdvancedData()->getPositionY(),
                ]
            ];
        });
    }
}
