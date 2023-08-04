<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\Enemy;

/**
 * @package App\Service\CombatLog\Models\ResultEvents
 * @author Wouter
 * @since 01/06/2023
 */
class EnemyEngaged extends BaseResultEvent
{
    private ?Enemy $resolvedEnemy = null;

    /**
     * @param AdvancedCombatLogEvent $baseEvent
     */
    public function __construct(AdvancedCombatLogEvent $baseEvent)
    {
        parent::__construct($baseEvent);
    }

    /**
     * @return AdvancedCombatLogEvent
     */
    public function getEngagedEvent(): AdvancedCombatLogEvent
    {
        /** @var AdvancedCombatLogEvent $baseEvent */
        $baseEvent = $this->getBaseEvent();

        return $baseEvent;
    }

    /**
     * @return Creature
     */
    public function getGuid(): Creature
    {
        /** @var Creature $guid */
        $guid = $this->getEngagedEvent()->getAdvancedData()->getInfoGuid();

        return $guid;
    }

    /**
     * @return Enemy|null
     */
    public function getResolvedEnemy(): ?Enemy
    {
        return $this->resolvedEnemy;
    }

    /**
     * @param Enemy $resolvedEnemy
     * @return self
     */
    public function setResolvedEnemy(Enemy $resolvedEnemy): self
    {
        $this->resolvedEnemy = $resolvedEnemy;

        return $this;
    }
}
