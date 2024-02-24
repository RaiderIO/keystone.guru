<?php

namespace App\Logic\SimulationCraft;

use App\Models\Npc;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;

interface RaidEventPullEnemyInterface
{
    /**
     * @return int
     */
    public function calculateHealth(SimulationCraftRaidEventsOptions $options, Npc $npc): int;
}
