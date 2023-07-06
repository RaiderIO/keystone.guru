<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;

class SpellCast extends BaseResultEvent
{
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
    public function getAdvancedCombatLogEvent(): AdvancedCombatLogEvent
    {
        /** @var AdvancedCombatLogEvent $baseEvent */
        $baseEvent = $this->getBaseEvent();

        return $baseEvent;
    }

    /**
     * @return int
     */
    public function getSpellId(): int
    {
        /** @var Spell $prefix */
        $prefix = $this->getAdvancedCombatLogEvent()->getPrefix();

        return $prefix->getSpellId();
    }
}
