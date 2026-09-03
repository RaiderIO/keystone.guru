<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeCombatLogEvent;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Service\CombatLog\Builders\Logging\ResultEventDungeonRouteBuilderLoggingInterface;
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

    /**
     * @var Collection<string, ActivePullEnemy> Every enemy we have seen engaged, by guid - including the ones that
     *                                          resolved to no mapped Enemy and are therefore in no pull
     */
    private Collection $engagedEnemiesByGuid;

    public function __construct(
        CoordinatesServiceInterface      $coordinatesService,
        DungeonRouteRepositoryInterface  $dungeonRouteRepository,
        KillZoneRepositoryInterface      $killZoneRepository,
        KillZoneEnemyRepositoryInterface $killZoneEnemyRepository,
        KillZoneSpellRepositoryInterface $killZoneSpellRepository,
        EnemyRepositoryInterface         $enemyRepository,
        NpcRepositoryInterface           $npcRepository,
        DungeonRoute                     $dungeonRoute,
        /** @var Collection<int, BaseResultEvent> */
        private readonly Collection      $resultEvents,
    ) {
        $this->log                  = App::make(ResultEventDungeonRouteBuilderLoggingInterface::class);
        $this->engagedEnemiesByGuid = collect();

        parent::__construct(
            $coordinatesService,
            $dungeonRouteRepository,
            $killZoneRepository,
            $killZoneEnemyRepository,
            $killZoneSpellRepository,
            $enemyRepository,
            $npcRepository,
            $dungeonRoute,
            $this->log,
        );
    }

    public function build(): DungeonRoute
    {
        foreach ($this->resultEvents as $resultEvent) {
            try {
                $baseEvent = $resultEvent->getBaseEvent();
                $this->log->buildStart(
                    $baseEvent->getTimestamp()->toDateTimeString(),
                    $baseEvent->getEventName(),
                );

                if ($resultEvent instanceof MapChangeResultEvent) {
                    /** @var MapChangeCombatLogEvent $baseEvent */
                    $this->currentFloor = $resultEvent->getFloor();
                } elseif ($this->currentFloor === null) {
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
                        } elseif ($activePull->isCompleted()) {
                            $activePull = $this->activePullCollection->addNewPull();

                            $this->log->buildCreateNewActivePullChainPullCompleted();
                        } // Check if we need to account for chain pulling
                        elseif (($activePullAverageHPPercent = $activePull->getAverageHPPercentAt($resultEvent->getEngagedEvent()->getTimestamp()))
                            <= self::CHAIN_PULL_DETECTION_HP_PERCENT) {
                            $activePull = $this->activePullCollection->addNewPull();

                            $this->log->buildCreateNewActiveChainPull($activePullAverageHPPercent, self::CHAIN_PULL_DETECTION_HP_PERCENT);
                        }

                        $activePullEnemy = $this->createActivePullEnemy($resultEvent);
                        // Remembered even when it resolves to nothing below: its death may still be what triggers a
                        // rule into awarding kills, and this is the only record of where it stood when we met it
                        $this->engagedEnemiesByGuid->put($activePullEnemy->getUniqueId(), $activePullEnemy);

                        $resolvedEnemy = $this->findUnkilledEnemyForNpcAtIngameLocation(
                            $activePullEnemy,
                            $this->activePullCollection->getInCombatGroups(),
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
                } elseif ($resultEvent instanceof EnemyKilled) {
                    if ($this->validNpcIds->search($resultEvent->getGuid()->getId()) === false) {
                        // No need to log really
                        continue;
                    }

                    /** @var UnitDied $baseEvent */
                    // Check if we had this enemy in combat, if so, we just killed it in our current pull
                    // UnitDied only has DestGuid
                    $guid = $resultEvent->getGuid()->getGuid();

                    // Find the pull that this enemy is part of
                    $diedInActivePull    = null;
                    $diedActivePullEnemy = null;
                    foreach ($this->activePullCollection as $activePull) {
                        /** @var ActivePull $activePull */
                        if ($activePull->isEnemyInCombat($guid)) {
                            // Grab it before enemyKilled() moves it out of the in-combat collection - it is the only
                            // thing on this path that knows which Enemy the kill resolved to and where it stood
                            $diedActivePullEnemy = $activePull->getEnemiesInCombat()->get($guid);
                            $activePull->enemyKilled($guid);
                            $diedInActivePull = $activePull;
                            $this->log->buildEnemyKilled($guid, $resultEvent->getBaseEvent()->getTimestamp()->toDateTimeString());
                        }
                    }

                    // An UnitDied carries no position of its own, so an award has to borrow the one we recorded when
                    // we first met this enemy - which we did even if it never resolved to a mapped enemy and is
                    // therefore in no pull at all. That mirrors CombatLogRouteDungeonRouteBuilder, where the DTO the
                    // trigger is built from always carries a position regardless of how the death resolved.
                    $diedActivePullEnemy ??= $this->engagedEnemiesByGuid->get($guid);

                    $awardedNpcIds = $this->notifyRulesEnemyDied(
                        $resultEvent->getGuid()->getId(),
                        $diedActivePullEnemy?->getResolvedEnemy(),
                    );

                    // Must happen before the pulls below are created, so the awarded kills are part of the pull that
                    // triggered them rather than of one after it
                    if ($awardedNpcIds->isNotEmpty()) {
                        if ($diedActivePullEnemy === null) {
                            // A kill we were never told the position of - EncounterEnd and the defeated-percentage
                            // threshold both produce one for an enemy that was never engaged. There is nothing to
                            // resolve the award against, and a rule considers what it returned accounted for, so
                            // these npcs are lost for the rest of the build. Loud rather than silent because it
                            // means a boss missing from the route.
                            $this->log->buildAwardedEnemyKillsDroppedWithoutTrigger($guid, $awardedNpcIds->all());
                        } else {
                            $this->awardEnemyKills(
                                $awardedNpcIds,
                                $diedInActivePull,
                                $diedActivePullEnemy,
                            );
                        }
                    }

                    // Handle the actual creation of pulls
                    foreach ($this->activePullCollection as $pullIndex => $activePull) {
                        /** @var ActivePull $activePull */
                        if ($activePull->getEnemiesInCombat()->isEmpty()) {
                            $this->createPull($activePull);

                            $this->activePullCollection->forget($pullIndex);
                        }
                    }
                } elseif ($resultEvent instanceof SpellCast) {
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
                        // We use the owner guid if available (in case a pet cast this), otherwise we use the info guid (which is the owner/caster)
                        $resultEvent->getAdvancedCombatLogEvent()->getAdvancedData()->getOwnerGuid()?->getGuid() ??
                        $resultEvent->getAdvancedCombatLogEvent()->getAdvancedData()->getInfoGuid()->getGuid(),
                        $resultEvent->getSpellId(),
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
            $enemyEngaged->getResolvedEnemy(),
        );
    }
}
