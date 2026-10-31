<?php

namespace App\Service\LiveSession\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;

/**
 * Decides whether a combat-log event must be retained when reducing a live-session buffer, on top of
 * whatever the auto-route creator already considers relevant.
 *
 * Each filter is responsible for a single "extra" reason to keep an event (e.g. player movement). Filters
 * may be stateful across the events of a single reduction pass (so a fresh instance is used per pass).
 */
interface LiveSessionBufferFilterInterface
{
    public function shouldKeep(BaseEvent $combatLogEvent): bool;
}
