<?php

namespace App\Service\LiveSession\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Suffixes\Summon;

/**
 * Keeps summon events. The auto-route creator's combat filter mutates kill-counting state when it sees a
 * summon (the summoned unit is excluded from enemy forces) but does not report the event as relevant, so
 * the summon line must be retained explicitly for the reduced buffer to reconstruct that state.
 */
class SummonBufferFilter implements LiveSessionBufferFilterInterface
{
    public function shouldKeep(BaseEvent $combatLogEvent): bool
    {
        return $combatLogEvent instanceof CombatLogEvent && $combatLogEvent->getSuffix() instanceof Summon;
    }
}
