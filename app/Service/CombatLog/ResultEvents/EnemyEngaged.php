<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\Enemy;

/**
 * @author Wouter
 *
 * @since 01/06/2023
 */
class EnemyEngaged extends BaseResultEvent
{
    private ?Enemy $resolvedEnemy = null;

    public function __construct(AdvancedCombatLogEvent $baseEvent)
    {
        parent::__construct($baseEvent);
    }

    public function getEngagedEvent(): AdvancedCombatLogEvent
    {
        /** @var AdvancedCombatLogEvent $baseEvent */
        $baseEvent = $this->getBaseEvent();

        return $baseEvent;
    }

    public function getGuid(): Creature
    {
        /** @var Creature $guid */
        $guid = $this->getEngagedEvent()->getAdvancedData()->getInfoGuid();

        return $guid;
    }

    public function getResolvedEnemy(): ?Enemy
    {
        return $this->resolvedEnemy;
    }

    public function setResolvedEnemy(Enemy $resolvedEnemy): self
    {
        $this->resolvedEnemy = $resolvedEnemy;

        return $this;
    }
}
