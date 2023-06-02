<?php

namespace App\Service\CombatLog\Models\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart as ChallengeModeStartEvent;
use App\Models\Dungeon;

class ChallengeModeStart extends BaseResultEvent
{
    private Dungeon $dungeon;

    public function __construct(ChallengeModeStartEvent $baseEvent)
    {
        parent::__construct($baseEvent);

        $this->dungeon = Dungeon::where('map_id', $baseEvent->getInstanceID())->firstOrFail();
    }

    /**
     * @return ChallengeModeStartEvent
     */
    public function getChallengeModeStartEvent(): ChallengeModeStartEvent
    {
        /** @var ChallengeModeStartEvent $baseEvent */
        $baseEvent = $this->getBaseEvent();

        return $baseEvent;
    }

    /**
     * @return Dungeon
     */
    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }
}
