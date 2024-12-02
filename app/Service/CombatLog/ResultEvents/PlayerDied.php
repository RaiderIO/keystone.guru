<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\UnitDied;

class PlayerDied extends BaseResultEvent
{
    public function __construct(
        UnitDied                $unitDied,
        private readonly Player $guid
    ) {
        parent::__construct($unitDied);
    }

    public function getGuid(): Player
    {
        return $this->guid;
    }
}
