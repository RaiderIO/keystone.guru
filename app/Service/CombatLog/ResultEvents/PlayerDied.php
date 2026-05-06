<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\UnitDied;

class PlayerDied extends BaseResultEvent
{
    public function __construct(
        UnitDied                                 $unitDied,
        private readonly Player                  $guid,
        private readonly ?AdvancedCombatLogEvent $lastKnownEvent,
    ) {
        parent::__construct($unitDied);
    }

    public function getGuid(): Player
    {
        return $this->guid;
    }

    public function getLastKnownEvent(): ?AdvancedCombatLogEvent
    {
        return $this->lastKnownEvent;
    }
}
