<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\ZoneChange as ZoneChangeEvent;
use App\Models\Dungeon;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use Exception;

class ZoneChange extends BaseResultEvent
{
    private ?Dungeon $dungeon = null;

    public function __construct(ZoneChangeEvent $baseEvent)
    {
        parent::__construct($baseEvent);

        try {
            $this->dungeon = Dungeon::where('map_id', $baseEvent->getZoneId())->firstOrFail();
        } catch (Exception) {
            throw new DungeonNotSupportedException(
                sprintf('Dungeon with map ID %d not found', $baseEvent->getZoneId())
            );
        }
    }

    public function getDungeon(): ?Dungeon
    {
        return $this->dungeon;
    }

    public function getZoneChangeEvent(): ZoneChangeEvent
    {
        /** @var ZoneChangeEvent $zoneChangeEvent */
        $zoneChangeEvent = $this->getBaseEvent();

        return $zoneChangeEvent;
    }
}
