<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd as ChallengeModeEndEvent;
use App\Models\Dungeon;

class ChallengeModeEnd extends BaseResultEvent
{
    private Dungeon $dungeon;

    public function __construct(ChallengeModeEndEvent $baseEvent)
    {
        parent::__construct($baseEvent);

        $this->dungeon = Dungeon::where('map_id', $baseEvent->getInstanceID())->firstOrFail();
    }

    /**
     * @return ChallengeModeEndEvent
     */
    public function getChallengeModeEndEvent(): ChallengeModeEndEvent
    {
        /** @var ChallengeModeEndEvent $baseEvent */
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
