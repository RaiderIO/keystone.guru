<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeCombatLogEvent;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Models\DungeonRoute;
use App\Service\CombatLog\Logging\ResultEventDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\ResultEvents\EnemyKilled;
use App\Service\CombatLog\ResultEvents\MapChange as MapChangeResultEvent;
use App\Service\CombatLog\ResultEvents\SpellCast;
use Illuminate\Support\Collection;

/**
 * @property Collection|EnemyEngaged[] $currentPullEnemiesKilled
 * @property Collection|EnemyEngaged[] $currentEnemiesInCombat
 *
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
                    // We are in combat with this enemy now
                    $this->currentEnemiesInCombat->put($resultEvent->getGuid()->getGuid(), $resultEvent);

                    $this->log->buildInCombatWithEnemy($resultEvent->getGuid()->getGuid());
                } else if ($resultEvent instanceof EnemyKilled) {
                    /** @var $baseEvent UnitDied */
                    // Check if we had this enemy in combat, if so, we just killed it in our current pull
                    // UnitDied only has DestGuid
                    $guid = $resultEvent->getGuid()->getGuid();
                    if ($this->currentEnemiesInCombat->has($guid)) {
                        $this->currentPullEnemiesKilled->put($guid, $this->currentEnemiesInCombat->get($guid));

                        $this->currentEnemiesInCombat->forget($guid);
                        $this->log->buildUnitDiedNoLongerInCombat($guid);
                    } else {
                        $this->log->buildUnitDiedNotInCombat($guid);
                    }

                    // If we just killed the last enemy that we were in combat with, we just completed a pull
                    if ($this->currentEnemiesInCombat->isEmpty()) {
                        $this->log->buildCreateNewPull($this->currentPullEnemiesKilled->keys()->toArray());

                        $this->createPull();
                    }
                } else if ($resultEvent instanceof SpellCast) {
                    $this->log->buildSpellCast(
                        $resultEvent->getAdvancedCombatLogEvent()->getAdvancedData()->getInfoGuid()->getGuid(),
                        $resultEvent->getSpellId()
                    );

                    $this->currentPullSpellsCast->push($resultEvent->getSpellId());
                }
            } finally {
                $this->log->buildEnd();
            }
        }

        // Ensure that we create a final pull if need be
        if ($this->currentPullEnemiesKilled->isNotEmpty()) {
            $this->log->buildCreateNewFinalPull($this->currentPullEnemiesKilled->keys()->toArray());
            $this->createPull();
        }

        $this->recalculateEnemyForcesOnDungeonRoute();

        return $this->dungeonRoute;
    }

    /**
     * @return Collection|array{array{npcId: int, x: float, y: float}}
     */
    public function convertEnemiesKilledInCurrentPull(): Collection
    {
        return $this->currentPullEnemiesKilled->map(function (EnemyEngaged $resultEvent) {
            return [
                'npcId' => $resultEvent->getGuid()->getId(),
                'x'     => $resultEvent->getEngagedEvent()->getAdvancedData()->getPositionX(),
                'y'     => $resultEvent->getEngagedEvent()->getAdvancedData()->getPositionY(),
            ];
        });
    }
}
