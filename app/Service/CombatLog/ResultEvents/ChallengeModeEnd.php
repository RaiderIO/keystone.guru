<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd as ChallengeModeEndEvent;
use App\Models\Dungeon;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use Exception;

class ChallengeModeEnd extends BaseResultEvent
{
    private Dungeon $dungeon;

    public function __construct(ChallengeModeEndEvent $baseEvent)
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
