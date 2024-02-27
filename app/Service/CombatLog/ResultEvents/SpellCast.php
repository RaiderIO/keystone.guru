<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;

class SpellCast extends BaseResultEvent
{
    public function __construct(AdvancedCombatLogEvent $baseEvent)
    {
        parent::__construct($baseEvent);
    }

    public function getAdvancedCombatLogEvent(): AdvancedCombatLogEvent
    {
        /** @var AdvancedCombatLogEvent $baseEvent */
        $baseEvent = $this->getBaseEvent();

        return $baseEvent;
    }

    public function getSpellId(): int
    {
        /** @var Spell $prefix */
        $prefix = $this->getAdvancedCombatLogEvent()->getPrefix();

        return $prefix->getSpellId();
    }
}
