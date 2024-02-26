<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd as ChallengeModeEndEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart as ChallengeModeStartEvent;
use App\Models\Dungeon;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use Exception;

class ChallengeModeEnd extends BaseResultEvent
{
    private Dungeon $dungeon;

    public function __construct(ChallengeModeStartEvent $challengeModeStart, ChallengeModeEndEvent $challengeModeEnd)
    {
        parent::__construct($challengeModeEnd);

        try {
            $this->dungeon = Dungeon::where('challenge_mode_id', $challengeModeStart->getChallengeModeID())->firstOrFail();
        } catch (Exception) {
            throw new DungeonNotSupportedException(
                sprintf('Dungeon with challenge mode ID %d not found', $challengeModeStart->getChallengeModeID())
            );
        }
    }

    public function getChallengeModeEndEvent(): ChallengeModeEndEvent
    {
        /** @var ChallengeModeEndEvent $baseEvent */
        $baseEvent = $this->getBaseEvent();

        return $baseEvent;
    }

    public function getDungeon(): Dungeon
    {
        return $this->dungeon;
    }
}
