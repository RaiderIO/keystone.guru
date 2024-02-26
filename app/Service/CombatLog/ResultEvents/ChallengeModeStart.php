<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart as ChallengeModeStartEvent;
use App\Models\Dungeon;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use Exception;

class ChallengeModeStart extends BaseResultEvent
{
    private Dungeon $dungeon;

    public function __construct(ChallengeModeStartEvent $baseEvent)
    {
        parent::__construct($baseEvent);

        try {
            $this->dungeon = Dungeon::where('challenge_mode_id', $baseEvent->getChallengeModeID())->firstOrFail();
        } catch (Exception) {
            throw new DungeonNotSupportedException(
                sprintf('Dungeon with challenge mode ID %d not found', $baseEvent->getChallengeModeID())
            );
        }
    }

    public function getChallengeModeStartEvent(): ChallengeModeStartEvent
    {
        /** @var ChallengeModeStartEvent $baseEvent */
        $baseEvent = $this->getBaseEvent();

        return $baseEvent;
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }
}
