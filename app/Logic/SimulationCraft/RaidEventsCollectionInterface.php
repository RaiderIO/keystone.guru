<?php

namespace App\Logic\SimulationCraft;

use Illuminate\Support\Collection;

interface RaidEventsCollectionInterface
{
    /**
     * @return $this
     */
    public function calculateRaidEvents(): self;

    /**
     * @return Collection<int, RaidEventPull>
     */
    public function getRaidEventPulls(): Collection;
}
