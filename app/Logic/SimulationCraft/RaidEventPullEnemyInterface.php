<?php

namespace App\Logic\SimulationCraft;

use App\Models\Npc;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;

interface RaidEventPullEnemyInterface
{
    /**
     * @param SimulationCraftRaidEventsOptions $options
     * @param Npc $npc
     * @return int
     */
    public function calculateHealth(SimulationCraftRaidEventsOptions $options, Npc $npc): int;
}
