<?php

namespace App\Logic\SimulationCraft;

interface RaidEventsCollectionInterface
{
    /**
     * @return $this
     */
    public function calculateRaidEvents(): self;
}
