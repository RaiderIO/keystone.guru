<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\Guid\Creature;

class EnemyKilled extends BaseResultEvent
{
    /**
     * @param BaseEvent $baseEvent
     * @param Creature  $guid The GUID _can_ be extracted from the BaseEvent. Sometimes. But not always (EncounterEnd),
     * so we have to pass it here so we know which enemy was killed.
     */
    public function __construct(
        BaseEvent                 $baseEvent,
        private readonly Creature $guid
    ) {
        parent::__construct($baseEvent);
    }

    public function getGuid(): Creature
    {
        return $this->guid;
    }
}
