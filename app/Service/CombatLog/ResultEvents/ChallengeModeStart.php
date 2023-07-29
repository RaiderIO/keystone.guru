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
            $this->dungeon = Dungeon::where('map_id', $baseEvent->getInstanceID())->firstOrFail();
        } catch (Exception $exception) {
            throw new DungeonNotSupportedException(
                sprintf('Dungeon with instance ID %d not found', $baseEvent->getInstanceID())
            );
        }
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
